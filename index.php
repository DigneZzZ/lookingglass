<?php declare(strict_types=1);
/**
 * Looking Glass - Modern Tailwind/shadcn UI
 *
 * Provides UI and input for the looking glass backend.
 *
 * @copyright 2024-2026 DigneZzZ (gig.ovh)
 * @license Mozilla Public License 2.0
 * @version 2.0.0
 * @link https://github.com/DigneZzZ/lookingglass
 */

require __DIR__.'/bootstrap.php';

use Hybula\LookingGlass;

$errorMessage = null;
if (!empty($_POST)) {
    if (!isset($_POST['csrfToken']) || !isset($_SESSION[LookingGlass::SESSION_CSRF]) || ($_POST['csrfToken'] !== $_SESSION[LookingGlass::SESSION_CSRF])) {
        exitErrorMessage('Missing or incorrect CSRF token.');
    }

    if (!isset($_POST['submitForm']) || !isset($_POST['backendMethod']) || !isset($_POST['targetHost'])) {
        exitErrorMessage('Unsupported POST received.');
    }

    if (!in_array($_POST['backendMethod'], LG_METHODS)) {
        exitErrorMessage('Unsupported backend method.');
    }

    $_SESSION[LookingGlass::SESSION_TARGET_METHOD] = $_POST['backendMethod'];
    $_SESSION[LookingGlass::SESSION_TARGET_HOST]   = $_POST['targetHost'];
    if (!isset($_POST['checkTerms']) && LG_TERMS) {
        exitErrorMessage('You must agree with the Terms of Service.');
    }

    $targetHost = $_POST['targetHost'];
    if (in_array($_POST['backendMethod'], ['ping', 'mtr', 'traceroute'])) {
        if (!LookingGlass::isValidIpv4($_POST['targetHost']) &&
            !$targetHost = LookingGlass::isValidHost($_POST['targetHost'], LookingGlass::IPV4)
        ) {
            exitErrorMessage('No valid IPv4 provided.');
        }
    }

    if (in_array($_POST['backendMethod'], ['ping6', 'mtr6', 'traceroute6'])) {
        if (!LookingGlass::isValidIpv6($_POST['targetHost']) &&
            !$targetHost = LookingGlass::isValidHost($_POST['targetHost'],LookingGlass::IPV6)
        ) {
            exitErrorMessage('No valid IPv6 provided.');
        }
    }

    $_SESSION[LookingGlass::SESSION_TARGET_HOST]  = $targetHost;
    $_SESSION[LookingGlass::SESSION_TOS_CHECKED]  = true;
    $_SESSION[LookingGlass::SESSION_CALL_BACKEND] = true;
    exitNormal();
}

$templateData['session_target']       = $_SESSION[LookingGlass::SESSION_TARGET_HOST] ?? '';
$templateData['session_method']       = $_SESSION[LookingGlass::SESSION_TARGET_METHOD] ?? '';
$templateData['session_call_backend'] = $_SESSION[LookingGlass::SESSION_CALL_BACKEND] ?? false;
$templateData['session_tos_checked']  = isset($_SESSION[LookingGlass::SESSION_TOS_CHECKED]) ? ' checked' : '';

if (isset($_SESSION[LookingGlass::SESSION_ERROR_MESSAGE])) {
    $templateData['error_message'] = $_SESSION[LookingGlass::SESSION_ERROR_MESSAGE];
    unset($_SESSION[LookingGlass::SESSION_ERROR_MESSAGE]);
}

if (LG_BLOCK_CUSTOM) {
    if (defined('LG_CUSTOM_PHP') && file_exists(LG_CUSTOM_PHP)) {
        include LG_CUSTOM_PHP;
    }

    if (defined('LG_CUSTOM_HTML') && file_exists(LG_CUSTOM_HTML)) {
        ob_start();
        include LG_CUSTOM_HTML;
        $templateData['custom_html'] = ob_get_clean();
    }

    if (defined('LG_CUSTOM_HEADER_PHP') && file_exists(LG_CUSTOM_HEADER_PHP)) {
        ob_start();
        include LG_CUSTOM_HEADER_PHP;
        $templateData['custom_header'] = ob_get_clean();
    }

    if (defined('LG_CUSTOM_FOOTER_PHP') && file_exists(LG_CUSTOM_FOOTER_PHP)) {
        ob_start();
        include LG_CUSTOM_FOOTER_PHP;
        $templateData['custom_footer'] = ob_get_clean();
    }
}

