<?php

declare(strict_types=1);
/**
 * Looking Glass
 *
 * The LookingGlass class provides all functionality.
 *
 * @copyright 2024-2026 DigneZzZ (gig.ovh)
 * @license Mozilla Public License 2.0
 * @version 2.0.0
 * @link https://github.com/DigneZzZ/lookingglass
 */

namespace Hybula;

class LookingGlass
{
    public const IPV4 = 'ipv4';
    public const IPV6 = 'ipv6';

    public const SESSION_TARGET_HOST = 'target_host';
    public const SESSION_TARGET_METHOD = 'target_method';
    public const SESSION_TOS_CHECKED = 'tos_checked';
    public const SESSION_CALL_BACKEND = 'call_backend';
    public const SESSION_ERROR_MESSAGE = 'error_message';
    public const SESSION_CSRF = 'CSRF';

    public const METHOD_PING = 'ping';
    public const METHOD_PING6 = 'ping6';
    public const METHOD_MTR = 'mtr';
    public const METHOD_MTR6 = 'mtr6';
    public const METHOD_TRACEROUTE = 'traceroute';
    public const METHOD_TRACEROUTE6 = 'traceroute6';
    public const METHOD_WHOIS = 'whois';
    public const METHOD_BGP = 'bgp';

    private const MTR_COUNT = 10;

    /**
     * Validates the config.php file for required constants.
     *
     * @return void
     */
    public static function validateConfig(): void
    {
        //@formatter:off
        if (!defined('LG_TITLE')) {
            die('LG_TITLE not found in config.php');
        }
        if (!defined('LG_LOGO')) {
            die('LG_LOGO not found in config.php');
        }
        if (!defined('LG_LOGO_DARK')) {
            die('LG_LOGO_DARK not found in config.php');
        }
        if (!defined('LG_LOGO_URL')) {
            die('LG_LOGO_URL not found in config.php');
        }
        if (!defined('LG_CSS_OVERRIDES')) {
            die('LG_CSS_OVERRIDES not found in config.php');
        }
        if (!defined('LG_BLOCK_NETWORK')) {
            die('LG_BLOCK_NETWORK not found in config.php');
        }
        if (!defined('LG_BLOCK_LOOKINGGLASS')) {
            die('LG_BLOCK_LOOKINGGLASS not found in config.php');
        }
        if (!defined('LG_BLOCK_SPEEDTEST')) {
            die('LG_BLOCK_SPEEDTEST not found in config.php');
        }
        if (!defined('LG_BLOCK_CUSTOM')) {
            die('LG_BLOCK_CUSTOM not found in config.php');
        }
        if (!defined('LG_CUSTOM_HTML')) {
            die('LG_CUSTOM_HTML not found in config.php');
        }
        if (!defined('LG_CUSTOM_PHP')) {
            die('LG_CUSTOM_PHP not found in config.php');
        }
        if (!defined('LG_LOCATION')) {
            die('LG_LOCATION not found in config.php');
        }
        if (!defined('LG_MAPS_QUERY')) {
            die('LG_MAPS_QUERY not found in config.php');
        }
        if (!defined('LG_FACILITY')) {
            die('LG_FACILITY not found in config.php');
        }
        if (!defined('LG_FACILITY_URL')) {
            die('LG_FACILITY_URL not found in config.php');
        }
        if (!defined('LG_IPV4')) {
            die('LG_IPV4 not found in config.php');
        }
        if (!defined('LG_IPV6')) {
            die('LG_IPV6 not found in config.php');
        }
        if (!defined('LG_METHODS')) {
            die('LG_METHODS not found in config.php');
        }
        if (!defined('LG_LOCATIONS')) {
            die('LG_LOCATIONSnot found in config.php');
        }
        if (!defined('LG_SPEEDTEST_IPERF')) {
            die('LG_SPEEDTEST_IPERF not found in config.php');
        }
        if (!defined('LG_SPEEDTEST_LABEL_INCOMING')) {
            die('LG_SPEEDTEST_LABEL_INCOMING not found in config.php');
        }
        if (!defined('LG_SPEEDTEST_CMD_INCOMING')) {
            die('LG_SPEEDTEST_CMD_INCOMING not found in config.php');
        }
        if (!defined('LG_SPEEDTEST_LABEL_OUTGOING')) {
            die('LG_SPEEDTEST_LABEL_OUTGOING not found in config.php');
        }
        if (!defined('LG_SPEEDTEST_CMD_OUTGOING')) {
            die('LG_SPEEDTEST_CMD_OUTGOING not found in config.php');
        }
        if (!defined('LG_SPEEDTEST_FILES')) {
            die('LG_SPEEDTEST_FILES not found in config.php');
        }
        if (!defined('LG_TERMS')) {
            die('LG_TERMS not found in config.php');
        }
        if (!defined('LG_CHECK_LATENCY')) {
            die('LG_CHECK_LATENCY not found in config.php');
        }
        if (!defined('LG_THEME')) {
            die('LG_THEME not found in config.php');
        }
        //@formatter:on
    }

