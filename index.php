<?php

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$apiBaseUrl = ($basePath === '' ? '' : $basePath) . '/api';
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
                    },
                    boxShadow: {
                        card: '0 20px 60px rgba(15, 23, 42, 0.08)',
                        float: '0 30px 80px rgba(37, 99, 235, 0.18)'
                    }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-dashboard.css">
</head>

<body class="min-h-screen bg-cloud text-ink font-sans antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-16 top-0 h-80 w-80 rounded-full bg-accent/10 blur-3xl"></div>
        <div class="absolute right-0 top-24 h-72 w-72 rounded-full bg-cyan-200/40 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-emerald-100/60 blur-3xl"></div>
    </div>
    <div class="relative min-h-screen px-4 py-4 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-[1600px] gap-6 xl:grid-cols-[300px,minmax(0,1fr)]">
            <aside class="panel flex h-full flex-col p-5 lg:p-6">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                        Banamur Auth
                    </div>
                    <h1 class="mt-5 text-3xl font-semibold tracking-tight text-slate-950">
                        Mini back-office API
                    </h1>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Navigation claire, edition inline, confirmations modales et console API dans une meme interface.
                    </p>
                </div>

                <nav class="mt-8 grid gap-2" id="primaryNav">
                    <button type="button" class="nav-item" data-route="dashboard">
                        <span class="nav-title">Dashboard</span>
                        <span class="nav-caption">Vue globale, auth et explorer</span>
                    </button>
                    <button type="button" class="nav-item" data-route="users">
                        <span class="nav-title">Users</span>
                        <span class="nav-caption">Recherche, edition inline et pagination</span>
                    </button>
                    <button type="button" class="nav-item" data-route="roles">
                        <span class="nav-title">Roles</span>
                        <span class="nav-caption">Permissions et gestion rapide</span>
                    </button>
                    <button type="button" class="nav-item" data-route="logs">
                        <span class="nav-title">Logs</span>
                        <span class="nav-caption">Audit, filtres et timeline paginee</span>
                    </button>
                </nav>

                <div class="mt-8 space-y-4">
                    <div class="card-subtle">
                        <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Base API</div>
                        <div id="apiBaseUrlLabel" class="mt-2 text-sm font-medium text-slate-900"></div>
                    </div>
                    <div class="card-subtle bg-slate-950 text-white">
                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Session</div>
                        <div id="sessionStatus" class="mt-2 text-sm font-medium text-white">Non connecte</div>
                        <div class="mt-4 grid gap-2 text-xs text-slate-400">
                            <div class="truncate">User: <span id="sidebarUserToken"></span></div>
                            <div class="truncate">Admin: <span id="sidebarAdminToken"></span></div>
                        </div>
                    </div>
                </div>

                <div class="mt-auto rounded-[28px] border border-slate-200 bg-gradient-to-br from-accentSoft via-white to-mint p-5 shadow-card">
                    <div class="text-xs uppercase tracking-[0.24em] text-slate-500">Design</div>
                    <p class="mt-3 text-sm leading-6 text-slate-700">
                        Minimalisme clair, surfaces profondes, navigation type produit et feedbacks immediats pour administrer l'API plus vite.
                    </p>
                </div>
            </aside>

            <div class="grid gap-6">
                <header class="panel overflow-hidden px-6 py-6 sm:px-8">
                    <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <div class="text-xs uppercase tracking-[0.24em] text-slate-400">Back office</div>
                            <h2 id="currentViewTitle" class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">
                                Dashboard
                            </h2>
                            <p id="currentViewDescription" class="mt-3 max-w-3xl text-base leading-7 text-slate-600">
                                Vue globale de l'API, authentification visuelle et console d'execution de routes.
                            </p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3 xl:w-[520px]">
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
                        </div>
                    </div>
                </header>

                <main>
                    <section id="view-dashboard" class="view-section grid gap-6" data-view="dashboard">
                        <div class="grid gap-6 lg:grid-cols-[1.05fr,0.95fr]">
                            <div class="panel p-6 sm:p-8">
                                <div class="section-head">
                                    <div>
                                        <p class="section-kicker">Authentication</p>
                                        <h3 class="section-title">Connexion, inscription et session</h3>
                                    </div>
                                    <button id="meButton" class="ghost-button">Verifier ma session</button>
                                </div>
                                <div class="mt-8 grid gap-6 md:grid-cols-2">
                                    <form id="registerForm" class="card-subtle space-y-4">
                                        <div>
                                            <h4 class="subcard-title">Creer un compte</h4>
                                            <p class="subcard-text">Appelle POST /auth/register avec un vrai body JSON.</p>
                                        </div>
                                        <input name="username" placeholder="username" class="input" required>
                                        <input name="email" type="email" placeholder="email@example.com" class="input" required>
                                        <input name="password" type="password" placeholder="Mot de passe" class="input" required>
                                        <input name="first_name" placeholder="Prenom" class="input">
                                        <input name="last_name" placeholder="Nom" class="input">
                                        <input name="phone" placeholder="Telephone" class="input">
                                        <button class="primary-button w-full">Inscrire</button>
                                    </form>

                                    <form id="loginForm" class="card-subtle space-y-4">
                                        <div>
                                            <h4 class="subcard-title">Se connecter</h4>
                                            <p class="subcard-text">Le token est conserve localement pour les vues Users, Roles et Logs.</p>
                                        </div>
                                        <input name="identifier" placeholder="Email ou username" class="input" required>
                                        <input name="password" type="password" placeholder="Mot de passe" class="input" required>
                                        <label class="inline-flex items-center gap-2 text-sm text-slate-500">
                                            <input id="useAdminToken" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-accent focus:ring-accent">
                                            Sauvegarder comme token admin
                                        </label>
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <button class="primary-button">Connexion</button>
                                            <button id="logoutButton" type="button" class="secondary-button">Deconnexion</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="panel p-6 sm:p-8">
                                <div class="section-head">
                                    <div>
                                        <p class="section-kicker">Session state</p>
                                        <h3 class="section-title">Tokens et reponses</h3>
                                    </div>
                                </div>
                                <div class="mt-8 space-y-4">
                                    <div class="card-subtle">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <div class="subcard-title">Token utilisateur</div>
                                                <p class="subcard-text">Utilise pour /auth/me et pour les tests utilisateur.</p>
                                            </div>
                                            <button data-copy-target="userTokenPreview" class="chip-button">Copier</button>
                                        </div>
                                        <pre id="userTokenPreview" class="token-preview mt-4"></pre>
                                    </div>
                                    <div class="card-subtle">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <div class="subcard-title">Token admin</div>
                                                <p class="subcard-text">Utilise pour le back-office complet.</p>
                                            </div>
                                            <button data-copy-target="adminTokenPreview" class="chip-button">Copier</button>
                                        </div>
                                        <pre id="adminTokenPreview" class="token-preview mt-4"></pre>
                                    </div>
                                    <div class="card-subtle bg-slate-950 text-white">
                                        <div class="subcard-title text-white">Reponse API</div>
                                        <p class="mt-1 text-sm text-slate-400">Toutes les actions du back-office alimentent ce panneau.</p>
                                        <pre id="responseViewer" class="json-viewer mt-4 bg-white/5 text-slate-100">{}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="panel p-6 sm:p-8">
                            <div class="section-head">
                                <div>
                                    <p class="section-kicker">Endpoint explorer</p>
                                    <h3 class="section-title">Console de test pour tous les endpoints</h3>
                                </div>
                                <button id="runSelectedEndpoint" class="ghost-button">Executer</button>
                            </div>
                            <div class="mt-8 grid gap-6 xl:grid-cols-[320px,minmax(0,1fr)]">
                                <div class="rounded-[28px] border border-slate-200 bg-slate-50 p-4">
                                    <div id="endpointList" class="grid gap-3"></div>
                                </div>
                                <form id="explorerForm" class="grid gap-4">
                                    <div class="grid gap-4 lg:grid-cols-[120px,1fr,180px]">
                                        <input id="explorerMethod" class="input font-semibold uppercase" readonly>
                                        <input id="explorerPath" class="input" readonly>
                                        <select id="explorerAuth" class="input">
                                            <option value="none">Sans auth</option>
                                            <option value="user">Token utilisateur</option>
                                            <option value="admin">Token admin</option>
                                        </select>
                                    </div>
                                    <textarea id="explorerBody" rows="16" class="input font-mono text-sm resize-y" spellcheck="false"></textarea>
                                    <div class="flex flex-wrap gap-3">
                                        <button class="primary-button">Executer cet endpoint</button>
                                        <button id="resetExplorerBody" type="button" class="secondary-button">Recharger le modele</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>

                    <section id="view-users" class="view-section hidden grid gap-6" data-view="users">
                        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr),360px]">
                            <div class="panel p-6 sm:p-8">
                                <div class="section-head">
                                    <div>
                                        <p class="section-kicker">Users workspace</p>
                                        <h3 class="section-title">Recherche, filtres et edition inline</h3>
                                    </div>
                                    <button id="refreshUsersButton" class="ghost-button">Rafraichir</button>
                                </div>

                                <div class="mt-8 grid gap-4 lg:grid-cols-[minmax(0,1fr),180px,180px,140px]">
                                    <input id="usersSearchInput" class="input" placeholder="Rechercher par nom, email ou username">
                                    <select id="usersStatusFilter" class="input">
                                        <option value="all">Tous les statuts</option>
                                        <option value="active">active</option>
                                        <option value="inactive">inactive</option>
                                        <option value="blocked">blocked</option>
                                        <option value="pending">pending</option>
                                    </select>
                                    <select id="usersRoleFilter" class="input">
                                        <option value="all">Tous les roles</option>
                                    </select>
                                    <select id="usersPageSize" class="input">
                                        <option value="5">5 / page</option>
                                        <option value="10" selected>10 / page</option>
                                        <option value="20">20 / page</option>
                                    </select>
                                </div>

                                <div class="mt-6 overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-[0.18em] text-slate-500">
                                                <tr>
                                                    <th class="px-5 py-4">Utilisateur</th>
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
                                    <div class="flex gap-3">
                                        <button id="usersPrevPage" type="button" class="secondary-button">Precedent</button>
                                        <button id="usersNextPage" type="button" class="secondary-button">Suivant</button>
                                    </div>
                                </div>
                            </div>

                            <div class="panel p-6 sm:p-8">
                                <div class="section-head">
                                    <div>
                                        <p class="section-kicker">Create user</p>
                                        <h3 class="section-title">Ajouter un utilisateur</h3>
                                    </div>
                                </div>
                                <form id="createUserForm" class="mt-8 space-y-4">
                                    <input name="username" placeholder="Username" class="input" required>
                                    <input name="email" type="email" placeholder="Email" class="input" required>
                                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                                        <input name="first_name" placeholder="Prenom" class="input">
                                        <input name="last_name" placeholder="Nom" class="input">
                                    </div>
                                    <input name="phone" placeholder="Telephone" class="input">
                                    <select name="status" class="input">
                                        <option value="active">active</option>
                                        <option value="inactive">inactive</option>
                                        <option value="blocked">blocked</option>
                                        <option value="pending">pending</option>
                                    </select>
                                    <input name="password" type="password" placeholder="Mot de passe" class="input" required>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700">Roles</label>
                                        <div id="userRolesOptions" class="grid gap-2"></div>
                                    </div>
                                    <button class="primary-button w-full">Creer l'utilisateur</button>
                                </form>
                            </div>
                        </div>
                    </section>

                    <section id="view-roles" class="view-section hidden grid gap-6" data-view="roles">
                        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr),380px]">
                            <div class="panel p-6 sm:p-8">
                                <div class="section-head">
                                    <div>
                                        <p class="section-kicker">Roles studio</p>
                                        <h3 class="section-title">Edition inline des roles et permissions</h3>
                                    </div>
                                    <button id="refreshRolesButton" class="ghost-button">Rafraichir</button>
                                </div>

                                <div class="mt-8 grid gap-4 lg:grid-cols-[minmax(0,1fr),150px]">
                                    <input id="rolesSearchInput" class="input" placeholder="Rechercher par nom, code ou description">
                                    <select id="rolesPageSize" class="input">
                                        <option value="4">4 / page</option>
                                        <option value="6" selected>6 / page</option>
                                        <option value="10">10 / page</option>
                                    </select>
                                </div>

                                <div id="rolesGrid" class="mt-6 grid gap-4 xl:grid-cols-2"></div>

                                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div id="rolesPageInfo" class="text-sm text-slate-500"></div>
                                    <div class="flex gap-3">
                                        <button id="rolesPrevPage" type="button" class="secondary-button">Precedent</button>
                                        <button id="rolesNextPage" type="button" class="secondary-button">Suivant</button>
                                    </div>
                                </div>
                            </div>

                            <div class="panel p-6 sm:p-8">
                                <div class="section-head">
                                    <div>
                                        <p class="section-kicker">Create role</p>
                                        <h3 class="section-title">Ajouter un role</h3>
                                    </div>
                                </div>
                                <form id="createRoleForm" class="mt-8 space-y-4">
                                    <input name="name" placeholder="Nom du role" class="input" required>
                                    <input name="code" placeholder="Code du role" class="input" required>
                                    <textarea name="description" rows="4" placeholder="Description" class="input resize-none"></textarea>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700">Permissions</label>
                                        <div id="permissionsOptions" class="grid gap-2"></div>
                                    </div>
                                    <button class="primary-button w-full">Creer le role</button>
                                </form>
                            </div>
                        </div>
                    </section>

                    <section id="view-logs" class="view-section hidden grid gap-6" data-view="logs">
                        <div class="panel p-6 sm:p-8">
                            <div class="section-head">
                                <div>
                                    <p class="section-kicker">Audit trail</p>
                                    <h3 class="section-title">Recherche, filtres et pagination sur les logs</h3>
                                </div>
                                <button id="refreshLogsButton" class="ghost-button">Rafraichir</button>
                            </div>

                            <div class="mt-8 grid gap-4 xl:grid-cols-[minmax(0,1fr),220px,160px,160px]">
                                <input id="logsSearchInput" class="input" placeholder="Rechercher par event, message, user, email ou IP">
                                <select id="logsEventFilter" class="input">
                                    <option value="all">Tous les evenements</option>
                                </select>
                                <select id="logsFetchLimit" class="input">
                                    <option value="50">50 charges</option>
                                    <option value="100" selected>100 charges</option>
                                    <option value="200">200 charges</option>
                                    <option value="500">500 charges</option>
                                </select>
                                <select id="logsPageSize" class="input">
                                    <option value="5">5 / page</option>
                                    <option value="10" selected>10 / page</option>
                                    <option value="20">20 / page</option>
                                </select>
                            </div>

                            <div id="logsTimeline" class="mt-6 space-y-4"></div>

                            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div id="logsPageInfo" class="text-sm text-slate-500"></div>
                                <div class="flex gap-3">
                                    <button id="logsPrevPage" type="button" class="secondary-button">Precedent</button>
                                    <button id="logsNextPage" type="button" class="secondary-button">Suivant</button>
                                </div>
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
            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button id="confirmModalCancel" type="button" class="secondary-button">Annuler</button>
                <button id="confirmModalSubmit" type="button" class="danger-button">Confirmer</button>
            </div>
        </div>
    </div>

    <script>
        window.BANAMUR_DASHBOARD = {
            apiBaseUrl: <?php echo json_encode($apiBaseUrl, JSON_UNESCAPED_SLASHES); ?>
        };
    </script>
    <script src="js/admin-dashboard.js"></script>
</body>

</html>