<?php

require_once __DIR__ . '/bootstrap.php';

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$apiBaseUrl = ($basePath === '' ? '' : $basePath) . '/api';
$loginUrl = ($basePath === '' ? '' : $basePath) . '/login';
$adminUrl = ($basePath === '' ? '' : $basePath) . '/admin';
$assetBaseUrl = $basePath === '' ? '' : $basePath;
$adminToken = trim((string) ($_COOKIE['banamur_admin_token'] ?? ''));

if ($adminToken === '') {
    header('Location: ' . $loginUrl, true, 302);
    exit;
}

try {
    $authService = new \AuthService();
    $identity = $authService->getAuthenticatedUser($adminToken);
    $roleCodes = array_values(array_map(static function ($role) {
        return $role['code'] ?? null;
    }, $identity['user']['roles'] ?? []));

    if (!in_array('ADMIN', $roleCodes, true) && !in_array('SUPER_ADMIN', $roleCodes, true)) {
        throw new \ApiException('Acces admin requis.', 403, 'forbidden_role');
    }
} catch (Throwable $exception) {
    setcookie('banamur_admin_token', '', time() - 3600, '/');
    header('Location: ' . $loginUrl, true, 302);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banamur Auth Back Office</title>
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

<body class="min-h-screen bg-cloud text-ink font-sans antialiased admin-page-shell">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-16 top-0 h-80 w-80 rounded-full bg-accent/10 blur-3xl"></div>
        <div class="absolute right-0 top-24 h-72 w-72 rounded-full bg-cyan-200/40 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-emerald-100/60 blur-3xl"></div>
    </div>

    <div class="relative min-h-screen px-3 py-3 sm:px-4 lg:px-5">
        <div id="appShell" class="mx-auto grid max-w-[1480px] gap-4 xl:grid-cols-[270px,minmax(0,1fr)]">
            <aside id="sidebarShell" class="panel flex h-full flex-col p-5 lg:p-6">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Banamur Auth</div>
                    <h1 class="mt-5 text-3xl font-semibold tracking-tight text-slate-950">Mini back-office API</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Cette page est reservee aux roles ADMIN et SUPER_ADMIN. Le filtrage est verifie cote PHP avant rendu.</p>
                </div>

                <nav class="mt-8 grid gap-2" id="primaryNav">
                    <button type="button" class="nav-item" data-route="dashboard"><span class="nav-title">Dashboard</span><span class="nav-caption">Vue globale, auth et explorer</span></button>
                    <button type="button" class="nav-item" data-route="users"><span class="nav-title">Users</span><span class="nav-caption">Recherche, edition inline et pagination</span></button>
                    <button type="button" class="nav-item" data-route="roles"><span class="nav-title">Roles</span><span class="nav-caption">Permissions et gestion rapide</span></button>
                    <button type="button" class="nav-item" data-route="logs"><span class="nav-title">Logs</span><span class="nav-caption">Audit, filtres et timeline paginee</span></button>
                </nav>

                <div class="mt-8 space-y-4">
                    <div class="card-subtle">
                        <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Base API</div>
                        <div id="apiBaseUrlLabel" class="mt-2 text-sm font-medium text-slate-900"></div>
                    </div>
                    <div class="card-subtle bg-slate-950 text-white">
                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Session</div>
                        <div id="sessionStatus" class="mt-2 text-sm font-medium text-white">Admin connecte</div>
                        <div id="sessionIdentity" class="mt-2 text-xs leading-5 text-slate-300"></div>
                        <div id="sessionApiPermissions" class="mt-3 text-xs leading-5 text-slate-300"></div>
                        <div class="mt-4 grid gap-2 text-xs text-slate-400">
                            <div class="truncate">User: <span id="sidebarUserToken"></span></div>
                            <div class="truncate">Admin: <span id="sidebarAdminToken"></span></div>
                            <div class="truncate">API key locale: <span id="sidebarApiKey"></span></div>
                        </div>
                        <button id="openApiKeysButton" type="button" class="secondary-button mt-4 w-full">Ouvrir la gestion API</button>
                        <button id="sidebarLogoutButton" type="button" class="danger-button mt-4 w-full">Deconnecter admin/admin</button>
                    </div>
                </div>

                <div class="mt-auto rounded-[28px] border border-slate-200 bg-gradient-to-br from-accentSoft via-white to-mint p-5 shadow-card">
                    <div class="text-xs uppercase tracking-[0.24em] text-slate-500">Controle</div>
                    <p class="mt-3 text-sm leading-6 text-slate-700">Si le cookie admin disparaît ou devient invalide, retour immediat vers /login.</p>
                </div>
            </aside>

            <div class="grid gap-6">
                <header id="workspaceHeader" class="panel overflow-hidden px-5 py-5 sm:px-6">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <div class="text-xs uppercase tracking-[0.24em] text-slate-400">Back office</div>
                            <h2 id="currentViewTitle" class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Dashboard</h2>
                            <p id="currentViewDescription" class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Vue globale de l'API, gestion des comptes et console d'execution admin.</p>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-3 xl:w-[760px] xl:grid-cols-6">
                            <div class="metric-card">
                                <div class="metric-label">Users</div>
                                <div id="metricUsers" class="metric-value">0</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-label">Roles</div>
                                <div id="metricRoles" class="metric-value">0</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-label">Logs</div>
                                <div id="metricLogs" class="metric-value">0</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-label">Admins</div>
                                <div id="metricAdmins" class="metric-value">0</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-label">Devs</div>
                                <div id="metricDevelopers" class="metric-value">0</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-label">API Keys</div>
                                <div id="metricApiKeys" class="metric-value">0</div>
                            </div>
                        </div>
                    </div>
                </header>

                <main>
                    <section id="view-dashboard" class="view-section grid gap-6" data-view="dashboard">
                        <div class="grid gap-4 lg:grid-cols-[1.15fr,0.85fr] auth-private-block">
                            <div class="panel p-5 sm:p-6">
                                <div class="section-head">
                                    <div>
                                        <p class="section-kicker">Administration</p>
                                        <h3 class="section-title">Session admin et gouvernance</h3>
                                    </div>
                                    <div class="flex gap-3"><button id="meButton" class="ghost-button">Verifier ma session</button><button id="logoutButton" type="button" class="secondary-button">Deconnexion</button></div>
                                </div>
                                <div class="mt-5 grid gap-3 lg:grid-cols-3">
                                    <div class="card-subtle">
                                        <div class="subcard-title">JWT admin</div>
                                        <p class="subcard-text">Jeton de travail du back-office.</p>
                                        <pre id="adminTokenPreview" class="token-preview mt-4"></pre>
                                    </div>
                                    <div class="card-subtle">
                                        <div class="subcard-title">JWT utilisateur</div>
                                        <p class="subcard-text">Reference locale si present.</p>
                                        <pre id="userTokenPreview" class="token-preview mt-4"></pre>
                                    </div>
                                    <div class="card-subtle">
                                        <div class="subcard-title">Cle API developpeur</div>
                                        <p class="subcard-text">Derniere cle creee depuis l'admin.</p>
                                        <pre id="apiKeyPreview" class="token-preview mt-4"></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="panel p-5 sm:p-6 auth-session-panel">
                                <div class="section-head">
                                    <div>
                                        <p class="section-kicker">API feedback</p>
                                        <h3 class="section-title">Reponses techniques</h3>
                                    </div>
                                </div>
                                <div class="card-subtle bg-slate-950 text-white mt-5">
                                    <div class="subcard-title text-white">Reponse API</div>
                                    <p class="mt-1 text-sm text-slate-400">Chaque action d'administration alimente ce panneau.</p>
                                    <pre id="responseViewer" class="json-viewer mt-4 bg-white/5 text-slate-100">{}</pre>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-4 auth-private-block">
                            <article class="panel p-5 sm:p-5">
                                <p class="section-kicker">Acces initial</p>
                                <h3 class="section-title text-[1.35rem]">Admin par defaut</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-600">Compte local de bootstrap pour le premier acces admin.</p>
                                <div class="credential-block mt-5">
                                    <div><span class="credential-label">Login</span><strong>admin</strong></div>
                                    <div><span class="credential-label">Mot de passe</span><strong>admin</strong></div>
                                </div>
                            </article>
                            <article class="panel p-5 sm:p-5">
                                <p class="section-kicker">Comptes admin</p>
                                <h3 class="section-title text-[1.35rem]"><span id="dashboardAdminCount">0</span> superviseurs</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-600">Creent d'autres admins et pilotent les roles.</p><button type="button" class="ghost-button mt-5 w-full" data-user-filter-shortcut="admin">Voir les admins</button>
                            </article>
                            <article class="panel p-5 sm:p-5">
                                <p class="section-kicker">Comptes developpeur</p>
                                <h3 class="section-title text-[1.35rem]"><span id="dashboardDeveloperCount">0</span> integrateurs</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-600">Utilises pour les integrations et cles API.</p><button type="button" class="ghost-button mt-5 w-full" data-user-filter-shortcut="developer">Voir les developpeurs</button>
                            </article>
                            <article class="panel p-5 sm:p-5">
                                <p class="section-kicker">Acces machine</p>
                                <h3 class="section-title text-[1.35rem]"><span id="dashboardApiKeysCount">0</span> cles actives</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-600">Inventaire et revocation instantanee.</p><button type="button" class="ghost-button mt-5 w-full" data-route-shortcut="dashboard-api-keys">Ouvrir les cles API</button>
                            </article>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-[0.95fr,1.05fr] auth-private-block">
                            <div class="panel p-5 sm:p-6">
                                <div class="section-head">
                                    <div>
                                        <p class="section-kicker">Developer access</p>
                                        <h3 class="section-title">Generer une cle API</h3>
                                    </div>
                                </div>
                                <form id="createApiKeyForm" class="mt-5 space-y-3"><input name="name" placeholder="Nom visible de la cle API" class="input" required><select id="apiKeyUserSelect" name="user_id" class="input">
                                        <option value="">Utilisateur connecte</option>
                                    </select><input name="expires_in_days" type="number" min="1" max="365" placeholder="Expiration en jours (optionnel)" class="input"><button class="primary-button w-full">Creer la cle API</button></form>
                            </div>
                            <div class="panel p-5 sm:p-6">
                                <div class="section-head">
                                    <div>
                                        <p class="section-kicker">API key inventory</p>
                                        <h3 class="section-title">Cles API actives</h3>
                                    </div><button id="refreshApiKeysButton" class="ghost-button">Rafraichir</button>
                                </div>
                                <div id="apiKeysList" class="mt-5 grid gap-3"></div>
                            </div>
                        </div>

                        <div class="panel p-5 sm:p-6 auth-private-block">
                            <div class="section-head">
                                <div>
                                    <p class="section-kicker">Endpoint explorer</p>
                                    <h3 class="section-title">Console de test pour tous les endpoints</h3>
                                </div><button id="runSelectedEndpoint" class="ghost-button">Executer</button>
                            </div>
                            <div class="mt-5 grid gap-4 xl:grid-cols-[280px,minmax(0,1fr)]">
                                <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-3">
                                    <div id="endpointList" class="grid gap-3"></div>
                                </div>
                                <form id="explorerForm" class="grid gap-3">
                                    <div class="grid gap-3 lg:grid-cols-[110px,1fr,160px]"><input id="explorerMethod" class="input font-semibold uppercase" readonly><input id="explorerPath" class="input" readonly><select id="explorerAuth" class="input">
                                            <option value="none">Sans auth</option>
                                            <option value="user">Token utilisateur</option>
                                            <option value="admin">Token admin</option>
                                            <option value="apiKey">Cle API</option>
                                        </select></div><textarea id="explorerBody" rows="10" class="input font-mono text-sm resize-y" spellcheck="false"></textarea>
                                    <div class="flex flex-wrap gap-3"><button class="primary-button">Executer cet endpoint</button><button id="resetExplorerBody" type="button" class="secondary-button">Recharger le modele</button></div>
                                </form>
                            </div>
                        </div>
                    </section>

                    <section id="view-users" class="view-section hidden grid gap-4" data-view="users">
                        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr),330px]">
                            <div class="panel p-5 sm:p-6">
                                <div class="section-head">
                                    <div>
                                        <p class="section-kicker">Users workspace</p>
                                        <h3 class="section-title">Recherche, filtres et edition inline</h3>
                                    </div><button id="refreshUsersButton" class="ghost-button">Rafraichir</button>
                                </div>
                                <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr),160px,160px,160px,120px]"><input id="usersSearchInput" class="input" placeholder="Rechercher par nom, email ou username"><select id="usersStatusFilter" class="input">
                                        <option value="all">Tous les statuts</option>
                                        <option value="active">active</option>
                                        <option value="inactive">inactive</option>
                                        <option value="blocked">blocked</option>
                                        <option value="pending">pending</option>
                                    </select><select id="usersAccountTypeFilter" class="input">
                                        <option value="all">Tous les comptes</option>
                                        <option value="admin">Admins</option>
                                        <option value="developer">Developpeurs</option>
                                        <option value="no-role">Sans role</option>
                                    </select><select id="usersRoleFilter" class="input">
                                        <option value="all">Tous les roles</option>
                                    </select><select id="usersPageSize" class="input">
                                        <option value="5">5 / page</option>
                                        <option value="10" selected>10 / page</option>
                                        <option value="20">20 / page</option>
                                    </select></div>
                                <div class="mt-6 overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-[0.18em] text-slate-500">
                                                <tr>
                                                    <th class="px-5 py-4">Utilisateur</th>
                                                    <th class="px-5 py-4">Type compte</th>
                                                    <th class="px-5 py-4">Statut</th>
                                                    <th class="px-5 py-4">Roles</th>
                                                    <th class="px-5 py-4">Derniere connexion</th>
                                                    <th class="px-5 py-4 text-right">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="usersTableBody" class="divide-y divide-slate-100 bg-white"></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div id="usersPageInfo" class="text-sm text-slate-500"></div>
                                    <div class="flex gap-3"><button id="usersPrevPage" type="button" class="secondary-button">Precedent</button><button id="usersNextPage" type="button" class="secondary-button">Suivant</button></div>
                                </div>
                            </div>
                            <div class="panel p-5 sm:p-6">
                                <div class="section-head">
                                    <div>
                                        <p class="section-kicker">Account studio</p>
                                        <h3 class="section-title">Creer un admin ou un developpeur</h3>
                                    </div>
                                </div>
                                <form id="createUserForm" class="mt-5 space-y-3">
                                    <div class="grid gap-3 sm:grid-cols-2"><button type="button" class="secondary-button w-full" data-user-preset="admin">Preset admin</button><button type="button" class="ghost-button w-full" data-user-preset="developer">Preset developpeur</button></div>
                                    <div id="createUserPresetHint" class="account-preset-hint">Choisis un preset pour preparer les bons roles et la bonne experience de creation.</div><input name="username" placeholder="Username" class="input" required><input name="email" type="email" placeholder="Email" class="input" required>
                                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1"><input name="first_name" placeholder="Prenom" class="input" autocomplete="given-name"><input name="last_name" placeholder="Nom" class="input" autocomplete="family-name"></div><input name="phone" placeholder="Telephone" class="input" autocomplete="tel"><select name="status" class="input">
                                        <option value="active">active</option>
                                        <option value="inactive">inactive</option>
                                        <option value="blocked">blocked</option>
                                        <option value="pending">pending</option>
                                    </select><input name="password" type="password" placeholder="Mot de passe" class="input" autocomplete="new-password" required>
                                    <div><label class="mb-2 block text-sm font-medium text-slate-700">Roles</label>
                                        <div id="userRolesOptions" class="grid gap-2"></div>
                                    </div>
                                    <div id="developerAccessFields" class="card-subtle hidden">
                                        <div>
                                            <div class="subcard-title">Acces developpeur</div>
                                            <p class="subcard-text">Tu peux creer une cle API des la creation du compte developpeur.</p>
                                        </div><label class="mt-4 inline-flex items-center gap-2 text-sm text-slate-600"><input id="createUserApiKeyToggle" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-accent focus:ring-accent">Generer automatiquement une cle API</label>
                                        <div class="mt-4 grid gap-4"><input id="createUserApiKeyName" placeholder="Nom visible de la cle API" class="input" value="Cle developpeur initiale"><input id="createUserApiKeyDays" type="number" min="1" max="365" placeholder="Expiration en jours" class="input" value="30"></div>
                                    </div><button class="primary-button w-full">Creer l'utilisateur</button>
                                </form>
                            </div>
                        </div>
                    </section>

                    <section id="view-roles" class="view-section hidden grid gap-4" data-view="roles">
                        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr),350px]">
                            <div class="panel p-5 sm:p-6">
                                <div class="section-head">
                                    <div>
                                        <p class="section-kicker">Roles studio</p>
                                        <h3 class="section-title">Edition inline des roles et permissions</h3>
                                    </div><button id="refreshRolesButton" class="ghost-button">Rafraichir</button>
                                </div>
                                <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr),130px]"><input id="rolesSearchInput" class="input" placeholder="Rechercher par nom, code ou description"><select id="rolesPageSize" class="input">
                                        <option value="4">4 / page</option>
                                        <option value="6" selected>6 / page</option>
                                        <option value="10">10 / page</option>
                                    </select></div>
                                <div id="rolesGrid" class="mt-6 grid gap-4 xl:grid-cols-2"></div>
                                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div id="rolesPageInfo" class="text-sm text-slate-500"></div>
                                    <div class="flex gap-3"><button id="rolesPrevPage" type="button" class="secondary-button">Precedent</button><button id="rolesNextPage" type="button" class="secondary-button">Suivant</button></div>
                                </div>
                            </div>
                            <div class="panel p-5 sm:p-6">
                                <div class="section-head">
                                    <div>
                                        <p class="section-kicker">Create role</p>
                                        <h3 class="section-title">Ajouter un role</h3>
                                    </div>
                                </div>
                                <form id="createRoleForm" class="mt-5 space-y-3"><input name="name" placeholder="Nom du role" class="input" required><input name="code" placeholder="Code du role" class="input" required><textarea name="description" rows="3" placeholder="Description" class="input resize-none"></textarea>
                                    <div><label class="mb-2 block text-sm font-medium text-slate-700">Permissions</label>
                                        <div id="permissionsOptions" class="grid gap-2"></div>
                                    </div><button class="primary-button w-full">Creer le role</button>
                                </form>
                            </div>
                        </div>
                    </section>

                    <section id="view-logs" class="view-section hidden grid gap-4" data-view="logs">
                        <div class="panel p-5 sm:p-6">
                            <div class="section-head">
                                <div>
                                    <p class="section-kicker">Audit trail</p>
                                    <h3 class="section-title">Recherche, filtres et pagination sur les logs</h3>
                                </div><button id="refreshLogsButton" class="ghost-button">Rafraichir</button>
                            </div>
                            <div class="mt-5 grid gap-3 xl:grid-cols-[minmax(0,1fr),200px,140px,140px]"><input id="logsSearchInput" class="input" placeholder="Rechercher par event, message, user, email ou IP"><select id="logsEventFilter" class="input">
                                    <option value="all">Tous les evenements</option>
                                </select><select id="logsFetchLimit" class="input">
                                    <option value="50">50 charges</option>
                                    <option value="100" selected>100 charges</option>
                                    <option value="200">200 charges</option>
                                    <option value="500">500 charges</option>
                                </select><select id="logsPageSize" class="input">
                                    <option value="5">5 / page</option>
                                    <option value="10" selected>10 / page</option>
                                    <option value="20">20 / page</option>
                                </select></div>
                            <div id="logsTimeline" class="mt-5 space-y-3"></div>
                            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div id="logsPageInfo" class="text-sm text-slate-500"></div>
                                <div class="flex gap-3"><button id="logsPrevPage" type="button" class="secondary-button">Precedent</button><button id="logsNextPage" type="button" class="secondary-button">Suivant</button></div>
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </div>

    <div id="confirmModal" class="modal-overlay hidden">
        <div class="modal-card">
            <div class="text-xs uppercase tracking-[0.22em] text-slate-400">Confirmation</div>
            <h3 id="confirmModalTitle" class="mt-3 text-2xl font-semibold text-slate-950">Confirmer l'action</h3>
            <p id="confirmModalMessage" class="mt-3 text-sm leading-6 text-slate-600"></p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-end"><button id="confirmModalCancel" type="button" class="secondary-button">Annuler</button><button id="confirmModalSubmit" type="button" class="danger-button">Confirmer</button></div>
        </div>
    </div>

    <div id="toastStack" class="toast-stack" aria-live="polite" aria-atomic="true"></div>

    <script>
        window.BANAMUR_DASHBOARD = {
            page: 'admin',
            apiBaseUrl: <?php echo json_encode($apiBaseUrl, JSON_UNESCAPED_SLASHES); ?>,
            loginUrl: <?php echo json_encode($loginUrl, JSON_UNESCAPED_SLASHES); ?>,
            adminUrl: <?php echo json_encode($adminUrl, JSON_UNESCAPED_SLASHES); ?>,
            bootAdminToken: <?php echo json_encode($adminToken, JSON_UNESCAPED_SLASHES); ?>,
            bootAdminUser: <?php echo json_encode($identity['user'] ?? null, JSON_UNESCAPED_SLASHES); ?>
        };
    </script>
    <script src="<?php echo htmlspecialchars($assetBaseUrl . '/js/admin-dashboard.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>

</html>