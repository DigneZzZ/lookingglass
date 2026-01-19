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
    
    // IPv4 network commands validation
    if (in_array($_POST['backendMethod'], ['ping', 'mtr', 'traceroute'])) {
        if (!LookingGlass::isValidIpv4($_POST['targetHost']) &&
            !$targetHost = LookingGlass::isValidHost($_POST['targetHost'], LookingGlass::IPV4)
        ) {
            exitErrorMessage('No valid IPv4 provided.');
        }
    }

    // IPv6 network commands validation
    if (in_array($_POST['backendMethod'], ['ping6', 'mtr6', 'traceroute6'])) {
        if (!LookingGlass::isValidIpv6($_POST['targetHost']) &&
            !$targetHost = LookingGlass::isValidHost($_POST['targetHost'],LookingGlass::IPV6)
        ) {
            exitErrorMessage('No valid IPv6 provided.');
        }
    }

    // WHOIS validation - accepts IP, domain, or ASN
    if ($_POST['backendMethod'] === 'whois') {
        $targetHost = trim($_POST['targetHost']);
        // Basic sanitization - allow alphanumeric, dots, colons, slashes, hyphens
        if (!preg_match('/^[a-zA-Z0-9.:\/\-]+$/', $targetHost)) {
            exitErrorMessage('Invalid WHOIS target. Use IP, domain, or ASN (e.g., AS15169).');
        }
    }

    // BGP validation - accepts IP, prefix, or ASN
    if ($_POST['backendMethod'] === 'bgp') {
        $targetHost = trim($_POST['targetHost']);
        // Allow IP addresses, prefixes (x.x.x.x/xx), and ASN (ASxxxxx or just numbers)
        $isValidBGP = filter_var($targetHost, FILTER_VALIDATE_IP) ||
                      preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/', $targetHost) ||
                      preg_match('/^[0-9a-fA-F:]+\/\d{1,3}$/', $targetHost) ||
                      preg_match('/^AS?\d+$/i', $targetHost);
        if (!$isValidBGP) {
            exitErrorMessage('Invalid BGP target. Use IP, prefix (x.x.x.x/xx), or ASN (AS15169).');
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
                        popover: {
                            DEFAULT: 'hsl(var(--popover))',
                            foreground: 'hsl(var(--popover-foreground))',
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
            --popover: 0 0% 100%;
            --popover-foreground: 222.2 84% 4.9%;
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
            --background: 224 71% 4%;
            --foreground: 213 31% 91%;
            --card: 224 71% 4%;
            --card-foreground: 213 31% 91%;
            --popover: 224 71% 4%;
            --popover-foreground: 213 31% 91%;
            --primary: 210 100% 52%;
            --primary-foreground: 222.2 47.4% 11.2%;
            --secondary: 222.2 47.4% 11.2%;
            --secondary-foreground: 210 40% 98%;
            --muted: 223 47% 11%;
            --muted-foreground: 215.4 16.3% 56.9%;
            --accent: 216 34% 17%;
            --accent-foreground: 210 40% 98%;
            --destructive: 0 63% 31%;
            --destructive-foreground: 210 40% 98%;
            --border: 216 34% 17%;
            --input: 216 34% 17%;
            --ring: 224 76% 48%;
        }

        body {
            @apply bg-background text-foreground antialiased;
        }

        /* Animated background */
        .bg-grid {
            background-size: 60px 60px;
            background-image: 
                linear-gradient(to right, hsl(var(--border) / 0.3) 1px, transparent 1px),
                linear-gradient(to bottom, hsl(var(--border) / 0.3) 1px, transparent 1px);
        }

        .dark .bg-grid {
            background-image: 
                linear-gradient(to right, hsl(216 34% 17% / 0.5) 1px, transparent 1px),
                linear-gradient(to bottom, hsl(216 34% 17% / 0.5) 1px, transparent 1px);
        }

        .bg-gradient-radial {
            background: radial-gradient(ellipse 80% 50% at 50% -20%, hsl(var(--primary) / 0.15), transparent);
        }

        .dark .bg-gradient-radial {
            background: radial-gradient(ellipse 80% 50% at 50% -20%, hsl(217 91% 60% / 0.15), transparent);
        }

        /* Glow effects */
        .glow {
            box-shadow: 0 0 20px -5px hsl(var(--primary) / 0.4);
        }

        .glow-sm {
            box-shadow: 0 0 10px -3px hsl(var(--primary) / 0.3);
        }

        .card {
            @apply rounded-xl border border-border bg-card/80 backdrop-blur-sm text-card-foreground shadow-lg transition-all duration-300;
        }

        .card:hover {
            @apply border-primary/30 shadow-xl;
            box-shadow: 0 0 30px -10px hsl(var(--primary) / 0.2);
        }

        .btn {
            @apply inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 active:scale-[0.98];
        }

        .btn-primary {
            @apply bg-primary text-primary-foreground shadow-md hover:bg-primary/90 hover:shadow-lg hover:shadow-primary/25 h-10 px-5 py-2;
        }

        .btn-secondary {
            @apply bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80 h-9 px-4 py-2;
        }

        .btn-outline {
            @apply border border-input bg-background/50 shadow-sm hover:bg-accent hover:text-accent-foreground hover:border-primary/50 h-9 px-4 py-2;
        }

        .btn-ghost {
            @apply hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2;
        }

        .input {
            @apply flex h-10 w-full rounded-lg border border-input bg-background/50 backdrop-blur-sm px-3 py-2 text-sm shadow-sm transition-all duration-200 file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-50;
        }

        .select {
            @apply flex h-10 w-full items-center justify-between rounded-lg border border-input bg-background/50 backdrop-blur-sm px-3 py-2 text-sm shadow-sm ring-offset-background transition-all duration-200 placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-primary disabled:cursor-not-allowed disabled:opacity-50;
        }

        .badge {
            @apply inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2;
        }

        .badge-secondary {
            @apply border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80;
        }

        .badge-outline {
            @apply text-foreground;
        }

        /* Terminal styling */
        .terminal {
            @apply rounded-xl bg-black/90 backdrop-blur-md border border-white/10 shadow-2xl overflow-hidden;
        }

        .terminal-header {
            @apply flex items-center gap-2 px-4 py-3 bg-white/5 border-b border-white/10;
        }

        .terminal-dot {
            @apply w-3 h-3 rounded-full;
        }

        .terminal-content {
            @apply p-4 font-mono text-sm text-green-400 max-h-80 overflow-y-auto;
        }

        /* Animations */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes fade-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slide-up {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .animate-fade-in {
            animation: fade-in 0.5s ease-out forwards;
        }

        .animate-slide-up {
            animation: slide-up 0.6s ease-out forwards;
        }

        /* Staggered animation delays */
        .delay-100 { animation-delay: 100ms; }
        .delay-200 { animation-delay: 200ms; }
        .delay-300 { animation-delay: 300ms; }
        .delay-400 { animation-delay: 400ms; }

        /* Mobile responsive improvements */
        @media (max-width: 640px) {
            .card {
                @apply rounded-lg;
            }
            
            .btn {
                @apply h-11 text-base;
            }

            .input, .select {
                @apply h-11 text-base;
            }

            .terminal-content {
                @apply text-xs max-h-64;
            }
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            @apply bg-transparent;
        }

        ::-webkit-scrollbar-thumb {
            @apply bg-muted rounded-full;
        }

        ::-webkit-scrollbar-thumb:hover {
            @apply bg-muted-foreground/50;
        }

        /* Focus visible improvements */
        *:focus-visible {
            @apply outline-none ring-2 ring-primary ring-offset-2 ring-offset-background;
        }

        /* Tooltip */
        .tooltip {
            @apply absolute z-50 px-3 py-2 text-xs font-medium text-white bg-gray-900 rounded-lg shadow-lg;
        }

        /* Popover styling */
        .popover {
            @apply absolute z-50 w-72 rounded-lg border border-border bg-popover p-4 text-popover-foreground shadow-lg;
        }
    </style>
    <?php if ($templateData['custom_css']) { echo '<link href="'.$templateData['custom_css'].'" rel="stylesheet">'; } ?>
    <?php if ($templateData['custom_head']) { echo $templateData['custom_head']; } ?>
</head>
<body class="min-h-screen bg-grid bg-gradient-radial">
    <!-- Animated background orbs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-primary/5 rounded-full blur-3xl animate-pulse-slow"></div>
        <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-blue-500/5 rounded-full blur-3xl animate-pulse-slow delay-200"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-500/3 rounded-full blur-3xl animate-float"></div>
    </div>

    <?php echo isset($templateData['custom_header']) ? $templateData['custom_header'] : '' ?>

    <div class="container mx-auto max-w-5xl px-3 sm:px-4 py-6 sm:py-8 md:py-12">
        <!-- Header -->
        <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 mb-6 sm:mb-8 border-b border-border/50 animate-fade-in">
            <div class="flex items-center gap-3 sm:gap-4">
                <a href="<?php echo $templateData['logo_url'] ?>" target="_blank" class="flex items-center gap-2 sm:gap-3 text-foreground hover:text-primary transition-all duration-300 group">
                    <?php if ($templateData['logo_image'] || $templateData['logo_image_dark']): ?>
                    <!-- Light theme logo -->
                    <?php if ($templateData['logo_image']): ?>
                    <img src="<?php echo $templateData['logo_image'] ?>" alt="<?php echo strip_tags($templateData['logo_data']) ?: 'Looking Glass' ?>" class="h-8 sm:h-10 block dark:hidden transition-transform group-hover:scale-105">
                    <?php endif ?>
                    <!-- Dark theme logo -->
                    <?php if ($templateData['logo_image_dark']): ?>
                    <img src="<?php echo $templateData['logo_image_dark'] ?>" alt="<?php echo strip_tags($templateData['logo_data']) ?: 'Looking Glass' ?>" class="h-8 sm:h-10 hidden dark:block transition-transform group-hover:scale-105">
                    <?php elseif ($templateData['logo_image']): ?>
                    <img src="<?php echo $templateData['logo_image'] ?>" alt="<?php echo strip_tags($templateData['logo_data']) ?: 'Looking Glass' ?>" class="h-8 sm:h-10 hidden dark:block transition-transform group-hover:scale-105">
                    <?php endif ?>
                    <?php else: ?>
                    <div class="flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-primary/20 to-primary/5 border border-primary/20 shadow-lg group-hover:shadow-primary/20 transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 sm:w-6 sm:h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                <?php 
                // Фильтруем локации, исключая текущий сервер по домену
                $currentHost = $_SERVER['HTTP_HOST'] ?? '';
                $filteredLocations = array_filter($templateData['locations'], function($link) use ($currentHost) {
                    $parsedUrl = parse_url($link);
                    $linkHost = $parsedUrl['host'] ?? '';
                    return $linkHost !== $currentHost;
                });
                ?>
                <?php if (!empty($filteredLocations)): ?>
                <div class="relative">
                    <select onchange="if(this.value) window.location = this.value" class="select pr-8 min-w-[140px] sm:min-w-[160px] text-sm">
                        <option value=""><?php echo htmlspecialchars($templateData['current_location']) ?></option>
                        <?php foreach ($filteredLocations as $location => $link): ?>
                            <option value="<?php echo htmlspecialchars($link) ?>"><?php echo htmlspecialchars($location) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <?php endif ?>
            </div>
        </header>

        <main class="space-y-4 sm:space-y-6">
            <?php if (LG_BLOCK_NETWORK): ?>
            <!-- Network Info Card -->
            <div class="card animate-slide-up">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center gap-2 mb-4 sm:mb-6">
                        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                            </svg>
                        </div>
                        <h2 class="text-base sm:text-lg font-semibold">Network Information</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                        <!-- Location -->
                        <div class="space-y-2">
                            <label class="text-xs sm:text-sm font-medium text-muted-foreground">Location</label>
                            <div class="flex gap-2">
                                <input type="text" class="input flex-1 text-sm" value="<?php echo $templateData['current_location'] ?>" readonly>
                                <a href="https://www.openstreetmap.org/search?query=<?php echo urlencode($templateData['maps_query']); ?>" target="_blank" class="btn btn-outline shrink-0" title="View on map">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <!-- Facility -->
                        <div class="space-y-2">
                            <label class="text-xs sm:text-sm font-medium text-muted-foreground">Facility</label>
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
            <div class="card animate-slide-up delay-100">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center gap-2 mb-4 sm:mb-6">
                        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h2 class="text-base sm:text-lg font-semibold">Looking Glass</h2>
                    </div>

                    <div id="lgForm" class="space-y-4">
                        <input type="hidden" id="csrfToken" value="<?php echo $templateData['csrfToken'] ?>">

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-4">
                            <div class="lg:col-span-2 space-y-2">
                                <label class="text-xs sm:text-sm font-medium text-muted-foreground flex items-center gap-2">
                                    Target Host
                                    <span class="relative group">
                                        <span class="w-4 h-4 inline-flex items-center justify-center rounded-full bg-muted text-muted-foreground text-xs cursor-help border border-border hover:bg-accent transition-colors">?</span>
                                        <span class="absolute left-0 sm:left-1/2 sm:-translate-x-1/2 bottom-full mb-2 px-3 py-2 bg-card text-card-foreground text-xs rounded-lg shadow-xl border border-border opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 w-64 z-50">
                                            Enter an IP address or hostname where this server will send packets to. Use quick buttons: <strong>My IP</strong> to test route to you, or DNS servers (8.8.8.8, 1.1.1.1) to check connectivity.
                                        </span>
                                    </span>
                                </label>
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <input type="text" class="input flex-1" placeholder="IP address or hostname..." id="targetHost" value="<?php echo htmlspecialchars($templateData['session_target']) ?>" required>
                                    <div class="flex gap-1 flex-wrap">
                                        <button type="button" class="btn btn-outline text-xs px-2 sm:px-3 h-9 sm:h-10" onclick="setTarget('<?php echo $templateData['user_ip'] ?>')" title="Test route to your IP">
                                            My IP
                                        </button>
                                        <button type="button" class="btn btn-outline text-xs px-2 sm:px-3 h-9 sm:h-10" onclick="setTarget('8.8.8.8')" title="Google DNS">
                                            8.8.8.8
                                        </button>
                                        <button type="button" class="btn btn-outline text-xs px-2 sm:px-3 h-9 sm:h-10" onclick="setTarget('1.1.1.1')" title="Cloudflare DNS">
                                            1.1.1.1
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs sm:text-sm font-medium text-muted-foreground">Method</label>
                                <select class="select" id="backendMethod">
                                    <?php foreach ($templateData['methods'] as $method): ?>
                                    <option value="<?php echo $method ?>"<?php if($templateData['session_method'] === $method): ?> selected<?php endif ?>><?php echo ucfirst($method) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-2">
                            <?php if ($templateData['tos']): ?>
                            <label class="flex items-center gap-2 text-xs sm:text-sm cursor-pointer group">
                                <input type="checkbox" id="checkTerms" class="w-4 h-4 rounded border-border accent-primary"<?php echo $templateData['session_tos_checked'] ?>>
                                <span class="group-hover:text-primary transition-colors">I agree with the <a href="<?php echo $templateData['tos'] ?>" target="_blank" class="text-primary hover:underline font-medium">Terms of Use</a></span>
                            </label>
                            <?php else: ?>
                            <div></div>
                            <?php endif ?>
                            <button type="button" class="btn btn-primary gap-2 w-full sm:w-auto glow-sm" id="executeButton" onclick="executeCommand()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Execute
                            </button>
                        </div>

                        <div id="errorAlert" class="hidden animate-fade-in">
                            <div class="flex items-center gap-3 p-4 rounded-xl bg-destructive/10 border border-destructive/30 text-destructive">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm font-medium" id="errorMessage"></span>
                            </div>
                        </div>

                        <div class="hidden animate-fade-in" id="outputCard">
                            <div class="terminal mt-4">
                                <div class="terminal-header">
                                    <div class="flex gap-1.5">
                                        <div class="terminal-dot bg-red-500"></div>
                                        <div class="terminal-dot bg-yellow-500"></div>
                                        <div class="terminal-dot bg-green-500"></div>
                                    </div>
                                    <span class="text-xs text-zinc-400 ml-2 font-mono" id="terminalTitle">Terminal Output</span>
                                </div>
                                <pre id="outputContent" class="terminal-content whitespace-pre-wrap"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif ?>

            <?php if (!empty($templateData['network_info'])): ?>
            <?php $netInfo = $templateData['network_info']; ?>
            <!-- Network Details Card -->
            <div class="card animate-slide-up delay-150">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center gap-2 mb-4 sm:mb-6">
                        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                        </div>
                        <h2 class="text-base sm:text-lg font-semibold">Network Details</h2>
                        <?php if (!empty($netInfo['fetched_at'])): ?>
                        <span class="ml-auto text-[10px] text-muted-foreground" title="Data cached from bgpview.io">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?php echo date('Y-m-d H:i', $netInfo['fetched_at']); ?>
                        </span>
                        <?php endif ?>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- ASN -->
                        <div class="space-y-2">
                            <label class="text-xs sm:text-sm font-medium text-muted-foreground">Autonomous System</label>
                            <div class="flex gap-2">
                                <div class="flex-1 px-3 py-2 rounded-lg bg-muted/50 border border-border/50">
                                    <span class="font-mono text-sm font-semibold text-primary"><?php echo htmlspecialchars($netInfo['asn']) ?></span>
                                    <?php if (!empty($netInfo['asn_name'])): ?>
                                    <span class="text-sm text-muted-foreground ml-2"><?php echo htmlspecialchars($netInfo['asn_name']) ?></span>
                                    <?php endif ?>
                                </div>
                                <?php if (!empty($netInfo['peeringdb'])): ?>
                                <a href="<?php echo htmlspecialchars($netInfo['peeringdb']) ?>" target="_blank" class="btn btn-outline shrink-0" title="View on PeeringDB">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                                <?php endif ?>
                            </div>
                        </div>

                        <?php if (!empty($netInfo['prefixes_v4'])): ?>
                        <!-- IPv4 Prefixes -->
                        <div class="space-y-2">
                            <label class="text-xs sm:text-sm font-medium text-muted-foreground">IPv4 Prefixes</label>
                            <div class="px-3 py-2 rounded-lg bg-muted/50 border border-border/50">
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($netInfo['prefixes_v4'] as $prefix): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-green-500/10 text-green-600 dark:text-green-400 text-xs font-mono"><?php echo htmlspecialchars($prefix) ?></span>
                                    <?php endforeach ?>
                                </div>
                            </div>
                        </div>
                        <?php endif ?>

                        <?php if (!empty($netInfo['prefixes_v6'])): ?>
                        <!-- IPv6 Prefixes -->
                        <div class="space-y-2">
                            <label class="text-xs sm:text-sm font-medium text-muted-foreground">IPv6 Prefixes</label>
                            <div class="px-3 py-2 rounded-lg bg-muted/50 border border-border/50">
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($netInfo['prefixes_v6'] as $prefix): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-600 dark:text-blue-400 text-xs font-mono"><?php echo htmlspecialchars($prefix) ?></span>
                                    <?php endforeach ?>
                                </div>
                            </div>
                        </div>
                        <?php endif ?>

                        <?php if (!empty($netInfo['ix_list'])): ?>
                        <!-- Internet Exchanges -->
                        <div class="space-y-2 sm:col-span-2 lg:col-span-3">
                            <label class="text-xs sm:text-sm font-medium text-muted-foreground">Internet Exchanges</label>
                            <div class="px-3 py-2 rounded-lg bg-muted/50 border border-border/50">
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($netInfo['ix_list'] as $ix): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary/10 text-primary text-xs font-medium">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <?php echo htmlspecialchars($ix) ?>
                                    </span>
                                    <?php endforeach ?>
                                </div>
                            </div>
                        </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
            <?php endif ?>

            <?php if (LG_BLOCK_SPEEDTEST): ?>
            <!-- Speedtest Card -->
            <div class="card animate-slide-up delay-200">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center gap-2 mb-4 sm:mb-6">
                        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h2 class="text-base sm:text-lg font-semibold">Speed Test</h2>
                    </div>

                    <?php if ($templateData['speedtest_iperf']): ?>
                    <!-- iPerf3 Help -->
                    <div class="mb-4 p-3 rounded-lg bg-muted/30 border border-border/50">
                        <div class="flex items-start gap-2">
                            <span class="text-primary mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div class="text-xs text-muted-foreground space-y-1">
                                <p class="font-medium text-foreground">iPerf3 Options:</p>
                                <p><code class="px-1 py-0.5 bg-muted rounded text-[10px]">-c</code> client mode &nbsp;
                                   <code class="px-1 py-0.5 bg-muted rounded text-[10px]">-p</code> port (5201) &nbsp;
                                   <code class="px-1 py-0.5 bg-muted rounded text-[10px]">-P</code> parallel streams &nbsp;
                                   <code class="px-1 py-0.5 bg-muted rounded text-[10px]">-R</code> reverse (download) &nbsp;
                                   <code class="px-1 py-0.5 bg-muted rounded text-[10px]">-t</code> duration (sec)</p>
                                <p class="text-[10px] opacity-75">Server accepts one connection at a time (--one-off mode for security)</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-6">
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                                </svg>
                                <label class="text-xs sm:text-sm font-medium"><?php echo $templateData['speedtest_incoming_label'] ?></label>
                                <span class="text-[10px] text-muted-foreground">(upload to server)</span>
                            </div>
                            <div class="flex gap-2">
                                <code class="flex-1 px-3 py-2 rounded-lg bg-muted/50 font-mono text-xs break-all border border-border/50"><?php echo $templateData['speedtest_incoming_cmd']; ?></code>
                                <button class="btn btn-outline shrink-0" onclick="copyToClipboard('<?php echo $templateData['speedtest_incoming_cmd'] ?>', this)">
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
                                <span class="text-[10px] text-muted-foreground">(download from server)</span>
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
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-muted-foreground">Download Test Files</label>
                            <span class="text-xs text-muted-foreground">(limited per session)</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($templateData['speedtest_files'] as $file => $link): ?>
                            <button type="button" onclick="downloadFile('<?php echo $file ?>', '<?php echo $link ?>')" class="btn btn-secondary gap-2 download-btn group" data-file="<?php echo $file ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-hover:translate-y-0.5 download-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span class="file-label"><?php echo $file ?></span>
                            </button>
                            <?php endforeach ?>
                        </div>
                        <p class="text-xs text-muted-foreground">Limits: 100M (3×), 1G (2×), 10G (1×) per session</p>
                    </div>
                    <?php endif ?>
                </div>
            </div>
            <?php endif ?>

            <?php echo $templateData['custom_html'] ?? '' ?>
        </main>

        <!-- Footer -->
        <footer class="pt-6 mt-8 sm:mt-12 border-t border-border/50 animate-fade-in delay-300">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs sm:text-sm text-muted-foreground">
                <span class="text-center sm:text-left">Powered by <a href="https://github.com/DigneZzZ/lookingglass" target="_blank" class="text-primary hover:underline font-medium transition-colors">Looking Glass</a> by <a href="https://gig.ovh" target="_blank" class="text-primary hover:underline font-medium transition-colors">DigneZzZ</a></span>
                <a href="https://github.com/DigneZzZ/lookingglass" target="_blank" class="hover:opacity-80 transition-all hover:scale-105">
                    <img src="https://img.shields.io/github/stars/DigneZzZ/lookingglass?style=social" alt="GitHub Stars" class="h-5">
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

        // Toast notification
        function showToast(message, type = 'error') {
            // Remove existing toasts
            document.querySelectorAll('.toast-notification').forEach(t => t.remove());
            
            const toast = document.createElement('div');
            toast.className = `toast-notification fixed bottom-4 right-4 left-4 sm:left-auto sm:w-96 z-50 p-4 rounded-xl shadow-2xl border backdrop-blur-md animate-fade-in ${
                type === 'error' 
                    ? 'bg-destructive/90 border-destructive text-white' 
                    : type === 'success'
                    ? 'bg-green-500/90 border-green-600 text-white'
                    : 'bg-card/90 border-border text-card-foreground'
            }`;
            
            toast.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="shrink-0 mt-0.5">
                        ${type === 'error' 
                            ? '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                            : type === 'success'
                            ? '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
                            : '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                        }
                    </div>
                    <div class="flex-1 text-sm font-medium">${message}</div>
                    <button onclick="this.closest('.toast-notification').remove()" class="shrink-0 hover:opacity-70 transition-opacity">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        // Download file with limit check
        async function downloadFile(fileName, url) {
            const button = document.querySelector(`[data-file="${fileName}"]`);
            const icon = button.querySelector('.download-icon');
            const label = button.querySelector('.file-label');
            
            // Show loading state
            const originalIcon = icon.outerHTML;
            icon.outerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
            button.disabled = true;
            button.classList.add('opacity-70');
            
            try {
                const response = await fetch(url, { method: 'HEAD' });
                
                if (response.status === 429) {
                    // Rate limited - get error message
                    const errorResponse = await fetch(url);
                    const errorData = await errorResponse.json();
                    showToast(`${errorData.error}. ${errorData.message}`, 'error');
                    return;
                }
                
                if (!response.ok) {
                    showToast('Download failed. Please try again.', 'error');
                    return;
                }
                
                // Start actual download
                showToast(`Starting download: ${fileName}...`, 'info');
                
                // Create hidden link and trigger download
                const link = document.createElement('a');
                link.href = url;
                link.download = fileName + '.bin';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
            } catch (err) {
                showToast('Network error. Please try again.', 'error');
            } finally {
                // Restore button
                button.disabled = false;
                button.classList.remove('opacity-70');
                button.querySelector('svg').outerHTML = originalIcon;
            }
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