    /**
     * Starts a PHP session and sets security tokens.
     *
     * @return void
     */
    public static function startSession(): void
    {
        session_name('HYLOOKINGLASS');
        @session_start() or die('Could not start session!');
    }

    /**
     * Validates and checks an IPv4 address.
     *
     * @param  string  $ip  The IPv4 address to validate.
     * @return bool True or false depending on validation.
     */
    public static function isValidIpv4(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        return false;
    }

    /**
     * Validates and checks an IPv6 address.
     *
     * @param  string  $ip  The IPv6 address to validate.
     * @return bool True or false depending on validation.
     */
    public static function isValidIpv6(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        return false;
    }

    /**
     * Validates and checks a host address.
     * Differs from isValidIpvX because it also extracts the host.
     *
     * @param  string  $host  The host to validate.
     * @return string Actual hostname or empty if none found.
     */
    public static function isValidHost(string $host, string $type): string
    {
        $host = str_replace(['http://', 'https://', ';', ',', '\\'], '', $host);
        if (!substr_count($host, '.')) {
            return '';
        }

        if (filter_var('https://'.$host, FILTER_VALIDATE_URL)) {
            if ($host = parse_url('https://'.$host, PHP_URL_HOST)) {
                if ($type === self::IPV4 && isset(dns_get_record($host, DNS_A)[0]['ip'])) {
                    return $host;
                }
                if ($type === self::IPV6 && isset(dns_get_record($host, DNS_AAAA)[0]['ipv6'])) {
                    return $host;
                }

                return '';
            }
        }

        return '';
    }

