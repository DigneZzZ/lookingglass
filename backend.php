<?php declare(strict_types=1);
/**
 * Looking Glass
 *
 * Does the actual backend work for executed commands.
 *
 * @copyright 2024-2026 DigneZzZ (gig.ovh)
 * @license Mozilla Public License 2.0
 * @version 2.0.0
 * @link https://github.com/DigneZzZ/lookingglass
 */

require __DIR__.'/LookingGlass.php';
require __DIR__.'/config.php';

use Hybula\LookingGlass;

LookingGlass::validateConfig();
LookingGlass::startSession();

header('X-Accel-Buffering: no');

if (isset($_SESSION[LookingGlass::SESSION_TARGET_HOST]) &&
    isset($_SESSION[LookingGlass::SESSION_TARGET_METHOD]) &&
    isset($_SESSION[LookingGlass::SESSION_CALL_BACKEND])
) {
    unset($_SESSION[LookingGlass::SESSION_CALL_BACKEND]);


    switch ($_SESSION[LookingGlass::SESSION_TARGET_METHOD]) {
        case LookingGlass::METHOD_PING:
            LookingGlass::ping($_SESSION[LookingGlass::SESSION_TARGET_HOST]);
            break;
        case LookingGlass::METHOD_PING6:
            LookingGlass::ping6($_SESSION[LookingGlass::SESSION_TARGET_HOST]);
            break;
        case LookingGlass::METHOD_MTR:
            LookingGlass::mtr($_SESSION[LookingGlass::SESSION_TARGET_HOST]);
            break;
        case LookingGlass::METHOD_MTR6:
            LookingGlass::mtr6($_SESSION[LookingGlass::SESSION_TARGET_HOST]);
            break;
        case LookingGlass::METHOD_TRACEROUTE:
            LookingGlass::traceroute($_SESSION[LookingGlass::SESSION_TARGET_HOST]);
            break;
        case LookingGlass::METHOD_TRACEROUTE6:
            LookingGlass::traceroute6($_SESSION[LookingGlass::SESSION_TARGET_HOST]);
            break;
        case LookingGlass::METHOD_WHOIS:
            LookingGlass::whois($_SESSION[LookingGlass::SESSION_TARGET_HOST]);
            break;
        case LookingGlass::METHOD_BGP:
            LookingGlass::bgp($_SESSION[LookingGlass::SESSION_TARGET_HOST]);
            break;
    }
}
