<?php declare(strict_types=1);
/**
 * Looking Glass - Speedtest File Download
 *
 * Handles speedtest file downloads with rate limiting per session.
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

// Only a configured FastCGI offload endpoint may authorize a transfer.
// Never fall back to streaming a large file in an application worker.
if (($_SERVER['LG_DOWNLOAD_OFFLOAD'] ?? '') !== '1') {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Speedtest file offloading is not configured']);
    exit;
}

if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit;
}

LookingGlass::startSession();

// Configuration
const DOWNLOAD_LIMITS = [
    '100MB' => 3,  // 3 downloads per session
    '1GB'   => 2,  // 2 downloads per session
    '10GB'  => 1,  // 1 download per session
];

const SESSION_DOWNLOADS = 'speedtest_downloads';

// Get requested file
$file = $_GET['file'] ?? '';

// Validate file parameter
$allowedFiles = [
    '100MB' => '/srv/files/100MB.bin',
    '1GB'   => '/srv/files/1GB.bin',
    '10GB'  => '/srv/files/10GB.bin',
];

if (!is_string($file) || !isset($allowedFiles[$file])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid file requested']);
    exit;
}

$filePath = $allowedFiles[$file];

// Check if file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'File not found. Please wait for files to be generated.']);
    exit;
}

// Initialize download counter in session
if (!isset($_SESSION[SESSION_DOWNLOADS])) {
    $_SESSION[SESSION_DOWNLOADS] = [];
}

if (!isset($_SESSION[SESSION_DOWNLOADS][$file])) {
    $_SESSION[SESSION_DOWNLOADS][$file] = 0;
}

// Check download limit
$limit = DOWNLOAD_LIMITS[$file] ?? 2;
$currentCount = $_SESSION[SESSION_DOWNLOADS][$file];

if ($currentCount >= $limit) {
    http_response_code(429);
    header('Content-Type: application/json');
    header('X-Download-Error: Download limit reached for ' . $file . ' file');
    echo json_encode([
        'error' => "Download limit reached for {$file} file",
        'limit' => $limit,
        'used' => $currentCount,
        'message' => 'Please try again later or start a new session'
    ]);
    exit;
}

// The web interface checks HEAD before starting the actual GET.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_SESSION[SESSION_DOWNLOADS][$file]++;
}
$remaining = $limit - $_SESSION[SESSION_DOWNLOADS][$file];
session_write_close();

// Get file info
$fileSize = filesize($filePath);
$fileName = $file . '.bin';

// Send headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Download-Remaining: ' . $remaining);

// Nginx owns the response body and its cross-session concurrency budget.
header('X-Accel-Redirect: /_speedtest/' . $fileName);
exit;