    /**
     * Determine the IP address of the client.
     * Also supports clients behind a proxy, however we need to validate this as this header can be spoofed.
     * The REMOTE_ADDR header is secure because it's populated by the webserver (extracted from TCP packets).
     *
     * @return string The IP address of the client.
     */
    public static function detectIpAddress(): string
    {
        if (php_sapi_name() === 'cli') {
            return '127.0.0.1';
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Get network information (ASN, prefixes, etc.) for an IP address.
     * Uses bgpview.io API with file-based caching (24 hours).
     *
     * @param string $ip The IP address to lookup.
     * @return array|null Network information or null on failure.
     */
    public static function getNetworkInfo(string $ip): ?array
    {
        // Validate IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        // Cache file path
        $cacheDir = sys_get_temp_dir() . '/lookingglass_cache';
        $cacheFile = $cacheDir . '/network_' . md5($ip) . '.json';
        $cacheTime = 86400; // 24 hours

        // Check cache
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
            $cached = file_get_contents($cacheFile);
            if ($cached !== false) {
                $data = json_decode($cached, true);
                if ($data !== null) {
                    return $data;
                }
            }
        }

        // Fetch from bgpview.io API
        $result = self::fetchNetworkInfoFromAPI($ip);
        
        // Cache result
        if ($result !== null) {
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            @file_put_contents($cacheFile, json_encode($result));
        }

        return $result;
    }

    /**
     * Fetch network information from RIPE Stat and PeeringDB APIs.
     * Returns upstreams, peers, and IX - useful info for hosting customers.
     *
     * @param string $ip The IP address to lookup.
     * @return array|null Network information or null on failure.
     */
    private static function fetchNetworkInfoFromAPI(string $ip): ?array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'header' => "User-Agent: LookingGlass/2.0\r\nAccept: application/json"
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ]);

        // Step 1: Get ASN from RIPE Stat
        $networkInfoUrl = 'https://stat.ripe.net/data/network-info/data.json?resource=' . urlencode($ip);
        $networkResponse = @file_get_contents($networkInfoUrl, false, $context);
        
        if ($networkResponse === false) {
            return null;
        }

        $networkData = json_decode($networkResponse, true);
        if (!isset($networkData['data']['asns'][0])) {
            return null;
        }

        $asn = $networkData['data']['asns'][0];

        // Step 2: Get ASN holder name from RIPE Stat
        $asnOverviewUrl = 'https://stat.ripe.net/data/as-overview/data.json?resource=AS' . $asn;
        $asnResponse = @file_get_contents($asnOverviewUrl, false, $context);
        
        $asnName = '';
        
        if ($asnResponse !== false) {
            $asnData = json_decode($asnResponse, true);
            if (isset($asnData['data']['holder'])) {
                $asnName = $asnData['data']['holder'];
            }
        }

        // Step 3: Get Upstreams (transit providers) from RIPE Stat
        $upstreamsUrl = 'https://stat.ripe.net/data/asn-neighbours/data.json?resource=AS' . $asn;
        $upstreamsResponse = @file_get_contents($upstreamsUrl, false, $context);
        
        $upstreams = [];
        
        if ($upstreamsResponse !== false) {
            $upstreamsData = json_decode($upstreamsResponse, true);
            if (isset($upstreamsData['data']['neighbours'])) {
                foreach ($upstreamsData['data']['neighbours'] as $neighbour) {
                    $neighbourAsn = $neighbour['asn'] ?? null;
                    $type = $neighbour['type'] ?? '';
                    $power = $neighbour['power'] ?? 0;
                    
                    // 'left' = upstream/transit provider
                    if ($neighbourAsn && $type === 'left' && count($upstreams) < 6) {
                        $upstreams[] = [
                            'asn' => 'AS' . $neighbourAsn,
                            'power' => $power
                        ];
                    }
                }
                
                // Sort by power (connection strength) descending
                usort($upstreams, fn($a, $b) => $b['power'] <=> $a['power']);
                
                // Get names for top upstreams
                foreach ($upstreams as &$upstream) {
                    $upstream['name'] = self::getAsnName((int)str_replace('AS', '', $upstream['asn']), $context);
                    unset($upstream['power']);
                }
                unset($upstream);
            }
        }

        // Step 4: Get IX from PeeringDB
        $ixList = [];
        $peeringDbUrl = 'https://www.peeringdb.com/api/netixlan?asn=' . $asn;
        $peeringDbResponse = @file_get_contents($peeringDbUrl, false, $context);
        
        if ($peeringDbResponse !== false) {
            $peeringData = json_decode($peeringDbResponse, true);
            if (isset($peeringData['data']) && is_array($peeringData['data'])) {
                $ixNames = [];
                foreach ($peeringData['data'] as $netixlan) {
                    if (isset($netixlan['name']) && !in_array($netixlan['name'], $ixNames)) {
                        $ixNames[] = $netixlan['name'];
                        if (count($ixNames) >= 10) break;
                    }
                }
                $ixList = $ixNames;
            }
        }

        return [
            'asn' => 'AS' . $asn,
            'asn_name' => $asnName,
            'upstreams' => $upstreams,
            'ix_list' => $ixList,
            'bgp_tools' => 'https://bgp.tools/as/' . $asn,
            'bgp_he' => 'https://bgp.he.net/AS' . $asn,
            'fetched_at' => time()
        ];
    }

    /**
     * Get ASN holder name from RIPE Stat (cached in static array).
     *
     * @param int $asn The ASN number.
     * @param resource $context Stream context.
     * @return string ASN holder name or empty string.
     */
    private static function getAsnName(int $asn, $context): string
    {
        static $cache = [];
        
        if (isset($cache[$asn])) {
            return $cache[$asn];
        }
        
        $url = 'https://stat.ripe.net/data/as-overview/data.json?resource=AS' . $asn;
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['data']['holder'])) {
                $cache[$asn] = $data['data']['holder'];
                return $cache[$asn];
            }
        }
        
        $cache[$asn] = '';
        return '';
    }

    /**
     * Map of country names to ISO 3166-1 alpha-2 codes.
     */
    private static array $countryMap = [
        // Common countries
        'netherlands' => 'nl', 'holland' => 'nl', 'amsterdam' => 'nl',
        'germany' => 'de', 'deutschland' => 'de', 'frankfurt' => 'de', 'berlin' => 'de', 'munich' => 'de',
        'france' => 'fr', 'paris' => 'fr', 'marseille' => 'fr',
        'united kingdom' => 'gb', 'uk' => 'gb', 'england' => 'gb', 'london' => 'gb', 'britain' => 'gb',
        'united states' => 'us', 'usa' => 'us', 'america' => 'us', 'new york' => 'us', 'los angeles' => 'us', 'dallas' => 'us', 'miami' => 'us', 'chicago' => 'us', 'seattle' => 'us', 'ashburn' => 'us',
        'canada' => 'ca', 'toronto' => 'ca', 'montreal' => 'ca', 'vancouver' => 'ca',
        'russia' => 'ru', 'moscow' => 'ru', 'saint petersburg' => 'ru', 'novosibirsk' => 'ru',
        'ukraine' => 'ua', 'kyiv' => 'ua', 'kiev' => 'ua', 'kharkiv' => 'ua',
        'poland' => 'pl', 'warsaw' => 'pl', 'krakow' => 'pl',
        'estonia' => 'ee', 'tallinn' => 'ee',
        'latvia' => 'lv', 'riga' => 'lv',
        'lithuania' => 'lt', 'vilnius' => 'lt',
        'finland' => 'fi', 'helsinki' => 'fi',
        'sweden' => 'se', 'stockholm' => 'se', 'malmö' => 'se',
        'norway' => 'no', 'oslo' => 'no',
        'denmark' => 'dk', 'copenhagen' => 'dk',
        'switzerland' => 'ch', 'zurich' => 'ch', 'geneva' => 'ch',
        'austria' => 'at', 'vienna' => 'at',
        'belgium' => 'be', 'brussels' => 'be',
        'spain' => 'es', 'madrid' => 'es', 'barcelona' => 'es',
        'portugal' => 'pt', 'lisbon' => 'pt',
        'italy' => 'it', 'milan' => 'it', 'rome' => 'it',
        'czech republic' => 'cz', 'czechia' => 'cz', 'prague' => 'cz',
        'hungary' => 'hu', 'budapest' => 'hu',
        'romania' => 'ro', 'bucharest' => 'ro',
        'bulgaria' => 'bg', 'sofia' => 'bg',
        'greece' => 'gr', 'athens' => 'gr',
        'turkey' => 'tr', 'istanbul' => 'tr', 'ankara' => 'tr',
        'israel' => 'il', 'tel aviv' => 'il',
        'japan' => 'jp', 'tokyo' => 'jp', 'osaka' => 'jp',
        'south korea' => 'kr', 'korea' => 'kr', 'seoul' => 'kr',
        'china' => 'cn', 'beijing' => 'cn', 'shanghai' => 'cn', 'hong kong' => 'hk',
        'taiwan' => 'tw', 'taipei' => 'tw',
        'singapore' => 'sg',
        'india' => 'in', 'mumbai' => 'in', 'delhi' => 'in', 'bangalore' => 'in',
        'australia' => 'au', 'sydney' => 'au', 'melbourne' => 'au',
        'new zealand' => 'nz', 'auckland' => 'nz',
        'brazil' => 'br', 'sao paulo' => 'br', 'são paulo' => 'br',
        'argentina' => 'ar', 'buenos aires' => 'ar',
        'mexico' => 'mx', 'mexico city' => 'mx',
        'south africa' => 'za', 'johannesburg' => 'za', 'cape town' => 'za',
        'uae' => 'ae', 'dubai' => 'ae', 'united arab emirates' => 'ae',
        'ireland' => 'ie', 'dublin' => 'ie',
        'luxembourg' => 'lu',
        'iceland' => 'is', 'reykjavik' => 'is',
        'serbia' => 'rs', 'belgrade' => 'rs',
        'croatia' => 'hr', 'zagreb' => 'hr',
        'slovenia' => 'si', 'ljubljana' => 'si',
        'slovakia' => 'sk', 'bratislava' => 'sk',
        'moldova' => 'md', 'chisinau' => 'md',
        'belarus' => 'by', 'minsk' => 'by',
        'kazakhstan' => 'kz', 'almaty' => 'kz', 'astana' => 'kz', 'nur-sultan' => 'kz',
        'georgia' => 'ge', 'tbilisi' => 'ge',
        'armenia' => 'am', 'yerevan' => 'am',
        'azerbaijan' => 'az', 'baku' => 'az',
        'vietnam' => 'vn', 'hanoi' => 'vn', 'ho chi minh' => 'vn',
        'thailand' => 'th', 'bangkok' => 'th',
        'malaysia' => 'my', 'kuala lumpur' => 'my',
        'indonesia' => 'id', 'jakarta' => 'id',
        'philippines' => 'ph', 'manila' => 'ph',
    ];

    /**
     * Get country code from location string.
     * Parses location like "Amsterdam, Netherlands" or "Tallinn, Estonia".
     *
     * @param string $location Location string.
     * @return string|null ISO 3166-1 alpha-2 country code (lowercase) or null.
     */
    public static function getCountryCode(string $location): ?string
    {
        // Remove emoji flags if present (they start with regional indicator symbols)
        $location = preg_replace('/[\x{1F1E0}-\x{1F1FF}]+/u', '', $location);
        $location = trim($location);
        
        // Normalize
        $normalized = strtolower(trim($location));
        
        // Check full string
        if (isset(self::$countryMap[$normalized])) {
            return self::$countryMap[$normalized];
        }
        
        // Split by comma and check each part
        $parts = array_map('trim', explode(',', $normalized));
        foreach (array_reverse($parts) as $part) {
            $part = trim($part);
            if (isset(self::$countryMap[$part])) {
                return self::$countryMap[$part];
            }
        }
        
        // Check if any part contains a known location
        foreach ($parts as $part) {
            foreach (self::$countryMap as $key => $code) {
                if (strpos($part, $key) !== false) {
                    return $code;
                }
            }
        }
        
        return null;
    }

    /**
     * Get country flag SVG URL from flagcdn.com.
     *
     * @param string $countryCode ISO 3166-1 alpha-2 country code.
     * @param int $width Width of the flag image.
     * @return string URL to flag SVG.
     */
    public static function getFlagUrl(string $countryCode, int $width = 24): string
    {
        $code = strtolower($countryCode);
        return "https://flagcdn.com/w{$width}/{$code}.png";
    }

    /**
     * Get flag HTML img tag for a location.
     *
     * @param string $location Location string like "Amsterdam, Netherlands".
     * @param int $width Width of the flag.
     * @param string $class CSS class for the img tag.
     * @return string HTML img tag or empty string if country not found.
     */
    public static function getFlagHtml(string $location, int $width = 20, string $class = 'inline-block'): string
    {
        $code = self::getCountryCode($location);
        if ($code === null) {
            return '';
        }
        
        $url = self::getFlagUrl($code, $width);
        $alt = strtoupper($code);
        return sprintf(
            '<img src="%s" alt="%s" class="%s" style="width:%dpx;height:auto;vertical-align:middle;border-radius:2px;" loading="lazy">',
            htmlspecialchars($url),
            htmlspecialchars($alt),
            htmlspecialchars($class),
            $width
        );
    }

    /**
     * Clean location string by removing emoji flags.
     *
     * @param string $location Location with possible emoji flags.
     * @return string Clean location string.
     */
    public static function cleanLocation(string $location): string
    {
        // Remove emoji flags (regional indicator symbols U+1F1E6 to U+1F1FF)
        $clean = preg_replace('/[\x{1F1E0}-\x{1F1FF}]+/u', '', $location);
        return trim($clean);
    }

    /**
     * Executes a ping command.
     *
     * @param  string  $host  The target host.
     * @param  int  $count  Number of requests.
     * @return bool True on success.
     */
    public static function ping(string $host, int $count = 4): bool
    {
        return self::procExecute(['ping', '-4', '-c', $count, '-w15'], $host);
    }

    /**
     * Executes a ping6 command.
     *
     * @param  string  $host  The target host.
     * @param  int  $count  Number of requests.
     * @return bool True on success.
     */
    public static function ping6(string $host, int $count = 4): bool
    {
        return self::procExecute(['ping', '-6', '-c', $count, '-w15'], $host);
    }

    /**
     * Executes a mtr command.
     *
     * @param  string  $host  The target host.
     * @return bool True on success.
     */
    public static function mtr(string $host): bool
    {
        return self::procExecute(['mtr', '--raw', '-n', '-4', '-c', self::MTR_COUNT], $host);
    }

    /**
     * Executes a mtr6 command.
     *
     * @param  string  $host  The target host.
     * @return bool True on success.
     */
    public static function mtr6(string $host): bool
    {
        return self::procExecute(['mtr', '--raw', '-n', '-6', '-c', self::MTR_COUNT], $host);
    }

    /**
     * Executes a traceroute command.
     *
     * @param  string  $host  The target host.
     * @param  int  $failCount  Number of failed hops.
     * @return bool True on success.
     */
    public static function traceroute(string $host, int $failCount = 4): bool
    {
        return self::procExecute(['traceroute', '-4', '-w2'], $host, $failCount);
    }

    /**
     * Executes a traceroute6 command.
     *
     * @param  string  $host  The target host.
     * @param  int  $failCount  Number of failed hops.
     * @return bool True on success.
     */
    public static function traceroute6(string $host, int $failCount = 4): bool
    {
        return self::procExecute(['traceroute', '-6', '-w2'], $host, $failCount);
    }

    /**
     * Executes a whois command.
     * Performs WHOIS lookup for IP addresses, domains, or ASN.
     *
     * @param  string  $target  The target (IP, domain, or ASN).
     * @return bool True on success.
     */
    public static function whois(string $target): bool
    {
        return self::procExecute(['whois'], $target);
    }

    /**
     * Performs BGP route lookup via external API (bgp.tools).
     * Shows BGP routing information including AS path, origin, and prefixes.
     *
     * @param  string  $target  The target IP or prefix.
     * @return bool True on success.
     */
    public static function bgp(string $target): bool
    {
        // Output buffer settings
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', false);
        while (@ob_end_flush()) {}
        @ob_implicit_flush(true);

        echo "BGP Route Lookup for: {$target}\n";
        echo str_repeat("-", 60) . "\n\n";

        // Validate input - IP, prefix, or ASN
        $isASN = preg_match('/^AS?\d+$/i', $target);
        $isIP = filter_var($target, FILTER_VALIDATE_IP);
        $isPrefix = preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/', $target) ||
                    preg_match('/^[0-9a-fA-F:]+\/\d{1,3}$/', $target);

        if (!$isASN && !$isIP && !$isPrefix) {
            echo "Error: Invalid target. Please provide an IP address, prefix (x.x.x.x/xx), or ASN (ASxxxxx).\n";
            return false;
        }

        // Use bgp.tools whois-style query
        $queryTarget = $target;
        if ($isASN) {
            $queryTarget = strtoupper($target);
            if (substr($queryTarget, 0, 2) !== 'AS') {
                $queryTarget = 'AS' . $queryTarget;
            }
        }

        echo "Querying BGP information...\n\n";

        // Execute whois query to bgp.tools
        $spec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $process = proc_open(['whois', '-h', 'bgp.tools', $queryTarget], $spec, $pipes, null);

        if (!is_resource($process)) {
            echo "Error: Could not execute BGP lookup.\n";
            return false;
        }

        // Close stdin
        fclose($pipes[0]);

        // Read output
        stream_set_blocking($pipes[1], false);
        $buffer = '';
        $lastOutput = time();

        while (!feof($pipes[1])) {
            $line = fgets($pipes[1]);
            if ($line !== false && $line !== '') {
                echo $line;
                @ob_flush();
                flush();
                $lastOutput = time();
            }

            // Timeout after 30 seconds of no output
            if ((time() - $lastOutput) > 30) {
                break;
            }

            usleep(10000); // 10ms delay
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return true;
    }

    /**
     * Executes a command and opens pipe for input/output.
     * Directly taken from telephone/LookingGlass (MIT License)
     *
     * @param  array  $cmd  The command to execute.
     * @param  string  $host  The host that is used as param.
     * @param  int  $failCount  Number of consecutive failed hops.
     * @return boolean True on success.
     * @link https://github.com/telephone/LookingGlass/blob/master/LookingGlass/LookingGlass.php#L172
     * @license https://github.com/telephone/LookingGlass/blob/master/LICENCE.txt
     */
    private static function procExecute(array $cmd, string $host, int $failCount = 2): bool
    {
        // define output pipes
        $spec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        // sanitize + remove single quotes
        $cmd[] = str_replace('\'', '', filter_var($host, FILTER_SANITIZE_URL));

        // execute command
        $process = proc_open($cmd, $spec, $pipes, null);

        // check pipe exists
        if (!is_resource($process)) {
            return false;
        }

        // check for mtr/traceroute
        if ($cmd[0] == 'mtr' || $cmd[0] == 'mtr6') {
            $type = 'mtr';
            $parser = new Parser();
        } elseif ($cmd[0] == 'traceroute' || $cmd[0] == 'traceroute6' ) {
            $type = 'traceroute';
        } else {
            $type = '';
        }

        $fail = 0;
        $match = 0;
        $traceCount = 0;
        $lastFail = 'start';
        // iterate stdout
        while (($str = fgets($pipes[1], 4096)) != null) {
            // check for output buffer
            if (ob_get_level() == 0) {
                ob_start();
            }

            // fix RDNS XSS (outputs non-breakble space correctly)
            $str = htmlspecialchars(trim($str));

            // correct output for mtr
            if ($type === 'mtr') {
                // correct output for mtr
                $parser->update($str);
                echo '@@@'.PHP_EOL.$parser->__toString().PHP_EOL.str_pad('', 4096).PHP_EOL;

                // flush output buffering
                @ob_flush();
                flush();
                continue;
            } // correct output for traceroute
            elseif ($type === 'traceroute') {
                if ($match < 10 && preg_match('/^[0-9] /', $str, $string)) {
                    $str = preg_replace('/^[0-9] /', '&nbsp;'.$string[0], $str);
                    $match++;
                }
                // check for consecutive failed hops
                if (strpos($str, '* * *') !== false) {
                    $fail++;
                    if ($lastFail !== 'start'
                        && ($traceCount - 1) === $lastFail
                        && $fail >= $failCount
                    ) {
                        echo str_pad($str.'<br />-- Traceroute timed out --<br />', 4096, ' ', STR_PAD_RIGHT);
                        break;
                    }
                    $lastFail = $traceCount;
                }
                $traceCount++;
            }

            // pad string for live output
            echo str_pad($str.'<br />', 4096, ' ', STR_PAD_RIGHT);

            // flush output buffering
            @ob_flush();
            flush();
        }

        // iterate stderr
        while (($err = fgets($pipes[2], 4096)) != null) {
            // check for IPv6 hostname passed to IPv4 command, and vice versa
            if (strpos($err, 'Name or service not known') !== false || strpos($err, 'unknown host') !== false) {
                echo 'Unauthorized request';
                break;
            }
        }

        $status = proc_get_status($process);
        if ($status['running']) {
            // close pipes that are still open
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            if ($status['pid']) {
                // use ps to get all the children of this process
                $psOutput = shell_exec('ps -o pid= --no-heading --ppid ' . (int)$status['pid']);
                if ($psOutput !== null) {
                    // Split the output into lines and filter numeric PIDs
                    $pids = preg_split('/\s+/', trim($psOutput));

                    foreach ($pids as $pid) {
                        if (is_numeric($pid)) {
                            posix_kill((int)$pid, 9);
                        }
                    }
                }
            }
            proc_close($process);
        }
        return true;
    }

    public static function getLatency(): float
    {
        $getLatency = self::getLatencyFromSs(self::detectIpAddress());
        if (isset($getLatency[0])) {
            return round((float)$getLatency[0]['latency']);
        } else {
            return 0.00;
        }
    }

    /**
     * This uses the command 'ss' in order to find out latency.
     * A clever way coded by @ayyylias, so please keep credits and do not just steal.
     *
     * @param  string  $ip  The command to execute.
     * @return array  Returns an array with results.
     */
    private static function getLatencyFromSs(string $ip): array
    {
        $ssPath = exec('which ss 2>/dev/null');
        if (empty($ssPath)) {
            // RHEL based systems;
            $ssPath = '/usr/sbin/ss';
        }

        if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipSs = '['.$ip.']';
        } else {
            $ipSs = $ip;
        }

        $lines = shell_exec($ssPath.' -Hintp state established dst '.$ipSs);
        $ss = [];
        $i = 0;
        $j = 0;
        foreach (explode(PHP_EOL, $lines ?? '') as $line) {
            if ($i > 1) {
                $i = 0;
                $j++;
            }
            if ($line !== '') {
                @$ss[$j] .= $line;
                $i++;
            }
        }
        $output = [];
        foreach ($ss as $socket) {
            $socket = preg_replace('!\s+!', ' ', $socket);
            $explodedsocket = explode(' ', $socket);
            $temp = [];

            if (strpos($explodedsocket[3], ']') !== false && strpos($explodedsocket[3], '::ffff') === false) {
                // IPv6 address
                preg_match('/\[(.*?)\]/', $explodedsocket[2], $temp);
                $sock['local'] = $temp[1];
                preg_match('/\[(.*?)\]/', $explodedsocket[3], $temp);
                $sock['remote'] = $temp[1];
            } else {
                // IPv4 address
                preg_match('/(\d+\.\d+\.\d+\.\d+)(:\d+)?/', $explodedsocket[2], $temp);
                $sock['local'] = $temp[1];
                preg_match('/(\d+\.\d+\.\d+\.\d+)(:\d+)?/', $explodedsocket[3], $temp);
                $sock['remote'] = $temp[1];
            }

            preg_match('/segs_out:(\d+)/', $socket, $temp);
            $sock['segs_out'] = $temp[1];
            preg_match('/segs_in:(\d+)/', $socket, $temp);
            $sock['segs_in'] = $temp[1];
            preg_match_all('/rtt:(\d+\.\d+)\/(\d+\.\d+)/', $socket, $temp);
            $sock['latency'] = $temp[1][0];
            $sock['jitter'] = $temp[2][0];
            preg_match_all('/retrans:\d+\/(\d+)/', $socket, $temp);
            $sock['retransmissions'] = (isset($temp[1][0]) ? $temp[1][0] : 0);
            if ($sock['remote'] == $ip || $sock['local'] == $ip) {
                $output[] = $sock;
            }
        }
        return $output;
    }
}

