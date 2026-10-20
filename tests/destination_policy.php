<?php
declare(strict_types=1);

// Run with: php tests/destination_policy.php
// DNS and diagnostic processes are stubbed; no probes or DNS requests are sent.
namespace Hybula {
    function dns_get_record($host, $type) {
        $GLOBALS['dnsCalls'][] = [$host, $type];
        $key = $type === DNS_A ? 'ip' : 'ipv6';
        $public = $type === DNS_A ? '8.8.8.8' : '2606:4700:4700::1111';
        $restricted = $type === DNS_A ? '127.0.0.1' : '::1';
        switch ($host) {
            case 'public.example': return [[$key => $public], [$key => $type === DNS_A ? '1.1.1.1' : '2001:4860:4860::8888']];
            case 'private.example': return [[$key => $type === DNS_A ? '10.0.0.1' : 'fd00::1']];
            case 'loopback.example': return [[$key => $restricted]];
            case 'linklocal.example': return [[$key => $type === DNS_A ? '169.254.169.254' : 'fe80::1']];
            case 'reserved.example': return [[$key => $type === DNS_A ? '240.0.0.1' : '::']];
            case 'mixed.example': return [[$key => $public], [$key => $restricted]];
            case 'mixed-reversed.example': return [[$key => $restricted], [$key => $public]];
            case 'mapped.example': return [[$key => $type === DNS_A ? '127.0.0.1' : '::ffff:7f00:1']];
            case 'alias.example': return [['type' => 'CNAME', 'target' => 'public.example'], [$key => $public]];
            case 'cname-only.example': return [['type' => 'CNAME', 'target' => 'private.example']];
            case 'wrong-family.example': return [[$type === DNS_A ? 'ipv6' : 'ip' => $public]];
            case 'malformed.example': return [[$key => 'not-an-address']];
            case 'failed.example': return false;
            case 'rebinding.example':
                return [[$key => count(array_filter($GLOBALS['dnsCalls'], static function ($call) use ($host) { return $call[0] === $host; })) === 1 ? $public : $restricted]];
            default: return [];
        }
    }

    function proc_open($command, $descriptors, &$pipes, $cwd = null) {
        $GLOBALS['commands'][] = $command;
        // Stop before running any utility. Tests inspect the exact final argv.
        return false;
    }
}

namespace {
    use Hybula\LookingGlass;

    $GLOBALS['dnsCalls'] = [];
    $GLOBALS['commands'] = [];

