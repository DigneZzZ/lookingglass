<?php declare(strict_types=1);
use Hybula\LookingGlass;

// Define the HTML title;
const LG_TITLE = 'Looking Glass';

// Define a logo, this can be HTML too, see the other example for an image;
const LG_LOGO = '<h2 style="color: #000000;">Company Looking Glass</h2>';
const LG_LOGO_DARK = '<h2 style="color: #ffffff;">Company Looking Glass</h2>';

// Define the URL where the logo points to;
const LG_LOGO_URL = 'https://github.com/hybula/lookingglass/';

// Theme mode;
const LG_THEME = 'auto';

// Enable the latency check feature;
const LG_CHECK_LATENCY = true;

// Define a custom CSS file which can be used to style the LG, set false to disable, else point to the CSS file;
const LG_CSS_OVERRIDES = false;
// Define <head> content, this could be JS, CSS or meta tags;
const LG_CUSTOM_HEAD = false;

// Enable or disable blocks/parts of the LG, set false to hide a part;
const LG_BLOCK_NETWORK = true;
const LG_BLOCK_LOOKINGGLASS = true;
const LG_BLOCK_SPEEDTEST = true;
// This enables the custom block, which you can use to add something custom to the LG;
const LG_BLOCK_CUSTOM = false;

// Define a file here which will be used to display the custom block, can be PHP too which outputs HTML;
const LG_CUSTOM_HTML = __DIR__.'/custom.html.php';
// Define a file here which will be loaded on top of the index file, this can be used to do some post logic;
const LG_CUSTOM_PHP = __DIR__.'/custom.post.php';

// Define a file here which will be used to display the custom header. Will be at the top of file;
const LG_CUSTOM_HEADER_PHP = __DIR__.'/custom.header.php';
// Define a file here which will be used to display the custom footer. Will be at the bottom of file;
const LG_CUSTOM_FOOTER_PHP = __DIR__.'/custom.footer.php';

// Define the location of this network, usually a city and a country;
const LG_LOCATION = 'Amsterdam, Netherlands';
// Define a query location for the link to openstreetmap (eg: Amsterdam, Netherlands will be https://www.openstreetmap.org/search?query=Amsterdam, Netherlands)
const LG_MAPS_QUERY = 'Amsterdam, Netherlands';
// Define the facility where the network is located, usually a data center;
const LG_FACILITY = 'Nikhef';
// Define a direct link to more information about the facility, this should be a link to PeeringDB;
const LG_FACILITY_URL = 'https://www.peeringdb.com/fac/18';
// Define an IPv4 for testing;
const LG_IPV4 = '127.0.0.1';
// Define an IPv6 for testing;
const LG_IPV6 = '::1';

// ============================================================================
// Available Methods
// ============================================================================
// 
// Network diagnostic tools:
// - ping/ping6       - ICMP echo requests (IPv4/IPv6)
// - mtr/mtr6         - Combination of ping and traceroute with stats
// - traceroute/traceroute6 - Path to destination
// 
// Lookup tools (require whois package installed):
// - whois            - WHOIS lookup for IP, domain, or ASN
// - bgp              - BGP route lookup via bgp.tools
// 
// Note: Comment out methods you don't want to enable
// ============================================================================

const LG_METHODS = [
    LookingGlass::METHOD_PING,
    LookingGlass::METHOD_PING6,
    LookingGlass::METHOD_MTR,
    LookingGlass::METHOD_MTR6,
    LookingGlass::METHOD_TRACEROUTE,
    LookingGlass::METHOD_TRACEROUTE6,
];

// Define other looking glasses, this is useful if you have multiple networks and looking glasses;
const LG_LOCATIONS = [
    'Location A' => 'https://github.com/hybula/lookingglass/',
    'Location B' => 'https://github.com/hybula/lookingglass/',
    'Location C' => 'https://github.com/hybula/lookingglass/',
];

// Enable the iPerf info inside the speedtest block, set to false to disable;
const LG_SPEEDTEST_IPERF = true;
// Define the label of an incoming iPerf test;
const LG_SPEEDTEST_LABEL_INCOMING = 'iPerf3 Incoming';
// Define the command to use to test incoming speed using iPerf, preferable iPerf3;
const LG_SPEEDTEST_CMD_INCOMING = 'iperf3 -4 -c hostname -p 5201 -P 4';
// Define the label of an outgoing iPerf test;
const LG_SPEEDTEST_LABEL_OUTGOING = 'iPerf3 Outgoing';
// Define the command to use to test outgoing speed using iPerf, preferable iPerf3;
const LG_SPEEDTEST_CMD_OUTGOING = 'iperf3 -4 -c hostname -p 5201 -P 4 -R';
// Define speedtest files with URLs to the actual files;
const LG_SPEEDTEST_FILES = [
    '100M' => 'https://github.com/hybula/lookingglass/',
    '1G' => 'https://github.com/hybula/lookingglass/',
    '10G' => 'https://github.com/hybula/lookingglass/'
];

// Define if you require visitors to agree with the Terms of Use. The value should be a link to the terms, or false to disable it completely.
const LG_TERMS = false;

// ============================================================================
// Network Details Block (optional)
// ============================================================================
// 
// Show a block with your network information (ASN, prefixes, peering, etc.)
// 
// By default, network info is fetched DYNAMICALLY from bgpview.io API
// based on the server's IPv4 address. Data is cached for 24 hours.
// 
// Set LG_NETWORK_INFO_DYNAMIC = false to use static values below
// ============================================================================

// Enable/disable dynamic network info fetching (default: true)
const LG_NETWORK_INFO_DYNAMIC = true;

// --- Static fallback values (used when dynamic=false or API fails) ---
// Your Autonomous System Number (e.g., "AS12345" or just "12345")
const LG_ASN = false; // Set to your ASN to enable this block
// Your organization/company name
const LG_ASN_NAME = '';
// Your announced IPv4 prefixes (comma-separated, e.g., "1.2.3.0/24, 5.6.0.0/16")
const LG_PREFIXES_V4 = '';
// Your announced IPv6 prefixes (comma-separated)
const LG_PREFIXES_V6 = '';
// PeeringDB link (e.g., "https://www.peeringdb.com/asn/12345")
const LG_PEERINGDB = '';
// Internet Exchanges list (comma-separated, e.g., "AMS-IX, DE-CIX, LINX")
const LG_IX_LIST = '';