class Hop
{
    /** @var int */
    public $idx;
    /** @var string */
    public $asn = '';
    /** @var float */
    public $avg = 0.0;
    /** @var int */
    public $loss = 0;
    /** @var float */
    public $stdev = 0.0;
    /** @var int */
    public $sent = 0;
    /** @var int */
    public $recieved = 0;
    /** @var float */
    public $last = 0.0;
    /** @var float */
    public $best = 0.0;
    /** @var float */
    public $worst = 0.0;

    /** @var string[] */
    public $ips = [];
    /** @var string[] */
    public $hosts = [];
    /** @var float[] */
    public $timings = [];

}

class RawHop
{
    /** @var string */
    public $dataType;
    /** @var int */
    public $idx;
    /** @var string */
    public $value;
}

class Parser
{
    /** @var Hop[] */
    protected $hopsCollection = [];
    /** @var int */
    private $hopCount = 0;
    /** @var int */
    private $outputWidth = 38;

    public function __construct()
    {
        putenv('RES_OPTIONS=retrans:1 retry:1 timeout:1 attempts:1');
    }

    public function __toString(): string
    {
        $str = '';
        foreach ($this->hopsCollection as $index => $hop) {
            $host = $hop->hosts[0] ?? $hop->ips[0] ?? '???';

            if (strlen($host) > $this->outputWidth) {
                $this->outputWidth = strlen($host);
            }

            $hop->recieved = count($hop->timings);
            if (count($hop->timings)) {
                $hop->last = $hop->timings[count($hop->timings) - 1];
                $hop->best = $hop->timings[0];
                $hop->worst = $hop->timings[0];
                $hop->avg = array_sum($hop->timings) / count($hop->timings);
            }

            if (count($hop->timings) > 1) {
                $hop->stdev = $this->stDev($hop->timings);
            }

            foreach ($hop->timings as $time) {
                if ($hop->best > $time) {
                    $hop->best = $time;
                }

                if ($hop->worst < $time) {
                    $hop->worst = $time;
                }
            }

            $hop->loss = $hop->sent ? (100 * ($hop->sent - $hop->recieved)) / $hop->sent : 100;

            $str = sprintf(
                "%s%2d.|-- %s%3d.0%%   %3d  %5.1f %5.1f %5.1f %5.1f %5.1f\n",
                $str,
                $index,
                str_pad($host, $this->outputWidth + 3, ' ', STR_PAD_RIGHT),
                $hop->loss,
                $hop->sent,
                $hop->last,
                $hop->avg,
                $hop->best,
                $hop->worst,
                $hop->stdev
            );
        }

        return sprintf("       Host%sLoss%%   Snt   Last   Avg  Best  Wrst StDev\n%s", str_pad('', $this->outputWidth + 7, ' ', STR_PAD_RIGHT), $str);
    }

