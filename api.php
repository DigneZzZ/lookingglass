<?php declare(strict_types=1);
/**
 * Looking Glass - AJAX API
 *
 * Handles AJAX requests for looking glass commands with streaming output.
 *
 * @copyright 2024-2026 DigneZzZ (gig.ovh)
 * @license Mozilla Public License 2.0
 * @version 2.0.0
 * @link https://github.com/DigneZzZ/lookingglass
 */

require __DIR__.'/LookingGlass.php';

if (file_exists(__DIR__.'/config.php')) {
    require __DIR__.'/config.php';
} else {
    die('config.php is not found');
}

use Hybula\LookingGlass;

LookingGlass::validateConfig();
LookingGlass::startSession();

header('Content-Type: text/plain; charset=utf-8');
header('X-Accel-Buffering: no');
header('Cache-Control: no-cache');

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo 'Invalid JSON';
    exit;
}

// CSRF check
if (!isset($input['csrfToken']) || !isset($_SESSION[LookingGlass::SESSION_CSRF]) || $input['csrfToken'] !== $_SESSION[LookingGlass::SESSION_CSRF]) {
    http_response_code(403);
    echo 'Invalid CSRF token';
    exit;
}

$method = $input['method'] ?? '';
$target = $input['target'] ?? '';

if (empty($method) || empty($target)) {
    http_response_code(400);
    echo 'Missing method or target';
    exit;
}

if (!in_array($method, LG_METHODS)) {
    http_response_code(400);
    echo 'Unsupported method';
    exit;
}

// Validate target
$validatedTarget = $target;

if (in_array($method, ['ping', 'mtr', 'traceroute'])) {
    if (!LookingGlass::isValidIpv4($target)) {
        $validatedTarget = LookingGlass::isValidHost($target, LookingGlass::IPV4);
        if (!$validatedTarget) {
            http_response_code(400);
            echo 'Invalid IPv4 address or hostname';
            exit;
        }
    }
}

if (in_array($method, ['ping6', 'mtr6', 'traceroute6'])) {
    if (!LookingGlass::isValidIpv6($target)) {
        $validatedTarget = LookingGlass::isValidHost($target, LookingGlass::IPV6);
        if (!$validatedTarget) {
            http_response_code(400);
            echo 'Invalid IPv6 address or hostname';
            exit;
        }
    }
}

// Store for session (optional, for compatibility)
$_SESSION[LookingGlass::SESSION_TARGET_HOST] = $validatedTarget;
$_SESSION[LookingGlass::SESSION_TARGET_METHOD] = $method;

// Execute command
switch ($method) {
    case LookingGlass::METHOD_PING:
        LookingGlass::ping($validatedTarget);
        break;
    case LookingGlass::METHOD_PING6:
        LookingGlass::ping6($validatedTarget);
        break;
    case LookingGlass::METHOD_MTR:
        LookingGlass::mtr($validatedTarget);
        break;
    case LookingGlass::METHOD_MTR6:
        LookingGlass::mtr6($validatedTarget);
        break;
    case LookingGlass::METHOD_TRACEROUTE:
        LookingGlass::traceroute($validatedTarget);
        break;
    case LookingGlass::METHOD_TRACEROUTE6:
        LookingGlass::traceroute6($validatedTarget);
        break;
}
