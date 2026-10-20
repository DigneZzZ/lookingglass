#!/usr/bin/env python3
"""Exercise the real Caddy/PHP/download routes with disposable Docker fixtures.

An optional Caddyfile argument selects another configuration. Use
--expect-vulnerable only to reproduce the original disclosure in a baseline.
No operator environment file or Git metadata is copied into the fixture.
"""

import http.client
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import time
import uuid


ROOT = Path(__file__).resolve().parents[1]
MARKER = "PRIVATE_FIXTURE_DO_NOT_SERVE"
DOWNLOAD = b"SYNTHETIC_SPEEDTEST_BYTES\n"
CADDY_IMAGE = os.environ.get("CADDY_IMAGE", "caddy:2-alpine")
PHP_IMAGE = os.environ.get("PHP_IMAGE", "php:8.4-fpm-alpine")
NGINX_IMAGE = os.environ.get("NGINX_IMAGE", "nginx:mainline-alpine")


def docker(*args):
    return subprocess.check_output(["docker", *args], text=True, timeout=90).strip()


def main():
    arguments = [arg for arg in sys.argv[1:] if arg != "--expect-vulnerable"]
    config = Path(arguments[0]) if arguments else ROOT / "docker/caddy/Caddyfile"
    baseline = "--expect-vulnerable" in sys.argv[1:]
    name = "lg-private-" + uuid.uuid4().hex[:12]
    containers = []
    network_created = False
    checks = 0
    with tempfile.TemporaryDirectory(prefix="lg-caddy-private-", dir=os.environ.get("LG_TEST_TMP")) as directory:
        staging = Path(directory)
        webroot = staging / "webroot"
        webroot.mkdir()
        for source in ROOT.glob("*.php"):
            shutil.copyfile(source, webroot / source.name)
        shutil.copyfile(ROOT / "docker/php-fpm/src/config.php", webroot / "config.php")
        private_files = [
            ".env", ".env.local", ".env.backup", ".git/config", ".git/HEAD",
            "assets/.env", "assets/.git/config", "docker/caddy/Caddyfile",
            "docker/php-fpm/src/config.php.bak", "docker-compose.caddy.yml",
            "docker-compose.override.yml", "config.php.bak", "config.dist.php~",
            "tests/private.txt", "DOCKER/private.txt", "TESTS/private.txt",
            "CONFIG.PHP.bak", "DOCKER-COMPOSE.override.yml",
        ]
        for filename in private_files:
            destination = webroot / filename
            destination.parent.mkdir(parents=True, exist_ok=True)
            destination.write_text(MARKER, encoding="utf-8")
        (webroot / ".env.php").write_text("<?php echo '" + MARKER + "';", encoding="utf-8")
        for filename, content in {
            "custom.css": "body { color: blue; }",
            "assets/config.css": "body { color: green; }",
            "logo.svg": '<svg xmlns="http://www.w3.org/2000/svg"/>',
            "custom.php": "<?php echo 'PUBLIC_PHP_OK';",
        }.items():
            destination = webroot / filename
            destination.parent.mkdir(parents=True, exist_ok=True)
            destination.write_text(content, encoding="utf-8")
        files = staging / "files"
        files.mkdir()
        for size in ("100MB", "1GB", "10GB"):
            (files / (size + ".bin")).write_bytes(DOWNLOAD)
        shutil.copyfile(config, staging / "Caddyfile")
        try:
            docker("network", "create", name)
            network_created = True
            php = name + "-php"
            containers.append(php)
            docker(
                "run", "-d", "--name", php, "--network", name,
                "--network-alias", "lg-php", "-v", f"{webroot}:/var/www/html:ro",
                "-v", f"{files}:/srv/files:ro",
                "-e", "LG_NETWORK_INFO_DYNAMIC=false", "-e", "LOCATION=Test",
                "-e", "FACILITY=Test", "-e", "FACILITY_URL=https://example.invalid",
                "-e", "IPV4_ADDRESS=192.0.2.1", "-e", "IPV6_ADDRESS=2001:db8::1",
                "-e", "LOGO=Test", "-e", "LOGO_DARK=Test",
                "-e", "LOGO_URL=https://example.invalid", PHP_IMAGE,
            )
            downloads = name + "-downloads"
            containers.append(downloads)
            docker(
                "run", "-d", "--name", downloads, "--network", name,
                "--network-alias", "downloads",
                "-v", f"{ROOT / 'docker/nginx/downloads.conf'}:/etc/nginx/nginx.conf:ro",
                "-v", f"{ROOT / 'docker/nginx/download-locations.conf'}:/etc/nginx/download-locations.conf:ro",
                "-v", f"{files}:/srv/files:ro", NGINX_IMAGE,
            )
            docker("exec", downloads, "nginx", "-t")
            caddy = name + "-caddy"
            containers.append(caddy)
            docker(
                "run", "-d", "--name", caddy, "--network", name,
                "-p", "127.0.0.1::8080", "-v", f"{webroot}:/var/www/html:ro",
                "-v", f"{staging / 'Caddyfile'}:/etc/caddy/Caddyfile:ro",
                "-e", "LG_DOMAIN=http://localhost:8080",
                "-e", "LG_EMAIL=security-test@example.invalid", CADDY_IMAGE,
            )
            docker("exec", caddy, "caddy", "validate", "--config", "/etc/caddy/Caddyfile")
            port = int(docker("port", caddy, "8080/tcp").rsplit(":", 1)[1])

            def request(path, method="GET", headers=None):
                connection = http.client.HTTPConnection("127.0.0.1", port, timeout=5)
                try:
                    connection.request(method, path, headers={"Host": "localhost:8080", **(headers or {})})
                    response = connection.getresponse()
                    return response.status, response.read(), dict(response.getheaders())
                finally:
                    connection.close()

            for _ in range(100):
                try:
                    if request("/custom.php")[:2] == (200, b"PUBLIC_PHP_OK"):
                        break
                except (OSError, http.client.HTTPException):
                    pass
                time.sleep(0.1)
            else:
                raise AssertionError("Caddy/PHP fixture did not become ready")

            if baseline:
                status, body, _ = request("/.env")
                assert status == 200 and MARKER.encode() in body, "Baseline disclosure did not reproduce"
                print("PASS baseline: GET /.env publicly exposes the synthetic private marker")
                return

            paths = ["/" + filename for filename in private_files] + [
                "/.env.php", "/.env.php/extra", "/.env?download=1", "/.env/",
                "/%2eenv", "/%2Eenv.local", "/.%65nv", "/%2egit%2fconfig",
                "//.env", "/assets/../.env", "/assets/%2e%2e/.env",
                "/assets%2f.env", "//docker//caddy/Caddyfile",
                "/%64ocker/caddy/Caddyfile", "/config.php", "/config.php/extra",
                "/config.dist.php", "/config.php%2fextra", "/CONFIG.PHP",
                "/Docker/caddy/Caddyfile", "/.ENV", "/assets%2f.GIT%2fconfig",
            ]
            for path in paths:
                for method in ("GET", "HEAD"):
                    status, body, headers = request(path, method)
                    assert status == 404, f"{method} {path}: expected 404, got {status}"
                    assert MARKER.encode() not in body and MARKER not in str(headers), path
                    checks += 1
            for path, expected, text in [
                ("/", 200, "Looking Glass"),
                ("/index.php", 200, "Looking Glass"),
                ("/index.bootstrap.php", 200, "Looking Glass"),
                ("/api.php", 405, "Method not allowed"),
                ("/backend.php", 200, ""),
                ("/download.php?file=invalid", 400, "Invalid file"),
                ("/custom.php", 200, "PUBLIC_PHP_OK"),
                ("/custom.php/extra", 200, "PUBLIC_PHP_OK"),
                ("/custom.css", 200, "color: blue"),
                ("/assets/config.css", 200, "color: green"),
                ("/logo.svg", 200, "<svg"),
                ("/files/100MB.bin", 403, "Access denied"),
                ("/_speedtest/100MB.bin", 404, ""),
            ]:
                status, body, _ = request(path)
                assert status == expected and text.encode() in body, (
                    f"GET {path}: expected {expected} containing {text!r}, got {status}: {body[:200]}"
                )
                checks += 1
            status, body, headers = request("/download.php?file=10GB", "HEAD")
            assert status == 200 and body == b"" and headers["Content-Length"] == str(len(DOWNLOAD))
            cookie = headers["Set-Cookie"].split(";", 1)[0]
            status, body, headers = request("/download.php?file=10GB", headers={"Cookie": cookie})
            assert status == 200 and body == DOWNLOAD and headers["X-Download-Remaining"] == "0"
            assert headers["Content-Disposition"] == 'attachment; filename="10GB.bin"'
            status, _, _ = request("/download.php?file=10GB", "HEAD", {"Cookie": cookie})
            assert status == 429, "GET quota was not enforced after a quota-free HEAD"
            checks += 3
            print(f"PASS: {checks} private-path, public-route and Nginx download checks")
            print(docker("exec", caddy, "caddy", "version"))
            print(docker("exec", php, "php", "-r", "echo PHP_VERSION;"))
        except Exception:
            for container in containers:
                subprocess.run(["docker", "logs", "--tail", "20", container], check=False, timeout=20)
            raise
        finally:
            for container in reversed(containers):
                subprocess.run(["docker", "rm", "-f", container], capture_output=True, check=False, timeout=30)
            if network_created:
                subprocess.run(["docker", "network", "rm", name], capture_output=True, check=False, timeout=30)


if __name__ == "__main__":
    main()
