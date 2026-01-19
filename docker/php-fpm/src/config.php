<?php declare(strict_types=1);
use Hybula\LookingGlass;

// Define the HTML title;
const LG_TITLE = 'Looking Glass';

// Define a logo, this can be HTML too, see the other example for an image;
define('LG_LOGO', getenv('LOGO'));
define('LG_LOGO_DARK', getenv('LOGO_DARK'));
// Define logo image URLs (optional, if set - will be used instead of LG_LOGO/LG_LOGO_DARK);
define('LG_LOGO_IMAGE', getenv('LOGO_IMAGE') ?: false);
define('LG_LOGO_IMAGE_DARK', getenv('LOGO_IMAGE_DARK') ?: false);
 
// Define the URL where the logo points to;
define('LG_LOGO_URL', getenv('LOGO_URL'));

// Theme mode;
const LG_THEME = 'auto';

// Enable the latency check feature;
const LG_CHECK_LATENCY = true;

// Define a custom CSS file which can be used to style the LG, set false to disable, else point to the CSS file;
const LG_CSS_OVERRIDES = false;
// Define <head> content, this could be JS, CSS or meta tags;
const LG_CUSTOM_HEAD = false;

// Enable or disable blocks/parts of the LG, pass these environment variables with any value to disable them;
define('LG_BLOCK_NETWORK', !getenv('DISABLE_BLOCK_NETWORK'));
define('LG_BLOCK_LOOKINGGLASS', !getenv('DISABLE_BLOCK_LOOKINGGLASS'));
define('LG_BLOCK_SPEEDTEST', !getenv('DISABLE_BLOCK_SPEEDTEST'));
// This enables the custom block, which you can use to add something custom to the LG;
define('LG_BLOCK_CUSTOM', getenv('ENABLE_CUSTOM_BLOCK'));

// Define a file here which will be used to display the custom block, can be PHP too which outputs HTML;
const LG_CUSTOM_HTML = __DIR__.'/custom.html.php';
// Define a file here which will be loaded on top of the index file, this can be used to do some post logic;
const LG_CUSTOM_PHP = __DIR__.'/custom.post.php';

// Define a file here which will be used to display the custom header. Will be at the top of file;
const LG_CUSTOM_HEADER_PHP = __DIR__.'/custom.header.php';
// Define a file here which will be used to display the custom footer. Will be at the bottom of file;
const LG_CUSTOM_FOOTER_PHP = __DIR__.'/custom.footer.php';

// Define the location of this network, usually a city and a country;
define('LG_LOCATION', getenv('LOCATION'));
// Define a query location for the link to openstreetmap (eg: Amsterdam, Netherlands will be https://www.openstreetmap.org/search?query=Amsterdam, Netherlands)
define('LG_MAPS_QUERY', getenv('MAPS_QUERY'));
// Define the facility where the network is located, usually a data center;
define('LG_FACILITY', getenv('FACILITY'));
// Define a direct link to more information about the facility, this should be a link to PeeringDB;
define('LG_FACILITY_URL', getenv('FACILITY_URL'));
// Define an IPv4 for testing;
define('LG_IPV4', getenv('IPV4_ADDRESS'));
// Define an IPv6 for testing;
define('LG_IPV6', getenv('IPV6_ADDRESS'));

// ============================================================================
// Available Methods
// ============================================================================
// 
// Network diagnostic tools:
// - ping/ping6       - ICMP echo requests (IPv4/IPv6)
// - mtr/mtr6         - Combination of ping and traceroute with stats
// - traceroute/traceroute6 - Path to destination
// 
// Lookup tools (optional, require whois package installed):
// - whois            - WHOIS lookup for IP, domain, or ASN
// - bgp              - BGP route lookup via bgp.tools
// 
// Note: WHOIS and BGP methods can be commented out if not needed
// ============================================================================