    private function stDev(array $array): float
    {
        $sdSquare = function ($x, $mean) {
            return pow($x - $mean, 2);
        };

        // square root of sum of squares devided by N-1
        return sqrt(array_sum(array_map($sdSquare, $array, array_fill(0, count($array), (array_sum($array) / count($array))))) / (count($array) - 1));
    }

    public function update($rawMtrInput)
    {
        //Store each line of output in rawhop structure
        $things = explode(' ', $rawMtrInput);

        if (count($things) !== 3 && (count($things) !== 4 && $things[0] === 'p')) {
            return;
        }

        $rawHop = new RawHop();
        $rawHop->dataType = $things[0];
        $rawHop->idx = (int)$things[1];
        $rawHop->value = $things[2];

        if ($this->hopCount < $rawHop->idx + 1) {
            $this->hopCount = $rawHop->idx + 1;
        }

        if (!isset($this->hopsCollection[$rawHop->idx])) {
            $this->hopsCollection[$rawHop->idx] = new Hop();
        }

        $hop = $this->hopsCollection[$rawHop->idx];
        $hop->idx = $rawHop->idx;
        switch ($rawHop->dataType) {
            case 'h':
                $hop->ips[] = $rawHop->value;
                $hop->hosts[] = gethostbyaddr($rawHop->value) ?: null;
                break;
            case 'd':
                //Not entirely sure if multiple IPs. Better use -n in mtr and resolve later in summarize.
                //out.Hops[data.idx].Host = append(out.Hops[data.idx].Host, data.value)
                break;
            case 'p':
                $hop->sent++;
                $hop->timings[] = (float)$rawHop->value / 1000;
                break;
        }

        $this->hopsCollection[$rawHop->idx] = $hop;

        $this->filterLastDupeHop();
    }

    // Function to calculate standard deviation (uses sd_square)

    private function filterLastDupeHop()
    {
        // filter dupe last hop
        $finalIdx = 0;
        $previousIp = '';

        foreach ($this->hopsCollection as $key => $hop) {
            if (count($hop->ips) && $hop->ips[0] !== $previousIp) {
                $previousIp = $hop->ips[0];
                $finalIdx = $key + 1;
            }
        }

        unset($this->hopsCollection[$finalIdx]);

        usort($this->hopsCollection, function ($a, $b) {
            return $a->idx - $b->idx;
        });
    }
}