    // Each request runs in a fresh PHP process so real route exits are preserved.
    if (($argv[1] ?? '') === '--request') {
        [$script, $mode, $app, $route, $method, $target] = $argv;
        session_save_path($app.'/sessions');
        session_name('HYLOOKINGLASS');
        session_id('destination-policy');
        session_start();
        $_SESSION = ['CSRF' => 'test-token'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        register_shutdown_function(static function () {
            echo "\nRESULT ".json_encode([
                'status' => http_response_code() ?: 200,
                'session' => $_SESSION,
                'commands' => $GLOBALS['commands'],
                'dnsCalls' => $GLOBALS['dnsCalls'],
            ])."\n";
        });
        if ($route === 'api.php') {
            // Override only the CLI input stream consumed by the real API.
            class RequestInput {
                public $context;
                private $position = 0;
                public function stream_open($path, $mode, $options, &$openedPath) { return $path === 'php://input'; }
                public function stream_read($count) {
                    $data = substr($GLOBALS['requestBody'], $this->position, $count);
                    $this->position += strlen($data);
                    return $data;
                }
                public function stream_eof() { return $this->position >= strlen($GLOBALS['requestBody']); }
                public function stream_stat() { return []; }
            }
            $GLOBALS['requestBody'] = json_encode(['method' => $method, 'target' => $target, 'csrfToken' => 'test-token']);
            stream_wrapper_unregister('php');
            stream_wrapper_register('php', RequestInput::class);
        } elseif ($route === 'backend.php') {
            // Simulate state written before the fix, including an unpinned name.
            $_SESSION['target_host'] = $target;
            $_SESSION['target_method'] = $method;
            $_SESSION['call_backend'] = true;
        } else {
            $_POST = ['submitForm' => '1', 'backendMethod' => $method, 'targetHost' => $target, 'csrfToken' => 'test-token'];
        }
        session_write_close();
        require $app.'/'.$route;
        exit;
    }

    require dirname(__DIR__).'/LookingGlass.php';
    $checks = 0;
    function check($condition, $message) {
        global $checks;
        ++$checks;
        if (!$condition) { throw new \RuntimeException($message); }
    }
    function request($app, $route, $method, $target) {
        $process = proc_open([PHP_BINARY, __FILE__, '--request', $app, $route, $method, $target],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        check($status === 0 && $errors === '', 'Route failed: '.$route.' '.$errors.' '.$output);
        check(strpos($output, 'Warning:') === false && strpos($output, 'Fatal error:') === false, 'Route emitted a PHP error: '.$output);
        check(preg_match('/\nRESULT (.+)\n$/', $output, $match) === 1, 'Missing route result: '.$output);
        $result = json_decode($match[1], true);
        $result['output'] = $output;
        return $result;
    }

    foreach ([LookingGlass::IPV4 => '8.8.8.8', LookingGlass::IPV6 => '2606:4700:4700::1111'] as $family => $public) {
        check(LookingGlass::isValidHost('public.example', $family) === $public, 'Public DNS must return a pinned address');
        check(LookingGlass::isValidHost('https://public.example:443/path', $family) === $public, 'Preserve URL host extraction');
        check(LookingGlass::isValidHost('alias.example', $family) === $public, 'Accept an alias with a valid address answer');
        foreach (['private', 'loopback', 'linklocal', 'reserved', 'mixed', 'mixed-reversed', 'mapped', 'cname-only', 'wrong-family', 'malformed', 'failed', 'missing'] as $name) {
            check(LookingGlass::isValidHost($name.'.example', $family) === '', 'Reject '.$family.' '.$name);
        }
    }
    check(LookingGlass::isValidHost('public.example', 'invalid') === '', 'Reject unknown family');
    foreach (['::ffff:127.0.0.1', '::ffff:10.0.0.1', '::ffff:7f00:1', '0:0:0:0:0:ffff:a00:1', '::127.0.0.1', '::a00:1'] as $ip) {
        check(!LookingGlass::isValidIpv6($ip), 'Reject restricted embedded IPv4 destination: '.$ip);
    }

    foreach (['ping', 'mtr', 'traceroute', 'ping6', 'mtr6', 'traceroute6'] as $method) {
        $ipv6 = substr($method, -1) === '6';
        $public = $ipv6 ? '2606:4700:4700::1111' : '8.8.8.8';
        foreach (['private.example', 'mixed.example', '127.0.0.1', '::1', '::ffff:7f00:1'] as $target) {
            $GLOBALS['commands'] = [];
            LookingGlass::$method($target);
            check($GLOBALS['commands'] === [], $method.' must reject '.$target.' before proc_open');
        }
        foreach (['public.example', $public] as $target) {
            $GLOBALS['commands'] = [];
            LookingGlass::$method($target);
            check(count($GLOBALS['commands']) === 1 && end($GLOBALS['commands'][0]) === $public, $method.' must execute numeric destination');
        }
        $GLOBALS['dnsCalls'] = [];
        $GLOBALS['commands'] = [];
        $pinned = LookingGlass::isValidHost('rebinding.example', $ipv6 ? LookingGlass::IPV6 : LookingGlass::IPV4);
        LookingGlass::$method($pinned);
        check(count($GLOBALS['dnsCalls']) === 1 && end($GLOBALS['commands'][0]) === $public, 'No re-resolution after validation');
    }
    $GLOBALS['commands'] = [];
    LookingGlass::whois('public.example');
    check(end($GLOBALS['commands'][0]) === 'public.example', 'WHOIS retains domain semantics');

    $app = sys_get_temp_dir().'/lg-destination-policy-'.bin2hex(random_bytes(8));
    mkdir($app);
    mkdir($app.'/sessions');
    try {
        foreach (['LookingGlass.php', 'bootstrap.php', 'api.php', 'index.php', 'index.bootstrap.php', 'backend.php'] as $file) {
            copy(dirname(__DIR__).'/'.$file, $app.'/'.$file);
        }
        $config = file_get_contents(dirname(__DIR__).'/config.dist.php');
        $config = str_replace('LG_NETWORK_INFO_DYNAMIC = true', 'LG_NETWORK_INFO_DYNAMIC = false', $config);
        $config = str_replace('LookingGlass::METHOD_TRACEROUTE6,', 'LookingGlass::METHOD_TRACEROUTE6, LookingGlass::METHOD_WHOIS, LookingGlass::METHOD_BGP,', $config);
        file_put_contents($app.'/config.php', $config);
        foreach (['api.php', 'index.php', 'index.bootstrap.php', 'backend.php'] as $route) {
            foreach (['ping', 'mtr', 'traceroute', 'ping6', 'mtr6', 'traceroute6'] as $method) {
                $public = substr($method, -1) === '6' ? '2606:4700:4700::1111' : '8.8.8.8';
                foreach (['private.example', 'loopback.example', 'linklocal.example', 'reserved.example', 'mixed.example', 'mapped.example'] as $target) {
                    $result = request($app, $route, $method, $target);
                    check($result['commands'] === [], $route.' '.$method.' reached proc_open for '.$target);
                    if ($route === 'api.php') {
                        check($result['status'] === 400, 'API preserves invalid-target status: '.$method.' '.$target.' '.json_encode($result));
                    } elseif ($route !== 'backend.php') {
                        check(isset($result['session']['error_message']) && empty($result['session']['call_backend']), 'Form preserves error and clears backend flag');
                    }
                }
                foreach (['public.example', $public] as $target) {
                    $result = request($app, $route, $method, $target);
                    if ($route === 'index.php' || $route === 'index.bootstrap.php') {
                        check($result['session']['target_host'] === $public && $result['session']['call_backend'] === true, 'Form must store the pinned address');
                    } else {
                        check(count($result['commands']) === 1 && end($result['commands'][0]) === $public, 'Route must execute the pinned address');
                    }
                }
                $result = request($app, $route, $method, 'rebinding.example');
                check(count($result['dnsCalls']) === 1, 'Route must resolve only once');
                $actual = $result['commands'] ? end($result['commands'][0]) : $result['session']['target_host'];
                check($actual === $public, 'Route must retain the first approved destination');
            }
        }
        foreach (['whois' => 'public.example', 'bgp' => 'AS15169'] as $method => $target) {
            // The existing BGP implementation passes bool to ini_set, unsupported on PHP 7.4.
            if ($method === 'bgp' && PHP_VERSION_ID < 80000) {
                echo "SKIP: existing PHP 7.4 BGP ini_set TypeError; diagnostic coverage is unchanged\n";
                continue;
            }
            $result = request($app, 'api.php', $method, $target);
            check(count($result['commands']) === 1 && end($result['commands'][0]) === $target && $result['dnsCalls'] === [], 'Lookup behavior remains intact');
        }
    } finally {
        foreach (glob($app.'/sessions/*') as $file) { unlink($file); }
        rmdir($app.'/sessions');
        foreach (glob($app.'/*') as $file) { unlink($file); }
        rmdir($app);
    }
    echo 'PASS: '.$checks." destination-policy checks\n";
}
