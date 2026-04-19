<?php

require_once __DIR__ . '/bootstrap.php';

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$apiBaseUrl = ($basePath === '' ? '' : $basePath) . '/api';
$loginUrl = ($basePath === '' ? '' : $basePath) . '/login';
$adminUrl = ($basePath === '' ? '' : $basePath) . '/admin';
$assetBaseUrl = $basePath === '' ? '' : $basePath;
$adminToken = trim((string) ($_COOKIE['banamur_admin_token'] ?? ''));

if ($adminToken !== '') {
    try {
        $authService = new \AuthService();
        $identity = $authService->getAuthenticatedUser($adminToken);
        $roleCodes = array_values(array_filter(array_map(static function ($role) {
            return $role['code'] ?? null;
        }, $identity['user']['roles'] ?? [])));

        if (in_array('ADMIN', $roleCodes, true) || in_array('SUPER_ADMIN', $roleCodes, true)) {
            header('Location: ' . $adminUrl, true, 302);
            exit;
        }

        setcookie('banamur_admin_token', '', time() - 3600, '/');
    } catch (Throwable $exception) {
        setcookie('banamur_admin_token', '', time() - 3600, '/');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banamur Auth Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Instrument Sans', 'ui-sans-serif', 'sans-serif']
                    },
                    colors: {
                        ink: '#0f172a',
                        mist: '#eef4ff',
                        cloud: '#f8fafc',
                        accent: '#2563eb',
                        accentSoft: '#dbeafe',
                        mint: '#d1fae5',
                        roseglass: '#ffe4e6'
                    }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBaseUrl . '/css/admin-dashboard.css', ENT_QUOTES, 'UTF-8'); ?>">
</head>

<body class="min-h-screen bg-cloud text-ink font-sans antialiased login-only login-page-shell">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-16 top-0 h-80 w-80 rounded-full bg-accent/10 blur-3xl"></div>
        <div class="absolute right-0 top-24 h-72 w-72 rounded-full bg-cyan-200/40 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-emerald-100/60 blur-3xl"></div>
    </div>

    <div class="relative min-h-screen px-4 py-4 sm:px-6 lg:px-8">
        <div id="loginGateHero" class="login-gate-hero">
            <div class="login-gate-panel panel">
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                    Banamur Auth Admin
                </div>
                <h1 class="mt-6 text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl">
                    Connexion au back-office
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                    URL dediee a l'authentification. La page admin est separee et livree seulement si le JWT admin est valide.
                </p>

                <div class="mt-8 grid gap-4 lg:grid-cols-[0.9fr,1.1fr]">
                    <div class="card-subtle">
                        <div class="subcard-title">Acces de bootstrap</div>
                        <p class="subcard-text">Premier acces local si aucun autre administrateur n'est deja en service.</p>
                        <div class="credential-block mt-4">
                            <div><span class="credential-label">Login</span><strong>admin</strong></div>
                            <div><span class="credential-label">Mot de passe</span><strong>admin</strong></div>
                        </div>
                        <div class="mt-6 grid gap-3 text-sm leading-6 text-slate-600">
                            <div><strong>URL login</strong><br><?php echo htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><strong>URL admin</strong><br><?php echo htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>

                    <form id="loginForm" class="card-subtle space-y-4 auth-login-card auth-login-standalone">
                        <div>
                            <h4 class="subcard-title">Authentification administrateur</h4>
                            <p class="subcard-text">Les comptes non-admin sont rediriges hors de l'interface admin.</p>
                        </div>
                        <input type="hidden" name="login_scope" value="admin_console">
                        <input name="identifier" placeholder="Email ou username" class="input" autocomplete="username" required>
                        <input name="password" type="password" placeholder="Mot de passe" class="input" autocomplete="current-password" required>
                        <input name="company_site" type="text" autocomplete="off" tabindex="-1" class="sr-only anti-bot-trap" aria-hidden="true">
                        <div class="anti-bot-panel">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="subcard-title">Lagune Shield</div>
                                    <p id="antiBotPrompt" class="subcard-text mt-1">Chargement du controle anti-robot...</p>
                                </div>
                                <button id="reloadAntiBotButton" type="button" class="chip-button">Nouveau defi</button>
                            </div>
                            <div id="antiBotGrid" class="anti-bot-grid mt-4"></div>
                            <div class="mt-4 grid gap-3 sm:grid-cols-[1fr,170px]">
                                <input id="antiBotAnswer" name="anti_bot_answer" placeholder="Exemple : ABI-204" class="input uppercase tracking-[0.18em]" autocomplete="off" required>
                                <div id="antiBotStatus" class="anti-bot-status">Defi en attente</div>
                            </div>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-500">
                            <input id="useAdminToken" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-accent focus:ring-accent" checked>
                            Sauvegarder comme token admin
                        </label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button class="primary-button">Connexion</button>
                            <a href="<?php echo htmlspecialchars($apiBaseUrl . '/health', ENT_QUOTES, 'UTF-8'); ?>" class="secondary-button inline-flex items-center justify-center">Tester l'API</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="toastStack" class="toast-stack" aria-live="polite" aria-atomic="true"></div>

    <script>
        window.BANAMUR_DASHBOARD = {
            page: 'login',
            apiBaseUrl: <?php echo json_encode($apiBaseUrl, JSON_UNESCAPED_SLASHES); ?>,
            loginUrl: <?php echo json_encode($loginUrl, JSON_UNESCAPED_SLASHES); ?>,
            adminUrl: <?php echo json_encode($adminUrl, JSON_UNESCAPED_SLASHES); ?>
        };
    </script>
    <script src="<?php echo htmlspecialchars($assetBaseUrl . '/js/admin-dashboard.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>

</html>