#!/usr/bin/env python3
"""Disposable Docker regression test; requires Docker, Python 3 and free loopback ports."""
import http.client
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
import uuid

ROOT = Path(__file__).resolve().parents[1]
PHP = "php:8.4-fpm-alpine"
NGINX = "nginx:mainline-alpine"
CADDY = "caddy:2-alpine"


def docker(*args):
    return subprocess.check_output(["docker", *map(str, args)], text=True).strip()


def check(condition, message):
    if not condition:
        raise AssertionError(message)


def request(port, path, method="GET", headers=None):
    conn = http.client.HTTPConnection("127.0.0.1", port, timeout=3)
    conn.request(method, path, headers=headers or {})
    return conn, conn.getresponse()


def closed(port, path, status, method="GET", headers=None):
    conn, response = request(port, path, method, headers)
    try:
        check(response.status == status, f"{method} {path}: {response.status} != {status}")
        return dict(response.getheaders()), response.read()
    finally:
        conn.close()


def port_of(name, port):
    return int(docker("port", name, f"{port}/tcp").rsplit(":", 1)[1])


def wait(port):
    deadline = time.monotonic() + 15
    while True:
        try:
            conn = socket.create_connection(("127.0.0.1", port), timeout=1)
            conn.close()
            return
        except OSError:
            if time.monotonic() > deadline:
                raise
            time.sleep(.1)