const LG_METHODS = [
    LookingGlass::METHOD_PING,
    LookingGlass::METHOD_PING6,
    LookingGlass::METHOD_MTR,
    LookingGlass::METHOD_MTR6,
    LookingGlass::METHOD_TRACEROUTE,
    LookingGlass::METHOD_TRACEROUTE6,
    LookingGlass::METHOD_WHOIS,
    LookingGlass::METHOD_BGP,
];

// Define other looking glasses, this is useful if you have multiple networks and looking glasses;
// Format: "Location1|https://url1,Location2|https://url2"
$locationsEnv = getenv('LG_LOCATIONS');
$parsedLocations = [];
if ($locationsEnv) {
    foreach (explode(',', $locationsEnv) as $item) {
        $parts = explode('|', trim($item));
        if (count($parts) === 2) {
            $parsedLocations[trim($parts[0])] = trim($parts[1]);
        }
    }
}
define('LG_LOCATIONS', $parsedLocations);

// ============================================================================
// iPerf3 Configuration
// ============================================================================
// 
// Режимы работы iPerf3:
// -c <host>  - режим клиента, подключение к серверу
// -s         - режим сервера (запускается в Docker контейнере)
// -p <port>  - порт для подключения (по умолчанию 5201)
// -P <n>     - количество параллельных потоков (рекомендуется 4-8)
// -R         - реверс (сервер отправляет данные клиенту, а не наоборот)
// -4         - использовать только IPv4
// -6         - использовать только IPv6
// -t <sec>   - длительность теста в секундах (по умолчанию 10)
// -i <sec>   - интервал вывода статистики
// -u         - использовать UDP вместо TCP
// -b <bps>   - ограничение пропускной способности (для UDP)
//
// Ограничения безопасности на сервере (docker-compose.caddy.yml):
// --one-off  - сервер обрабатывает только ОДНО подключение и завершается
//              (защита от DoS атак и злоупотреблений)
//              Supervisor автоматически перезапускает процесс после каждого теста
//
// Примеры команд для пользователей:
// Incoming (загрузка на сервер):   iperf3 -4 -c hostname -p 5201 -P 4
// Outgoing (скачивание с сервера): iperf3 -4 -c hostname -p 5201 -P 4 -R
// ============================================================================

// Enable the iPerf info inside the speedtest block, set to false to disable;
const LG_SPEEDTEST_IPERF = true;

// Define the label of an incoming iPerf test;
const LG_SPEEDTEST_LABEL_INCOMING = 'iPerf3 Incoming';

// Define the command to use to test incoming speed using iPerf;
// Incoming: клиент отправляет данные НА сервер (upload test)
$iperfHost = getenv('LG_DOMAIN') ?: getenv('IPV4_ADDRESS') ?: 'hostname';
define('LG_SPEEDTEST_CMD_INCOMING', 'iperf3 -4 -c ' . $iperfHost . ' -p 5201 -P 4');

// Define the label of an outgoing iPerf test;
const LG_SPEEDTEST_LABEL_OUTGOING = 'iPerf3 Outgoing';

// Define the command to use to test outgoing speed using iPerf;
// Outgoing (-R): сервер отправляет данные клиенту (download test)
define('LG_SPEEDTEST_CMD_OUTGOING', 'iperf3 -4 -c ' . $iperfHost . ' -p 5201 -P 4 -R');
// Define speedtest files with URLs to the actual files;
// Files are served through download.php with rate limiting
$lgDomain = getenv('LG_DOMAIN') ?: $_SERVER['HTTP_HOST'] ?? 'localhost';
define('LG_SPEEDTEST_FILES', [
    '100M' => 'https://' . $lgDomain . '/download.php?file=100MB',
    '1G' => 'https://' . $lgDomain . '/download.php?file=1GB',
    '10G' => 'https://' . $lgDomain . '/download.php?file=10GB'
]);

// Define if you require visitors to agree with the Terms of Use. The value should be a link to the terms, or false to disable it completely.
define('LG_TERMS', getenv('LG_TERMS') ?: false);