if (LG_CHECK_LATENCY) {
    $templateData['latency'] = LookingGlass::getLatency();
}

// Generate CSRF token only if not already set in session
if (!isset($_SESSION[LookingGlass::SESSION_CSRF])) {
    $_SESSION[LookingGlass::SESSION_CSRF] = bin2hex(random_bytes(12));
}
$templateData['csrfToken'] = $_SESSION[LookingGlass::SESSION_CSRF];
?>
<!doctype html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <meta name="description" content="Network Looking Glass - Test network connectivity">
    <meta name="color-scheme" content="dark light">
    <title><?php echo $templateData['title'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        border: 'hsl(var(--border))',
                        input: 'hsl(var(--input))',
                        ring: 'hsl(var(--ring))',
                        background: 'hsl(var(--background))',
                        foreground: 'hsl(var(--foreground))',
                        primary: {
                            DEFAULT: 'hsl(var(--primary))',
                            foreground: 'hsl(var(--primary-foreground))',
                        },
                        secondary: {
                            DEFAULT: 'hsl(var(--secondary))',
                            foreground: 'hsl(var(--secondary-foreground))',
                        },
                        destructive: {
                            DEFAULT: 'hsl(var(--destructive))',
                            foreground: 'hsl(var(--destructive-foreground))',
                        },
                        muted: {
                            DEFAULT: 'hsl(var(--muted))',
                            foreground: 'hsl(var(--muted-foreground))',
                        },
                        accent: {
                            DEFAULT: 'hsl(var(--accent))',
                            foreground: 'hsl(var(--accent-foreground))',
                        },
                        card: {
                            DEFAULT: 'hsl(var(--card))',
                            foreground: 'hsl(var(--card-foreground))',
                        },
                    },
                    borderRadius: {
                        lg: 'var(--radius)',
                        md: 'calc(var(--radius) - 2px)',
                        sm: 'calc(var(--radius) - 4px)',
                    },
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        :root {
            --background: 0 0% 100%;
            --foreground: 222.2 84% 4.9%;
            --card: 0 0% 100%;
            --card-foreground: 222.2 84% 4.9%;
            --primary: 221.2 83.2% 53.3%;
            --primary-foreground: 210 40% 98%;
            --secondary: 210 40% 96.1%;
            --secondary-foreground: 222.2 47.4% 11.2%;
            --muted: 210 40% 96.1%;
            --muted-foreground: 215.4 16.3% 46.9%;
            --accent: 210 40% 96.1%;
            --accent-foreground: 222.2 47.4% 11.2%;
            --destructive: 0 84.2% 60.2%;
            --destructive-foreground: 210 40% 98%;
            --border: 214.3 31.8% 91.4%;
            --input: 214.3 31.8% 91.4%;
            --ring: 221.2 83.2% 53.3%;
            --radius: 0.5rem;
        }

        .dark {
            --background: 222.2 84% 4.9%;
            --foreground: 210 40% 98%;
            --card: 222.2 84% 4.9%;
            --card-foreground: 210 40% 98%;
            --primary: 217.2 91.2% 59.8%;
            --primary-foreground: 222.2 47.4% 11.2%;
            --secondary: 217.2 32.6% 17.5%;
            --secondary-foreground: 210 40% 98%;
            --muted: 217.2 32.6% 17.5%;
            --muted-foreground: 215 20.2% 65.1%;
            --accent: 217.2 32.6% 17.5%;
            --accent-foreground: 210 40% 98%;
            --destructive: 0 62.8% 30.6%;
            --destructive-foreground: 210 40% 98%;
            --border: 217.2 32.6% 17.5%;
            --input: 217.2 32.6% 17.5%;
            --ring: 224.3 76.3% 48%;
        }

        body {
            @apply bg-background text-foreground antialiased;
        }

        .card {
            @apply rounded-xl border border-border bg-card text-card-foreground shadow-sm;
        }

        .btn {
            @apply inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50;
        }

        .btn-primary {
            @apply bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-4 py-2;
        }

        .btn-secondary {
            @apply bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80 h-9 px-4 py-2;
        }

        .btn-outline {
            @apply border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2;
        }

        .btn-ghost {
            @apply hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2;
        }

        .input {
            @apply flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50;
        }

        .select {
            @apply flex h-9 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50;
        }

        .badge {
            @apply inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2;
        }

        .badge-secondary {
            @apply border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80;
        }

        .badge-outline {
            @apply text-foreground;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }

        .animate-pulse-slow {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
    <?php if ($templateData['custom_css']) { echo '<link href="'.$templateData['custom_css'].'" rel="stylesheet">'; } ?>
    <?php if ($templateData['custom_head']) { echo $templateData['custom_head']; } ?>
</head>
<body class="min-h-screen">
    <?php echo isset($templateData['custom_header']) ? $templateData['custom_header'] : '' ?>

    <div class="container mx-auto max-w-5xl px-4 py-8 md:py-12">
        <!-- Header -->
        <header class="flex items-center justify-between pb-6 mb-8 border-b border-border">
            <div class="flex items-center gap-4">
                <a href="<?php echo $templateData['logo_url'] ?>" target="_blank" class="flex items-center gap-2 text-foreground hover:text-primary transition-colors">
                    <?php if ($templateData['logo_image'] || $templateData['logo_image_dark']): ?>
                    <!-- Light theme logo -->
                    <?php if ($templateData['logo_image']): ?>
                    <img src="<?php echo $templateData['logo_image'] ?>" alt="<?php echo strip_tags($templateData['logo_data']) ?: 'Looking Glass' ?>" class="h-10 block dark:hidden">
                    <?php endif ?>
                    <!-- Dark theme logo -->
                    <?php if ($templateData['logo_image_dark']): ?>
                    <img src="<?php echo $templateData['logo_image_dark'] ?>" alt="<?php echo strip_tags($templateData['logo_data']) ?: 'Looking Glass' ?>" class="h-10 hidden dark:block">
                    <?php elseif ($templateData['logo_image']): ?>
                    <img src="<?php echo $templateData['logo_image'] ?>" alt="<?php echo strip_tags($templateData['logo_data']) ?: 'Looking Glass' ?>" class="h-10 hidden dark:block">
                    <?php endif ?>
                    <?php else: ?>
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold"><?php echo strip_tags($templateData['logo_data']) ?: 'Looking Glass' ?></h1>
                        <p class="text-xs text-muted-foreground">Network Diagnostics</p>
                    </div>
                    <?php endif ?>
                </a>
            </div>
            <div class="flex items-center gap-3">
                <!-- Theme Toggle -->
                <button id="themeToggle" class="btn btn-ghost p-2" title="Toggle theme">
                    <svg class="w-5 h-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg class="w-5 h-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>
                <!-- Location Selector -->
                <?php if (!empty($templateData['locations'])): ?>
                <div class="relative">
                    <select onchange="window.location = this.value" class="select pr-8 min-w-[160px]">
                        <option value=""><?php echo $templateData['current_location'] ?></option>
                        <?php foreach ($templateData['locations'] as $location => $link): ?>
                            <?php if ($location !== $templateData['current_location']): ?>
                            <option value="<?php echo $link ?>"><?php echo $location ?></option>
                            <?php endif ?>
                        <?php endforeach ?>
                    </select>
                </div>
                <?php endif ?>
            </div>
        </header>

        <main class="space-y-6">
            <?php if (LG_BLOCK_NETWORK): ?>
            <!-- Network Info Card -->
            <div class="card">
                <div class="p-6">
                    <div class="flex items-center gap-2 mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                        </svg>
                        <h2 class="text-lg font-semibold">Network Information</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Location -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-muted-foreground">Location</label>
                            <div class="flex gap-2">
                                <input type="text" class="input flex-1" value="<?php echo $templateData['current_location'] ?>" readonly>
                                <a href="https://www.openstreetmap.org/search?query=<?php echo urlencode($templateData['maps_query']); ?>" target="_blank" class="btn btn-outline" title="View on map">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <!-- Facility -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-muted-foreground">Facility</label>
                            <div class="flex gap-2">
                                <input type="text" class="input flex-1" value="<?php echo $templateData['facility'] ?>" readonly>
                                <a href="<?php echo $templateData['facility_url'] ?>" target="_blank" class="btn btn-outline" title="PeeringDB">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <!-- Your IP -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-muted-foreground">Your IP</label>
                            <div class="flex gap-2 items-center">
                                <input type="text" class="input flex-1" value="<?php echo $templateData['user_ip'] ?>" readonly>
                                <?php if (LG_CHECK_LATENCY): ?>
                                <span class="badge badge-secondary" title="Latency to this server">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <?php echo $templateData['latency'] ?>ms
                                </span>
                                <?php endif ?>
                            </div>
                        </div>

                        <!-- IPv4 -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-muted-foreground">Server IPv4</label>
                            <div class="flex gap-2">
                                <input type="text" class="input flex-1 font-mono text-sm" value="<?php echo $templateData['ipv4'] ?>" readonly>
                                <button class="btn btn-outline" onclick="copyToClipboard('<?php echo $templateData['ipv4'] ?>', this)" title="Copy">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- IPv6 -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-medium text-muted-foreground">Server IPv6</label>
                            <div class="flex gap-2">
                                <input type="text" class="input flex-1 font-mono text-sm" value="<?php echo $templateData['ipv6'] ?>" readonly>
                                <button class="btn btn-outline" onclick="copyToClipboard('<?php echo $templateData['ipv6'] ?>', this)" title="Copy">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif ?>

            <?php if (LG_BLOCK_LOOKINGGLASS): ?>
            <!-- Looking Glass Card -->
            <div class="card">
                <div class="p-6">
                    <div class="flex items-center gap-2 mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h2 class="text-lg font-semibold">Looking Glass</h2>
                    </div>

                    <div id="lgForm" class="space-y-4">
                        <input type="hidden" id="csrfToken" value="<?php echo $templateData['csrfToken'] ?>">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                    Target Host
                                    <span class="relative group">
                                        <span class="w-4 h-4 inline-flex items-center justify-center rounded-full bg-muted text-muted-foreground text-xs cursor-help border border-border hover:bg-accent">?</span>
                                        <span class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 px-3 py-2 bg-popover text-popover-foreground text-xs rounded-md shadow-lg border border-border opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 w-64 z-50">
                                            Enter an IP address or hostname where this server will send packets to. Use quick buttons: <strong>My IP</strong> to test route to you, or DNS servers (8.8.8.8, 1.1.1.1) to check connectivity.
                                        </span>
                                    </span>
                                </label>
                                <div class="flex gap-2">
                                    <input type="text" class="input flex-1" placeholder="IP address or hostname..." id="targetHost" value="<?php echo $templateData['session_target'] ?>" required>
                                    <div class="flex gap-1">
                                        <button type="button" class="btn btn-outline text-xs px-2" onclick="setTarget('<?php echo $templateData['user_ip'] ?>')" title="Test route to your IP">
                                            My IP
                                        </button>
                                        <button type="button" class="btn btn-outline text-xs px-2" onclick="setTarget('8.8.8.8')" title="Google DNS">
                                            8.8.8.8
                                        </button>
                                        <button type="button" class="btn btn-outline text-xs px-2" onclick="setTarget('1.1.1.1')" title="Cloudflare DNS">
                                            1.1.1.1
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-muted-foreground">Method</label>
                                <select class="select" id="backendMethod">
                                    <?php foreach ($templateData['methods'] as $method): ?>
                                    <option value="<?php echo $method ?>"<?php if($templateData['session_method'] === $method): ?> selected<?php endif ?>><?php echo ucfirst($method) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <?php if ($templateData['tos']): ?>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" id="checkTerms" class="w-4 h-4 rounded border-border"<?php echo $templateData['session_tos_checked'] ?>>
                                <span>I agree with the <a href="<?php echo $templateData['tos'] ?>" target="_blank" class="text-primary hover:underline">Terms of Use</a></span>
                            </label>
                            <?php else: ?>
                            <div></div>
                            <?php endif ?>
                            <button type="button" class="btn btn-primary gap-2" id="executeButton" onclick="executeCommand()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Execute
                            </button>
                        </div>

                        <div id="errorAlert" class="hidden">
                            <div class="flex items-center gap-2 p-4 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm" id="errorMessage"></span>
                            </div>
                        </div>

                        <div class="hidden" id="outputCard">
                            <div class="rounded-lg bg-zinc-950 border border-zinc-800 p-4 mt-4">
                                <div class="flex items-center gap-2 mb-3 pb-3 border-b border-zinc-800">
                                    <div class="flex gap-1.5">
                                        <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                                        <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                                        <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                                    </div>
                                    <span class="text-xs text-zinc-500 ml-2" id="terminalTitle">Terminal Output</span>
                                </div>
                                <pre id="outputContent" class="font-mono text-sm text-green-400 whitespace-pre-wrap overflow-x-auto max-h-96 overflow-y-auto"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif ?>

            <?php if (LG_BLOCK_SPEEDTEST): ?>
            <!-- Speedtest Card -->
            <div class="card">
                <div class="p-6">
                    <div class="flex items-center gap-2 mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <h2 class="text-lg font-semibold">Speed Test</h2>
                    </div>

                    <?php if ($templateData['speedtest_iperf']): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                                </svg>
                                <label class="text-sm font-medium"><?php echo $templateData['speedtest_incoming_label'] ?></label>
                            </div>
                            <div class="flex gap-2">
                                <code class="flex-1 px-3 py-2 rounded-md bg-muted font-mono text-xs break-all"><?php echo $templateData['speedtest_incoming_cmd']; ?></code>
                                <button class="btn btn-outline" onclick="copyToClipboard('<?php echo $templateData['speedtest_incoming_cmd'] ?>', this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                                <label class="text-sm font-medium"><?php echo $templateData['speedtest_outgoing_label'] ?></label>
                            </div>
                            <div class="flex gap-2">
                                <code class="flex-1 px-3 py-2 rounded-md bg-muted font-mono text-xs break-all"><?php echo $templateData['speedtest_outgoing_cmd'] ?></code>
                                <button class="btn btn-outline" onclick="copyToClipboard('<?php echo $templateData['speedtest_outgoing_cmd'] ?>', this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif ?>

                    <?php if (count($templateData['speedtest_files'])): ?>
                    <div class="space-y-3">
                        <label class="text-sm font-medium text-muted-foreground">Download Test Files</label>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($templateData['speedtest_files'] as $file => $link): ?>
                            <a href="<?php echo $link ?>" class="btn btn-secondary gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <?php echo $file ?>
                            </a>
                            <?php endforeach ?>
                        </div>
                    </div>
                    <?php endif ?>
                </div>
            </div>
            <?php endif ?>

            <?php echo $templateData['custom_html'] ?? '' ?>
        </main>

        <!-- Footer -->
        <footer class="pt-6 mt-12 border-t border-border">
            <div class="flex items-center justify-between text-sm text-muted-foreground">
                <span>Powered by <a href="https://github.com/DigneZzZ/lookingglass" target="_blank" class="text-primary hover:underline">Looking Glass</a> by <a href="https://gig.ovh" target="_blank" class="text-primary hover:underline">DigneZzZ</a></span>
                <a href="https://github.com/DigneZzZ/lookingglass" target="_blank" class="hover:opacity-80 transition-opacity">
                    <img src="https://img.shields.io/github/stars/DigneZzZ/lookingglass?style=social" alt="GitHub Stars">
                </a>
            </div>
        </footer>
    </div>

    <?php echo isset($templateData['custom_footer']) ? $templateData['custom_footer'] : '' ?>

    <script>
        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        
        // Check saved theme or system preference
        const savedTheme = localStorage.getItem('theme');
        const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'light' || (!savedTheme && !systemDark)) {
            html.classList.remove('dark');
        }
        
        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
        });

        // Set target host from quick buttons
        function setTarget(ip) {
            document.getElementById('targetHost').value = ip;
            document.getElementById('targetHost').focus();
        }

        // Copy to clipboard
        async function copyToClipboard(text, button) {
            if (!navigator?.clipboard?.writeText) {
                return Promise.reject('Clipboard API not available');
            }
            
            const originalHtml = button.innerHTML;
            button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>';
            await navigator.clipboard.writeText(text);
            await new Promise(r => setTimeout(r, 1500));
            button.innerHTML = originalHtml;
        }

        // Execute command via AJAX
        let isExecuting = false;
        
        async function executeCommand() {
            if (isExecuting) return;
            
            const targetHost = document.getElementById('targetHost').value.trim();
            const method = document.getElementById('backendMethod').value;
            const csrfToken = document.getElementById('csrfToken').value;
            const executeButton = document.getElementById('executeButton');
            const outputCard = document.getElementById('outputCard');
            const outputContent = document.getElementById('outputContent');
            const errorAlert = document.getElementById('errorAlert');
            const errorMessage = document.getElementById('errorMessage');
            const terminalTitle = document.getElementById('terminalTitle');
            const checkTerms = document.getElementById('checkTerms');
            
            // Validation
            if (!targetHost) {
                showError('Please enter a target host');
                return;
            }
            
            <?php if ($templateData['tos']): ?>
            if (checkTerms && !checkTerms.checked) {
                showError('You must agree with the Terms of Use');
                return;
            }
            <?php endif ?>
            
            // Hide error, show output
            errorAlert.classList.add('hidden');
            outputCard.classList.remove('hidden');
            outputContent.innerHTML = '';
            terminalTitle.textContent = `${method} ${targetHost}`;
            
            // Set loading state
            isExecuting = true;
            executeButton.innerHTML = '<svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Executing...';
            executeButton.disabled = true;
            executeButton.classList.add('opacity-70');
            
            const isMtr = method === 'mtr' || method === 'mtr6';
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrfToken: csrfToken,
                        method: method,
                        target: targetHost
                    })
                });
                
                if (!response.ok) {
                    const errorText = await response.text();
                    showError(errorText || 'Request failed');
                    return;
                }
                
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                
                for await (const chunk of readChunks(reader)) {
                    const text = decoder.decode(chunk);
                    if (isMtr) {
                        const splittedText = text.split('@@@');
                        if (splittedText[1]) {
                            outputContent.innerHTML = splittedText[1].trim();
                        }
                    } else {
                        outputContent.innerHTML += text.trim().replace(/<br \/> +/g, '<br />');
                    }
                    // Auto-scroll to bottom
                    outputContent.scrollTop = outputContent.scrollHeight;
                }
            } catch (err) {
                showError('Network error: ' + err.message);
            } finally {
                isExecuting = false;
                executeButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg> Execute';
                executeButton.disabled = false;
                executeButton.classList.remove('opacity-70');
            }
        }
        
        function showError(message) {
            const errorAlert = document.getElementById('errorAlert');
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.textContent = message;
            errorAlert.classList.remove('hidden');
        }
        
        function readChunks(reader) {
            return {
                async* [Symbol.asyncIterator]() {
                    let readResult = await reader.read();
                    while (!readResult.done) {
                        yield readResult.value;
                        readResult = await reader.read();
                    }
                },
            };
        }
        
        // Execute on Enter key
        document.getElementById('targetHost')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') executeCommand();
        });
    </script>
</body>
</html>