def main():
    names = []
    net = "lg-test-" + uuid.uuid4().hex[:10]
    work = tempfile.TemporaryDirectory(prefix="lg-offload-", dir=os.environ.get("LG_TEST_TMP"))
    folder = Path(work.name)
    held = []
    try:
        docker("network", "create", net)
        site = folder / "site"
        site.mkdir()
        for name in ("download.php", "LookingGlass.php"):
            shutil.copyfile(ROOT / name, site / name)
        (site / "config.php").write_text("<?php\n")
        (site / "probe.php").write_text(
            "<?php require __DIR__.'/LookingGlass.php'; "
            "Hybula\\LookingGlass::startSession(); echo 'ready';")
        files = folder / "files"
        files.mkdir()
        for name, size in (("100MB", 100*1024**2), ("1GB", 1024**3), ("10GB", 10*1024**3)):
            with (files / (name + ".bin")).open("wb") as stream:
                stream.truncate(size)
        # One PHP worker makes even one surviving streaming path observable.
        (folder / "pool.conf").write_text(
            "[www]\npm=static\npm.max_children=1\n")
        php = net + "-php"
        names.append(php)
        docker("run", "-d", "--name", php, "--network", net, "--network-alias", "lg-php",
               "-p", "127.0.0.1::80",
               "-v", f"{site}:/var/www/html:ro", "-v", f"{files}:/srv/files:ro",
               "-v", f"{folder / 'pool.conf'}:/usr/local/etc/php-fpm.d/zz-test.conf:ro", PHP)
        docker("exec", php, "php", "-l", "/var/www/html/download.php")

        # Test the actual host-network nginx configuration in PHP's network namespace.
        direct = net + "-direct"
        names.append(direct)
        docker("run", "-d", "--name", direct, "--network", f"container:{php}",
               "-v", f"{ROOT / 'docker/nginx/nginx.conf'}:/etc/nginx/nginx.conf:ro",
               "-v", f"{ROOT / 'docker/nginx/download-locations.conf'}:/etc/nginx/download-locations.conf:ro",
               "-v", f"{files}:/srv/files:ro", NGINX)
        docker("exec", direct, "nginx", "-t")
        direct_port = port_of(php, 80)
        wait(direct_port)

        bulk = net + "-bulk"
        names.append(bulk)
        docker("run", "-d", "--name", bulk, "--network", net, "--network-alias", "downloads",
               "-p", "127.0.0.1::80",
               "-v", f"{ROOT / 'docker/nginx/downloads.conf'}:/etc/nginx/nginx.conf:ro",
               "-v", f"{ROOT / 'docker/nginx/download-locations.conf'}:/etc/nginx/download-locations.conf:ro",
               "-v", f"{files}:/srv/files:ro", NGINX)
        docker("exec", bulk, "nginx", "-t")
        bulk_port = port_of(bulk, 80)
        wait(bulk_port)

        # Use the checked-in Caddy routing; substitute local cleartext only for TLS.
        caddyfile = (ROOT / "docker/caddy/Caddyfile").read_text()
        (folder / "Caddyfile").write_text(
            caddyfile.replace("{$LG_DOMAIN}", "http://:8080").replace("tls {$LG_EMAIL}", ""))
        front = net + "-front"
        names.append(front)
        docker("run", "-d", "--name", front, "--network", net, "-p", "127.0.0.1::8080",
               "-v", f"{folder / 'Caddyfile'}:/etc/caddy/Caddyfile:ro",
               "-v", f"{site}:/var/www/html:ro", CADDY)
        docker("exec", front, "caddy", "validate", "--config", "/etc/caddy/Caddyfile")
        front_port = port_of(front, 8080)
        wait(front_port)

        for port in (direct_port, front_port):
            # UI's HEAD then GET: the sole 10GB allowance must survive HEAD.
            headers, _ = closed(port, "/download.php?file=10GB", 200, "HEAD")
            cookie = headers["Set-Cookie"].split(";", 1)[0]
            conn, response = request(port, "/download.php?file=10GB", headers={"Cookie": cookie})
            check(response.status == 200 and response.getheader("Content-Length") == str(10*1024**3),
                  "10GB handoff failed")
            check(response.getheader("X-Download-Remaining") == "0", "GET quota not consumed")
            held.append((conn, response))
            # Same session and the only PHP worker must both be available.
            check(closed(front_port, "/probe.php", 200, headers={"Cookie": cookie})[1] == b"ready",
                  "download retained the session lock or PHP worker")
            error_headers, _ = closed(port, "/download.php?file=10GB", 429, "HEAD",
                                      headers={"Cookie": cookie})
            check("Download limit reached" in error_headers["X-Download-Error"],
                  "HEAD quota error is unavailable to the UI")
            conn.close()
            held.clear()
            time.sleep(.2)

            for path in ("/files/10GB.bin", "/_speedtest/10GB.bin",
                         "/%5fspeedtest/10GB.bin", "/_speedtest/../files/10GB.bin"):
                conn, response = request(port, path)
                check(response.status in (400, 403, 404), f"public file route exposed: {path}")
                response.read()
                conn.close()
            for query in ("", "?file=../../etc/passwd", "?file[]=10GB", "?file=%31%30GBx"):
                closed(port, "/download.php" + query, 400)
            closed(port, "/download.php?file=100MB", 405, "POST")

            # Spoofed headers, file sizes and new cookies share the same peer budget.
            for size, spoof in (("10GB", "192.0.2.1"), ("1GB", "192.0.2.2")):
                conn, response = request(port, "/download.php?file=" + size,
                                         headers={"X-LG-Client": spoof, "X-Forwarded-For": spoof})
                check(response.status == 200, "allowed concurrent download failed")
                held.append((conn, response))
            error_headers, _ = closed(port, "/download.php?file=100MB", 429, "HEAD",
                                     headers={"X-LG-Client": "192.0.2.3", "X-Forwarded-For": "192.0.2.3"})
            check("Too many active downloads" in error_headers["X-Download-Error"],
                  "HEAD concurrency error is unavailable to the UI")
            check(closed(front_port, "/probe.php", 200)[1] == b"ready", "PHP pool starved")
            for conn, _ in held:
                conn.close()
            held.clear()
            time.sleep(.3)

            # A complete transfer, headers, and GET session quota.
            headers, _ = closed(port, "/download.php?file=100MB", 200, "HEAD")
            cookie = headers["Set-Cookie"].split(";", 1)[0]
            for remaining in (2, 1, 0):
                conn, response = request(port, "/download.php?file=100MB", headers={"Cookie": cookie})
                check(response.status == 200, "legitimate GET failed")
                check(response.getheader("X-Download-Remaining") == str(remaining), "quota changed")
                check(response.getheader("Content-Disposition") == 'attachment; filename="100MB.bin"',
                      "attachment header changed")
                count = 0
                while True:
                    chunk = response.read(1024*1024)
                    if not chunk:
                        break
                    count += len(chunk)
                check(count == 100*1024**2, "truncated legitimate download")
                conn.close()
            closed(port, "/download.php?file=100MB", 429, headers={"Cookie": cookie})
            print(f"PASS endpoint {port}: HEAD/GET, quotas, paths, methods, spoofing, peer cap, PHP responsiveness")

        # The private bulk service is addressed directly only inside this test.
        # Simulate 16 different socket peers as asserted by the trusted Caddy hop.
        for i in range(16):
            conn, response = request(bulk_port, "/download.php?file=10GB",
                                     headers={"X-LG-Client": f"192.0.2.{i+1}"})
            check(response.status == 200, f"global admission rejected slot {i+1}")
            held.append((conn, response))
        closed(bulk_port, "/download.php?file=1GB", 429, headers={"X-LG-Client": "192.0.2.99"})
        check(closed(front_port, "/probe.php", 200)[1] == b"ready", "global cap starved PHP")
        for conn, _ in held:
            conn.close()
        held.clear()
        time.sleep(.3)
        closed(front_port, "/download.php?file=1GB", 200, "HEAD")

        # Caddy aliases reaching PHP without the trusted FastCGI param fail closed.
        for path in ("/download.php/extra?file=10GB", "/%64ownload.php?file=10GB",
                     "//download.php?file=10GB"):
            conn, response = request(front_port, path, "HEAD",
                                     {"LG-Download-Offload": "1", "LG_DOWNLOAD_OFFLOAD": "1"})
            check(response.status in (200, 400, 404, 503), f"unexpected alias response {response.status}")
            conn.close()
        # Prove unsupported offload integration cannot be enabled with an HTTP header.
        result = docker("exec", php, "sh", "-c",
               'REQUEST_METHOD=GET HTTP_LG_DOWNLOAD_OFFLOAD=1 php /var/www/html/download.php')
        check("not configured" in result, "HTTP header enabled the offload integration")
        result = docker("exec", php, "sh", "-c",
                        'REQUEST_METHOD=GET php /var/www/html/download.php')
        check("not configured" in result, "missing offload integration did not fail closed")
        (files / "1GB.bin").unlink()
        closed(front_port, "/download.php?file=1GB", 404)
        print("PASS global cap, disconnect recovery, missing file, fail-closed integration")

        # A stalled receiver must release its web-server slots after send_timeout.
        for _ in range(2):
            conn, response = request(direct_port, "/download.php?file=10GB")
            check(response.status == 200, "idle timeout setup failed")
            held.append((conn, response))
        closed(direct_port, "/download.php?file=100MB", 429, "HEAD")
        deadline = time.monotonic() + 45
        while True:
            time.sleep(.5)
            conn, response = request(direct_port, "/download.php?file=100MB", "HEAD")
            status = response.status
            conn.close()
            if status == 200:
                break
            check(status == 429 and time.monotonic() < deadline,
                  "stalled downloads did not release slots after the 30s idle timeout")
        for conn, _ in held:
            conn.close()
        held.clear()
        print("PASS idle send timeout reclaims stalled transfer slots")
    finally:
        for conn, _ in held:
            conn.close()
        for name in reversed(names):
            subprocess.run(["docker", "rm", "-f", name], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        subprocess.run(["docker", "network", "rm", net], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        work.cleanup()


if __name__ == "__main__":
    main()
