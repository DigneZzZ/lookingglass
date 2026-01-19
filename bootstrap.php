<?php declare(strict_types=1);
/**
 * Looking Glass
 *
 * Bootstrap and configuration loader.
 *
 * @copyright 2024-2026 DigneZzZ (gig.ovh)
 * @license Mozilla Public License 2.0
 * @version 2.0.0
 * @link https://github.com/DigneZzZ/lookingglass
 */

use Hybula\LookingGlass;

if (!file_exists(__DIR__ . '/config.php')) {
    die('config.php is not found, but is required for application to work!');
}

if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('This script requires PHP 7.4 or higher. Current version: ' . PHP_VERSION);
}

require __DIR__ . '/LookingGlass.php';
require __DIR__ . '/config.php';

LookingGlass::validateConfig();
LookingGlass::startSession();

function exitErrorMessage(string $message): void
{
    unset($_SESSION[LookingGlass::SESSION_CALL_BACKEND]);
    $_SESSION[LookingGlass::SESSION_ERROR_MESSAGE] = $message;
    exitNormal();
}

function exitNormal(): void
{
    header("Refresh: 0");
    exit;
}

$templateData           = [
    'title'                    => LG_TITLE,
    'custom_css'               => LG_CSS_OVERRIDES,
    'custom_head'              => LG_CUSTOM_HEAD,
    'logo_url'                 => LG_LOGO_URL,
    'logo_data'                => LG_LOGO,
    'logo_data_dark'           => LG_LOGO_DARK,
    'logo_image'               => defined('LG_LOGO_IMAGE') ? LG_LOGO_IMAGE : false,
    'logo_image_dark'          => defined('LG_LOGO_IMAGE_DARK') ? LG_LOGO_IMAGE_DARK : false,
    //
    'block_network'            => LG_BLOCK_NETWORK,
    'block_lookingglass'       => LG_BLOCK_LOOKINGGLASS,
    'block_speedtest'          => LG_BLOCK_SPEEDTEST,
    'block_custom'             => LG_BLOCK_CUSTOM,
    'custom_html'              => '',
    //
    'locations'                => LG_LOCATIONS,
    'current_location'         => LG_LOCATION,
    'maps_query'               => LG_MAPS_QUERY,
    'facility'                 => LG_FACILITY,
    'facility_url'             => LG_FACILITY_URL,
    'ipv4'                     => LG_IPV4,
    'ipv6'                     => LG_IPV6,
    'methods'                  => LG_METHODS,
    'user_ip'                  => LookingGlass::detectIpAddress(),
    //
    'network_info'             => null, // Will be populated below if enabled
    //
    'speedtest_iperf'          => LG_SPEEDTEST_IPERF,
    'speedtest_incoming_label' => LG_SPEEDTEST_LABEL_INCOMING,
    'speedtest_incoming_cmd'   => LG_SPEEDTEST_CMD_INCOMING,
    'speedtest_outgoing_label' => LG_SPEEDTEST_LABEL_OUTGOING,
    'speedtest_outgoing_cmd'   => LG_SPEEDTEST_CMD_OUTGOING,
    'speedtest_files'          => LG_SPEEDTEST_FILES,
    //
    'tos'                      => LG_TERMS,
    'error_message'            => false,
];

// Network Connectivity: try dynamic fetch, fallback to static ENV
$networkInfo = null;

// Check if dynamic network info is enabled (LG_NETWORK_INFO_DYNAMIC=true or not set)
$dynamicEnabled = !defined('LG_NETWORK_INFO_DYNAMIC') || LG_NETWORK_INFO_DYNAMIC !== false;

if ($dynamicEnabled && !empty(LG_IPV4) && LG_IPV4 !== '127.0.0.1') {
    // Try to get dynamic network info from API (RIPE Stat + PeeringDB)
    $networkInfo = LookingGlass::getNetworkInfo(LG_IPV4);
}

// Fallback to static ENV if dynamic failed or disabled
if ($networkInfo === null && defined('LG_ASN') && LG_ASN) {
    $networkInfo = [
        'asn' => LG_ASN,
        'asn_name' => defined('LG_ASN_NAME') ? LG_ASN_NAME : '',
        'upstreams' => [], // Can't get dynamically, leave empty
        'peeringdb' => defined('LG_PEERINGDB') ? LG_PEERINGDB : 'https://www.peeringdb.com/asn/' . preg_replace('/[^0-9]/', '', LG_ASN),
        'ix_list' => defined('LG_IX_LIST') && LG_IX_LIST ? array_map('trim', explode(',', LG_IX_LIST)) : [],
    ];
}

$templateData['network_info'] = $networkInfo;
