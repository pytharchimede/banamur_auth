(function () {
  const viewMeta = {
    dashboard: {
      title: "Dashboard",
      description:
        "Vue globale de l'API, authentification visuelle et console d'execution de routes.",
    },
    users: {
      title: "Users",
      description:
        "Liste complete avec recherche, filtres, pagination et edition inline directement dans le tableau.",
    },
    roles: {
      title: "Roles",
      description:
        "Gestion des roles et permissions avec creation rapide et edition inline des cartes metier.",
    },
    logs: {
      title: "Logs",
      description:
        "Audit applicatif avec filtres multicriteres, chargement progressif et pagination par page.",
    },
  };

  const state = {
    apiBaseUrl: window.BANAMUR_DASHBOARD.apiBaseUrl,
    route: "dashboard",
    users: [],
    roles: [],
    permissions: [],
    logs: [],
    explorerSelection: null,
    editingUserId: null,
    editingRoleId: null,
    confirmAction: null,
    usersUI: {
      search: "",
      status: "all",
      role: "all",
      page: 1,
      pageSize: 10,
    },
    rolesUI: {
      search: "",
      page: 1,
      pageSize: 6,
    },
    logsUI: {
      search: "",
      event: "all",
      page: 1,
      pageSize: 10,
      fetchLimit: 100,
    },
    endpointCatalog: [
      endpoint("health", "Health", "GET", "/health", "none"),
      endpoint(
        "register",
        "Register",
        "POST",
        "/auth/register",
        "none",
        jsonTemplate({
          username: "alice",
          email: "alice@example.com",
          password: "motdepasse123",
          first_name: "Alice",
          last_name: "Banamur",
        }),
      ),
      endpoint(
        "login",
        "Login",
        "POST",
        "/auth/login",
        "none",
        jsonTemplate({
          identifier: "alice@example.com",
          password: "motdepasse123",
        }),
      ),
      endpoint("me", "Me", "GET", "/auth/me", "user"),
      endpoint("logout", "Logout", "POST", "/auth/logout", "user"),
      endpoint("logs", "Logs", "GET", "/logs?limit=100", "admin"),
      endpoint("users", "List Users", "GET", "/users", "admin"),
      endpoint("userShow", "Show User", "GET", "/users/1", "admin"),
      endpoint(
        "userCreate",
        "Create User",
        "POST",
        "/users",
        "admin",
        jsonTemplate({
          username: "bob",
          email: "bob@example.com",
          password: "motdepasse123",
          first_name: "Bob",
          last_name: "User",
          status: "active",
          role_codes: ["USER"],
        }),
      ),
      endpoint(
        "userUpdate",
        "Update User",
        "PUT",
        "/users/1",
        "admin",
        jsonTemplate({
          first_name: "Bob Updated",
          last_name: "User Updated",
          status: "active",
          role_codes: ["USER"],
        }),
      ),
      endpoint(
        "userRoles",
        "Sync User Roles",
        "PUT",
        "/users/1/roles",
        "admin",
        jsonTemplate({
          role_codes: ["USER", "ADMIN"],
        }),
      ),
      endpoint("userDelete", "Delete User", "DELETE", "/users/1", "admin"),
      endpoint("roles", "List Roles", "GET", "/roles", "admin"),
      endpoint("roleShow", "Show Role", "GET", "/roles/1", "admin"),
      endpoint(
        "roleCreate",
        "Create Role",
        "POST",
        "/roles",
        "admin",
        jsonTemplate({
          name: "Manager",
          code: "MANAGER",
          description: "Role de demonstration",
          permission_codes: ["user.read", "role.read"],
        }),
      ),
      endpoint(
        "roleUpdate",
        "Update Role",
        "PUT",
        "/roles/1",
        "admin",
        jsonTemplate({
          name: "Manager Updated",
          code: "MANAGER",
          description: "Role mis a jour",
          permission_codes: ["user.read", "user.update", "role.read"],
        }),
      ),
      endpoint(
        "permissions",
        "List Permissions",
        "GET",
        "/permissions",
        "admin",
      ),
      endpoint(
        "rolePermissions",
        "Sync Role Permissions",
        "PUT",
        "/roles/1/permissions",
        "admin",
        jsonTemplate({
          permission_codes: ["user.read", "user.update", "role.read"],
        }),
      ),
      endpoint("roleDelete", "Delete Role", "DELETE", "/roles/1", "admin"),
    ],
  };

  const elements = {
    apiBaseUrlLabel: document.getElementById("apiBaseUrlLabel"),
    sessionStatus: document.getElementById("sessionStatus"),
    sidebarUserToken: document.getElementById("sidebarUserToken"),
    sidebarAdminToken: document.getElementById("sidebarAdminToken"),
    currentViewTitle: document.getElementById("currentViewTitle"),
    currentViewDescription: document.getElementById("currentViewDescription"),
    metricUsers: document.getElementById("metricUsers"),
    metricRoles: document.getElementById("metricRoles"),
    metricLogs: document.getElementById("metricLogs"),
    responseViewer: document.getElementById("responseViewer"),
    userTokenPreview: document.getElementById("userTokenPreview"),
    adminTokenPreview: document.getElementById("adminTokenPreview"),
    usersTableBody: document.getElementById("usersTableBody"),
    usersSearchInput: document.getElementById("usersSearchInput"),
    usersStatusFilter: document.getElementById("usersStatusFilter"),
    usersRoleFilter: document.getElementById("usersRoleFilter"),
    usersPageSize: document.getElementById("usersPageSize"),
    usersPageInfo: document.getElementById("usersPageInfo"),
    usersPrevPage: document.getElementById("usersPrevPage"),
    usersNextPage: document.getElementById("usersNextPage"),
    createUserForm: document.getElementById("createUserForm"),
    userRolesOptions: document.getElementById("userRolesOptions"),
    rolesGrid: document.getElementById("rolesGrid"),
    rolesSearchInput: document.getElementById("rolesSearchInput"),
    rolesPageSize: document.getElementById("rolesPageSize"),
    rolesPageInfo: document.getElementById("rolesPageInfo"),
    rolesPrevPage: document.getElementById("rolesPrevPage"),
    rolesNextPage: document.getElementById("rolesNextPage"),
    createRoleForm: document.getElementById("createRoleForm"),
    permissionsOptions: document.getElementById("permissionsOptions"),
    logsTimeline: document.getElementById("logsTimeline"),
    logsSearchInput: document.getElementById("logsSearchInput"),
    logsEventFilter: document.getElementById("logsEventFilter"),
    logsFetchLimit: document.getElementById("logsFetchLimit"),
    logsPageSize: document.getElementById("logsPageSize"),
    logsPageInfo: document.getElementById("logsPageInfo"),
    logsPrevPage: document.getElementById("logsPrevPage"),
    logsNextPage: document.getElementById("logsNextPage"),
    endpointList: document.getElementById("endpointList"),
    explorerMethod: document.getElementById("explorerMethod"),
    explorerPath: document.getElementById("explorerPath"),
    explorerAuth: document.getElementById("explorerAuth"),
    explorerBody: document.getElementById("explorerBody"),
    confirmModal: document.getElementById("confirmModal"),
    confirmModalTitle: document.getElementById("confirmModalTitle"),
    confirmModalMessage: document.getElementById("confirmModalMessage"),
    confirmModalCancel: document.getElementById("confirmModalCancel"),
    confirmModalSubmit: document.getElementById("confirmModalSubmit"),
  };

  function init() {
    elements.apiBaseUrlLabel.textContent = state.apiBaseUrl;
    bindActions();
    renderTokens();
    renderEndpointCatalog();
    selectEndpoint(state.endpointCatalog[0].key);
    syncRouteFromHash();
    refreshReferenceData();
  }

  function bindActions() {
    window.addEventListener("hashchange", syncRouteFromHash);

    document.querySelectorAll("[data-route]").forEach(function (button) {
      button.addEventListener("click", function () {
        setRoute(button.getAttribute("data-route"));
      });
    });

    document
      .getElementById("registerForm")
      .addEventListener("submit", handleRegister);
    document
      .getElementById("loginForm")
      .addEventListener("submit", handleLogin);
    document
      .getElementById("logoutButton")
      .addEventListener("click", handleLogout);
    document.getElementById("meButton").addEventListener("click", function () {
      apiCall("/auth/me", { method: "GET", auth: "user" });
    });

    document
      .getElementById("refreshUsersButton")
      .addEventListener("click", loadUsers);
    elements.createUserForm.addEventListener("submit", handleCreateUser);
    elements.usersSearchInput.addEventListener("input", function (event) {
      state.usersUI.search = event.target.value;
      state.usersUI.page = 1;
      renderUsers();
    });
    elements.usersStatusFilter.addEventListener("change", function (event) {
      state.usersUI.status = event.target.value;
      state.usersUI.page = 1;
      renderUsers();
    });
    elements.usersRoleFilter.addEventListener("change", function (event) {
      state.usersUI.role = event.target.value;
      state.usersUI.page = 1;
      renderUsers();
    });
    elements.usersPageSize.addEventListener("change", function (event) {
      state.usersUI.pageSize = Number(event.target.value);
      state.usersUI.page = 1;
      renderUsers();
    });
    elements.usersPrevPage.addEventListener("click", function () {
      changePage("users", -1);
    });
    elements.usersNextPage.addEventListener("click", function () {
      changePage("users", 1);
    });

    document
      .getElementById("refreshRolesButton")
      .addEventListener("click", loadRoles);
    elements.createRoleForm.addEventListener("submit", handleCreateRole);
    elements.rolesSearchInput.addEventListener("input", function (event) {
      state.rolesUI.search = event.target.value;
      state.rolesUI.page = 1;
      renderRoles();
    });
    elements.rolesPageSize.addEventListener("change", function (event) {
      state.rolesUI.pageSize = Number(event.target.value);
      state.rolesUI.page = 1;
      renderRoles();
    });
    elements.rolesPrevPage.addEventListener("click", function () {
      changePage("roles", -1);
    });
    elements.rolesNextPage.addEventListener("click", function () {
      changePage("roles", 1);
    });

    document
      .getElementById("refreshLogsButton")
      .addEventListener("click", loadLogs);
    elements.logsSearchInput.addEventListener("input", function (event) {
      state.logsUI.search = event.target.value;
      state.logsUI.page = 1;
      renderLogs();
    });
    elements.logsEventFilter.addEventListener("change", function (event) {
      state.logsUI.event = event.target.value;
      state.logsUI.page = 1;
      renderLogs();
    });
    elements.logsFetchLimit.addEventListener("change", function (event) {
      state.logsUI.fetchLimit = Number(event.target.value);
      state.logsUI.page = 1;
      loadLogs();
    });
    elements.logsPageSize.addEventListener("change", function (event) {
      state.logsUI.pageSize = Number(event.target.value);
      state.logsUI.page = 1;
      renderLogs();
    });
    elements.logsPrevPage.addEventListener("click", function () {
      changePage("logs", -1);
    });
    elements.logsNextPage.addEventListener("click", function () {
      changePage("logs", 1);
    });

    document
      .getElementById("explorerForm")
      .addEventListener("submit", handleExplorerRun);
    document
      .getElementById("runSelectedEndpoint")
      .addEventListener("click", function () {
        document.getElementById("explorerForm").requestSubmit();
      });
    document
      .getElementById("resetExplorerBody")
      .addEventListener("click", resetExplorerBody);

    document.querySelectorAll("[data-copy-target]").forEach(function (button) {
      button.addEventListener("click", async function () {
        const target = document.getElementById(
          button.getAttribute("data-copy-target"),
        );

        if (!target) {
          return;
        }

        await navigator.clipboard.writeText(target.textContent || "");
      });
    });

    elements.confirmModalCancel.addEventListener("click", closeConfirmModal);
    elements.confirmModalSubmit.addEventListener("click", confirmModalAction);
    elements.confirmModal.addEventListener("click", function (event) {
      if (event.target === elements.confirmModal) {
        closeConfirmModal();
      }
    });
  }

  function syncRouteFromHash() {
    const hash = window.location.hash.replace("#", "");
    const nextRoute = Object.prototype.hasOwnProperty.call(viewMeta, hash)
      ? hash
      : "dashboard";
    state.route = nextRoute;
    renderRoute();
  }

  function setRoute(route) {
    const nextRoute = Object.prototype.hasOwnProperty.call(viewMeta, route)
      ? route
      : "dashboard";

    if (window.location.hash.replace("#", "") === nextRoute) {
      state.route = nextRoute;
      renderRoute();
      return;
    }

    window.location.hash = nextRoute;
  }

  function renderRoute() {
    document.querySelectorAll("[data-view]").forEach(function (section) {
      section.classList.toggle(
        "hidden",
        section.getAttribute("data-view") !== state.route,
      );
    });

    document.querySelectorAll("[data-route]").forEach(function (button) {
      button.classList.toggle(
        "active",
        button.getAttribute("data-route") === state.route,
      );
    });

    elements.currentViewTitle.textContent = viewMeta[state.route].title;
    elements.currentViewDescription.textContent =
      viewMeta[state.route].description;
  }

  async function refreshReferenceData() {
    await Promise.allSettled([
      loadRoles(),
      loadPermissions(),
      loadUsers(),
      loadLogs(),
    ]);
    renderMetrics();
  }

  async function handleRegister(event) {
    event.preventDefault();
    const payload = formToObject(event.currentTarget);
    const result = await apiCall("/auth/register", {
      method: "POST",
      body: payload,
    });

    if (result && result.success) {
      event.currentTarget.reset();
      await Promise.all([loadUsers(), loadLogs()]);
    }
  }

  async function handleLogin(event) {
    event.preventDefault();
    const payload = formToObject(event.currentTarget);
    const result = await apiCall("/auth/login", {
      method: "POST",
      body: payload,
    });

    if (!result || !result.success || !result.data || !result.data.token) {
      return;
    }

    const tokenKey = document.getElementById("useAdminToken").checked
      ? "banamur_admin_token"
      : "banamur_user_token";
    localStorage.setItem(tokenKey, result.data.token);
    renderTokens();

    if (tokenKey === "banamur_admin_token") {
      await refreshReferenceData();
    }
  }

  async function handleLogout() {
    const authType = localStorage.getItem("banamur_user_token")
      ? "user"
      : localStorage.getItem("banamur_admin_token")
        ? "admin"
        : null;

    if (authType) {
      await apiCall("/auth/logout", { method: "POST", auth: authType });
    }

    localStorage.removeItem("banamur_user_token");
    localStorage.removeItem("banamur_admin_token");
    renderTokens();
    await refreshReferenceData();
  }

  async function loadUsers() {
    const result = await apiCall("/users", {
      method: "GET",
      auth: "admin",
      silent: true,
    });
    state.users =
      result && result.success && Array.isArray(result.data) ? result.data : [];
    normalizePage("users", getFilteredUsers().length);
    renderUsersRoleFilter();
    renderUsers();
    renderMetrics();
  }

  async function loadRoles() {
    const result = await apiCall("/roles", {
      method: "GET",
      auth: "admin",
      silent: true,
    });
    state.roles =
      result && result.success && Array.isArray(result.data) ? result.data : [];
    normalizePage("roles", getFilteredRoles().length);
    renderRoleOptions();
    renderRoles();
    renderMetrics();
  }

  async function loadPermissions() {
    const result = await apiCall("/permissions", {
      method: "GET",
      auth: "admin",
      silent: true,
    });
    state.permissions =
      result && result.success && Array.isArray(result.data) ? result.data : [];
    renderPermissionOptions();
    renderRoles();
    renderMetrics();
  }

  async function loadLogs() {
    const result = await apiCall(
      "/logs?limit=" + encodeURIComponent(String(state.logsUI.fetchLimit)),
      { method: "GET", auth: "admin", silent: true },
    );
    state.logs =
      result && result.success && Array.isArray(result.data) ? result.data : [];
    normalizePage("logs", getFilteredLogs().length);
    renderLogsEventFilter();
    renderLogs();
    renderMetrics();
  }

  async function handleCreateUser(event) {
    event.preventDefault();
    const payload = formToObject(event.currentTarget);
    payload.role_codes = checkedValues(elements.userRolesOptions);

    const result = await apiCall("/users", {
      method: "POST",
      auth: "admin",
      body: payload,
    });

    if (result && result.success) {
      event.currentTarget.reset();
      await Promise.all([loadUsers(), loadLogs()]);
    }
  }

  async function handleCreateRole(event) {
    event.preventDefault();
    const payload = formToObject(event.currentTarget);
    payload.permission_codes = checkedValues(elements.permissionsOptions);

    const result = await apiCall("/roles", {
      method: "POST",
      auth: "admin",
      body: payload,
    });

    if (result && result.success) {
      event.currentTarget.reset();
      await Promise.all([loadRoles(), loadPermissions(), loadLogs()]);
    }
  }

  async function handleExplorerRun(event) {
    event.preventDefault();
    const method = elements.explorerMethod.value;
    const path = elements.explorerPath.value;
    const auth = elements.explorerAuth.value;
    const bodyText = elements.explorerBody.value.trim();
    let body;

    if (bodyText !== "" && method !== "GET" && method !== "DELETE") {
      try {
        body = JSON.parse(bodyText);
      } catch (error) {
        setResponse({
          success: false,
          error: { message: "JSON invalide dans l'explorer." },
        });
        return;
      }
    }

    await apiCall(path, { method: method, auth: auth, body: body });
  }

  function renderTokens() {
    const userToken = getToken("user");
    const adminToken = getToken("admin");
    const userPreview = userToken ? shortenToken(userToken) : "aucun";
    const adminPreview = adminToken ? shortenToken(adminToken) : "aucun";

    elements.userTokenPreview.textContent =
      userToken || "Aucun token utilisateur";
    elements.adminTokenPreview.textContent = adminToken || "Aucun token admin";
    elements.sidebarUserToken.textContent = userPreview;
    elements.sidebarAdminToken.textContent = adminPreview;
    elements.sessionStatus.textContent = adminToken
      ? "Admin connecte"
      : userToken
        ? "Utilisateur connecte"
        : "Non connecte";
  }

  function renderMetrics() {
    elements.metricUsers.textContent = String(state.users.length);
    elements.metricRoles.textContent = String(state.roles.length);
    elements.metricLogs.textContent = String(state.logs.length);
  }

  function renderUsersRoleFilter() {
    const currentValue = state.usersUI.role;
    const options = ['<option value="all">Tous les roles</option>'].concat(
      state.roles.map(function (role) {
        return (
          '<option value="' +
          escapeHtml(role.code) +
          '">' +
          escapeHtml(role.code) +
          "</option>"
        );
      }),
    );
    elements.usersRoleFilter.innerHTML = options.join("");
    elements.usersRoleFilter.value = currentValue;
  }

  function renderRoleOptions() {
    elements.userRolesOptions.innerHTML = state.roles.length
      ? state.roles
          .map(function (role) {
            return checkboxTile(
              "create-user-role-" + role.id,
              role.code,
              role.name || role.code,
            );
          })
          .join("")
      : '<div class="empty-state">Connecte un admin pour charger les roles.</div>';
  }

  function renderPermissionOptions() {
    elements.permissionsOptions.innerHTML = state.permissions.length
      ? state.permissions
          .map(function (permission) {
            return checkboxTile(
              "create-role-permission-" + permission.id,
              permission.code,
              permission.name || permission.code,
            );
          })
          .join("")
      : '<div class="empty-state">Aucune permission chargee.</div>';
  }

  function renderUsers() {
    const filtered = getFilteredUsers();
    const pagination = paginate(
      filtered,
      state.usersUI.page,
      state.usersUI.pageSize,
    );

    if (!filtered.length) {
      elements.usersTableBody.innerHTML =
        '<tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">Aucun utilisateur ne correspond a ces filtres.</td></tr>';
      renderPageInfo(
        elements.usersPageInfo,
        elements.usersPrevPage,
        elements.usersNextPage,
        pagination,
        "utilisateur",
      );
      return;
    }

    elements.usersTableBody.innerHTML = pagination.items
      .map(function (user) {
        return renderUserRow(user);
      })
      .join("");

    bindUsersTableActions();
    renderPageInfo(
      elements.usersPageInfo,
      elements.usersPrevPage,
      elements.usersNextPage,
      pagination,
      "utilisateur",
    );
  }

  function renderUserRow(user) {
    const roles = (user.roles || [])
      .map(function (role) {
        return (
          '<span class="badge badge-soft">' + escapeHtml(role.code) + "</span>"
        );
      })
      .join(" ");

    const baseRow =
      '<tr class="hover:bg-slate-50">' +
      '<td class="px-5 py-4"><div class="font-medium text-slate-900">' +
      escapeHtml(user.first_name || user.username) +
      (user.last_name ? " " + escapeHtml(user.last_name) : "") +
      '</div><div class="text-slate-500">' +
      escapeHtml(user.email) +
      "</div></td>" +
      '<td class="px-5 py-4"><span class="badge ' +
      statusBadgeClass(user.status) +
      '">' +
      escapeHtml(user.status) +
      "</span></td>" +
      '<td class="px-5 py-4">' +
      (roles || '<span class="text-slate-400">Aucun role</span>') +
      "</td>" +
      '<td class="px-5 py-4 text-slate-500">' +
      escapeHtml(user.last_login_at || "Jamais") +
      "</td>" +
      '<td class="px-5 py-4 text-right"><div class="flex justify-end gap-2">' +
      '<button type="button" class="chip-button" data-user-edit="' +
      escapeHtml(String(user.id)) +
      '">Inline edit</button>' +
      '<button type="button" class="chip-button" data-user-delete="' +
      escapeHtml(String(user.id)) +
      '">Supprimer</button></div></td>' +
      "</tr>";

    if (state.editingUserId !== user.id) {
      return baseRow;
    }

    return (
      baseRow +
      '<tr><td colspan="5" class="px-5 pb-5">' +
      renderInlineUserEditor(user) +
      "</td></tr>"
    );
  }

  function renderInlineUserEditor(user) {
    const selectedRoles = new Set(
      (user.roles || []).map(function (role) {
        return role.code;
      }),
    );

    return (
      '<form class="inline-editor inline-grid" data-inline-user-form="' +
      escapeHtml(String(user.id)) +
      '">' +
      '<div class="grid gap-3 lg:grid-cols-2">' +
      '<input class="input" name="username" value="' +
      escapeHtml(user.username || "") +
      '" placeholder="Username" required>' +
      '<input class="input" name="email" value="' +
      escapeHtml(user.email || "") +
      '" placeholder="Email" required>' +
      '<input class="input" name="first_name" value="' +
      escapeHtml(user.first_name || "") +
      '" placeholder="Prenom">' +
      '<input class="input" name="last_name" value="' +
      escapeHtml(user.last_name || "") +
      '" placeholder="Nom">' +
      '<input class="input" name="phone" value="' +
      escapeHtml(user.phone || "") +
      '" placeholder="Telephone">' +
      '<select class="input" name="status">' +
      renderStatusOptions(user.status) +
      "</select>" +
      '</div><input class="input" name="password" type="password" placeholder="Laisser vide pour garder le mot de passe actuel">' +
      '<div><div class="mb-2 text-sm font-medium text-slate-700">Roles</div><div class="pill-grid">' +
      state.roles
        .map(function (role) {
          return checkboxChip(
            "inline-user-" + user.id + "-" + role.id,
            role.code,
            role.code,
            selectedRoles.has(role.code),
          );
        })
        .join("") +
      '</div></div><div class="flex flex-wrap gap-3"><button class="primary-button">Sauvegarder</button><button type="button" class="secondary-button" data-user-cancel="' +
      escapeHtml(String(user.id)) +
      '">Annuler</button></div></form>'
    );
  }

  function bindUsersTableActions() {
    elements.usersTableBody
      .querySelectorAll("[data-user-edit]")
      .forEach(function (button) {
        button.addEventListener("click", function () {
          const id = Number(button.getAttribute("data-user-edit"));
          state.editingUserId = state.editingUserId === id ? null : id;
          renderUsers();
        });
      });

    elements.usersTableBody
      .querySelectorAll("[data-user-delete]")
      .forEach(function (button) {
        button.addEventListener("click", function () {
          const id = Number(button.getAttribute("data-user-delete"));
          const user = state.users.find(function (item) {
            return item.id === id;
          });
          openConfirmModal(
            "Supprimer cet utilisateur ?",
            "Cette action supprimera definitivement " +
              (user ? user.email : "cet utilisateur") +
              ".",
            function () {
              deleteUser(id);
            },
          );
        });
      });

    elements.usersTableBody
      .querySelectorAll("[data-user-cancel]")
      .forEach(function (button) {
        button.addEventListener("click", function () {
          state.editingUserId = null;
          renderUsers();
        });
      });

    elements.usersTableBody
      .querySelectorAll("[data-inline-user-form]")
      .forEach(function (form) {
        form.addEventListener("submit", function (event) {
          event.preventDefault();
          const id = Number(form.getAttribute("data-inline-user-form"));
          updateUser(id, form);
        });
      });
  }

  async function updateUser(id, form) {
    const payload = formToObject(form);
    payload.role_codes = checkedValues(form);
    if (!payload.password) {
      delete payload.password;
    }

    const result = await apiCall("/users/" + encodeURIComponent(String(id)), {
      method: "PUT",
      auth: "admin",
      body: payload,
    });

    if (result && result.success) {
      state.editingUserId = null;
      await Promise.all([loadUsers(), loadLogs()]);
    }
  }

  async function deleteUser(id) {
    const result = await apiCall("/users/" + encodeURIComponent(String(id)), {
      method: "DELETE",
      auth: "admin",
    });

    if (result && result.success) {
      if (state.editingUserId === id) {
        state.editingUserId = null;
      }
      await Promise.all([loadUsers(), loadLogs()]);
    }
  }

  function renderRoles() {
    const filtered = getFilteredRoles();
    const pagination = paginate(
      filtered,
      state.rolesUI.page,
      state.rolesUI.pageSize,
    );

    if (!filtered.length) {
      elements.rolesGrid.innerHTML =
        '<div class="empty-state xl:col-span-2">Aucun role ne correspond a ces filtres.</div>';
      renderPageInfo(
        elements.rolesPageInfo,
        elements.rolesPrevPage,
        elements.rolesNextPage,
        pagination,
        "role",
      );
      return;
    }

    elements.rolesGrid.innerHTML = pagination.items
      .map(function (role) {
        return renderRoleCard(role);
      })
      .join("");

    bindRolesActions();
    renderPageInfo(
      elements.rolesPageInfo,
      elements.rolesPrevPage,
      elements.rolesNextPage,
      pagination,
      "role",
    );
  }

  function renderRoleCard(role) {
    const permissions = (role.permissions || [])
      .map(function (permission) {
        return (
          '<span class="badge badge-soft">' +
          escapeHtml(permission.code) +
          "</span>"
        );
      })
      .join(" ");

    const header =
      '<article class="role-card"><div class="flex items-start justify-between gap-3"><div><div class="subcard-title">' +
      escapeHtml(role.name) +
      '</div><div class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">' +
      escapeHtml(role.code) +
      '</div></div><span class="badge badge-ok">' +
      escapeHtml(String((role.permissions || []).length)) +
      ' permissions</span></div><p class="mt-3 text-sm leading-6 text-slate-600">' +
      escapeHtml(role.description || "Aucune description") +
      '</p><div class="mt-4 flex flex-wrap gap-2">' +
      (permissions ||
        '<span class="text-sm text-slate-400">Aucune permission</span>') +
      '</div><div class="mt-5 flex flex-wrap gap-3"><button type="button" class="chip-button" data-role-edit="' +
      escapeHtml(String(role.id)) +
      '">Inline edit</button><button type="button" class="chip-button" data-role-delete="' +
      escapeHtml(String(role.id)) +
      '">Supprimer</button></div>';

    if (state.editingRoleId !== role.id) {
      return header + "</article>";
    }

    return header + renderInlineRoleEditor(role) + "</article>";
  }

  function renderInlineRoleEditor(role) {
    const selectedPermissions = new Set(
      (role.permissions || []).map(function (permission) {
        return permission.code;
      }),
    );

    return (
      '<form class="mt-5 inline-editor inline-grid" data-inline-role-form="' +
      escapeHtml(String(role.id)) +
      '"><input class="input" name="name" value="' +
      escapeHtml(role.name || "") +
      '" placeholder="Nom" required><input class="input" name="code" value="' +
      escapeHtml(role.code || "") +
      '" placeholder="Code" required><textarea class="input resize-none" name="description" rows="3" placeholder="Description">' +
      escapeHtml(role.description || "") +
      '</textarea><div><div class="mb-2 text-sm font-medium text-slate-700">Permissions</div><div class="pill-grid">' +
      state.permissions
        .map(function (permission) {
          return checkboxChip(
            "inline-role-" + role.id + "-" + permission.id,
            permission.code,
            permission.code,
            selectedPermissions.has(permission.code),
          );
        })
        .join("") +
      '</div></div><div class="flex flex-wrap gap-3"><button class="primary-button">Sauvegarder</button><button type="button" class="secondary-button" data-role-cancel="' +
      escapeHtml(String(role.id)) +
      '">Annuler</button></div></form>'
    );
  }

  function bindRolesActions() {
    elements.rolesGrid
      .querySelectorAll("[data-role-edit]")
      .forEach(function (button) {
        button.addEventListener("click", function () {
          const id = Number(button.getAttribute("data-role-edit"));
          state.editingRoleId = state.editingRoleId === id ? null : id;
          renderRoles();
        });
      });

    elements.rolesGrid
      .querySelectorAll("[data-role-delete]")
      .forEach(function (button) {
        button.addEventListener("click", function () {
          const id = Number(button.getAttribute("data-role-delete"));
          const role = state.roles.find(function (item) {
            return item.id === id;
          });
          openConfirmModal(
            "Supprimer ce role ?",
            "Cette action tentera de supprimer le role " +
              (role ? role.code : "selectionne") +
              ".",
            function () {
              deleteRole(id);
            },
          );
        });
      });

    elements.rolesGrid
      .querySelectorAll("[data-role-cancel]")
      .forEach(function (button) {
        button.addEventListener("click", function () {
          state.editingRoleId = null;
          renderRoles();
        });
      });

    elements.rolesGrid
      .querySelectorAll("[data-inline-role-form]")
      .forEach(function (form) {
        form.addEventListener("submit", function (event) {
          event.preventDefault();
          const id = Number(form.getAttribute("data-inline-role-form"));
          updateRole(id, form);
        });
      });
  }

  async function updateRole(id, form) {
    const payload = formToObject(form);
    payload.permission_codes = checkedValues(form);

    const result = await apiCall("/roles/" + encodeURIComponent(String(id)), {
      method: "PUT",
      auth: "admin",
      body: payload,
    });

    if (result && result.success) {
      state.editingRoleId = null;
      await Promise.all([loadRoles(), loadPermissions(), loadLogs()]);
    }
  }

  async function deleteRole(id) {
    const result = await apiCall("/roles/" + encodeURIComponent(String(id)), {
      method: "DELETE",
      auth: "admin",
    });

    if (result && result.success) {
      if (state.editingRoleId === id) {
        state.editingRoleId = null;
      }
      await Promise.all([loadRoles(), loadPermissions(), loadLogs()]);
    }
  }

  function renderLogsEventFilter() {
    const uniqueEvents = Array.from(
      new Set(
        state.logs.map(function (log) {
          return log.event_type;
        }),
      ),
    ).sort();

    const currentValue = state.logsUI.event;
    elements.logsEventFilter.innerHTML = [
      '<option value="all">Tous les evenements</option>',
    ]
      .concat(
        uniqueEvents.map(function (eventType) {
          return (
            '<option value="' +
            escapeHtml(eventType) +
            '">' +
            escapeHtml(eventType) +
            "</option>"
          );
        }),
      )
      .join("");
    elements.logsEventFilter.value = currentValue;
  }

  function renderLogs() {
    const filtered = getFilteredLogs();
    const pagination = paginate(
      filtered,
      state.logsUI.page,
      state.logsUI.pageSize,
    );

    if (!filtered.length) {
      elements.logsTimeline.innerHTML =
        '<div class="empty-state">Aucun log ne correspond aux filtres selectionnes.</div>';
      renderPageInfo(
        elements.logsPageInfo,
        elements.logsPrevPage,
        elements.logsNextPage,
        pagination,
        "log",
      );
      return;
    }

    elements.logsTimeline.innerHTML = pagination.items
      .map(function (log) {
        return renderLogCard(log);
      })
      .join("");

    renderPageInfo(
      elements.logsPageInfo,
      elements.logsPrevPage,
      elements.logsNextPage,
      pagination,
      "log",
    );
  }

  function renderLogCard(log) {
    const actor =
      log.full_name && log.full_name.trim() !== ""
        ? log.full_name
        : log.username || "Systeme";

    return (
      '<article class="timeline-card"><div class="flex flex-wrap items-center justify-between gap-3"><div class="subcard-title">' +
      escapeHtml(log.event_type) +
      '</div><span class="badge badge-soft">' +
      escapeHtml(log.created_at) +
      '</span></div><p class="mt-3 text-sm leading-6 text-slate-700">' +
      escapeHtml(log.message) +
      '</p><div class="mt-4 grid gap-3 text-sm text-slate-500 lg:grid-cols-4"><div><span class="font-medium text-slate-700">Utilisateur</span><div>' +
      escapeHtml(actor) +
      '</div></div><div><span class="font-medium text-slate-700">Email</span><div>' +
      escapeHtml(log.email || "-") +
      '</div></div><div><span class="font-medium text-slate-700">IP</span><div>' +
      escapeHtml(log.ip_address || "-") +
      '</div></div><div><span class="font-medium text-slate-700">ID user</span><div>' +
      escapeHtml(log.user_id || "-") +
      '</div></div></div><div class="mt-3 text-xs text-slate-400">' +
      escapeHtml(log.user_agent || "") +
      "</div></article>"
    );
  }

  function renderEndpointCatalog() {
    elements.endpointList.innerHTML = state.endpointCatalog
      .map(function (endpointConfig) {
        return (
          '<button type="button" class="endpoint-item" data-endpoint-key="' +
          escapeHtml(endpointConfig.key) +
          '"><div class="flex items-center justify-between gap-3"><span class="badge badge-soft">' +
          escapeHtml(endpointConfig.method) +
          '</span><span class="text-xs uppercase tracking-[0.18em] text-slate-400">' +
          escapeHtml(endpointConfig.auth) +
          '</span></div><div class="mt-3 font-medium text-slate-900">' +
          escapeHtml(endpointConfig.label) +
          '</div><div class="mt-1 text-sm text-slate-500">' +
          escapeHtml(endpointConfig.path) +
          "</div></button>"
        );
      })
      .join("");

    elements.endpointList
      .querySelectorAll("[data-endpoint-key]")
      .forEach(function (button) {
        button.addEventListener("click", function () {
          selectEndpoint(button.getAttribute("data-endpoint-key"));
        });
      });
  }

  function selectEndpoint(key) {
    state.explorerSelection =
      state.endpointCatalog.find(function (item) {
        return item.key === key;
      }) || null;

    if (!state.explorerSelection) {
      return;
    }

    elements.endpointList
      .querySelectorAll(".endpoint-item")
      .forEach(function (item) {
        item.classList.toggle(
          "active",
          item.getAttribute("data-endpoint-key") === key,
        );
      });

    elements.explorerMethod.value = state.explorerSelection.method;
    elements.explorerPath.value = state.explorerSelection.path;
    elements.explorerAuth.value = state.explorerSelection.auth;
    elements.explorerBody.value = state.explorerSelection.body;
  }

  function resetExplorerBody() {
    if (state.explorerSelection) {
      elements.explorerBody.value = state.explorerSelection.body;
    }
  }

  async function apiCall(path, options) {
    const method = options.method || "GET";
    const headers = { Accept: "application/json" };
    const token = getToken(options.auth);

    if (options.auth && options.auth !== "none") {
      if (!token) {
        const missingTokenPayload = {
          success: false,
          error: { message: "Token manquant pour cette action." },
        };
        if (!options.silent) {
          setResponse(missingTokenPayload);
        }
        return missingTokenPayload;
      }
      headers.Authorization = "Bearer " + token;
    }

    const fetchOptions = { method: method, headers: headers };
    if (options.body !== undefined && method !== "GET" && method !== "DELETE") {
      headers["Content-Type"] = "application/json";
      fetchOptions.body = JSON.stringify(options.body);
    }

    const response = await fetch(state.apiBaseUrl + path, fetchOptions);
    let payload;

    try {
      payload = await response.json();
    } catch (error) {
      payload = {
        success: false,
        error: {
          message: "Reponse JSON invalide.",
          status: response.status,
        },
      };
    }

    if (!options.silent) {
      setResponse(payload);
    }

    return payload;
  }

  function setResponse(payload) {
    elements.responseViewer.textContent = JSON.stringify(payload, null, 2);
  }

  function openConfirmModal(title, message, action) {
    state.confirmAction = action;
    elements.confirmModalTitle.textContent = title;
    elements.confirmModalMessage.textContent = message;
    elements.confirmModal.classList.remove("hidden");
  }

  function closeConfirmModal() {
    state.confirmAction = null;
    elements.confirmModal.classList.add("hidden");
  }

  function confirmModalAction() {
    const action = state.confirmAction;
    closeConfirmModal();
    if (typeof action === "function") {
      action();
    }
  }

  function changePage(scope, delta) {
    const uiKey = scope + "UI";
    const filteredLength =
      scope === "users"
        ? getFilteredUsers().length
        : scope === "roles"
          ? getFilteredRoles().length
          : getFilteredLogs().length;
    const maxPage = Math.max(
      1,
      Math.ceil(filteredLength / state[uiKey].pageSize),
    );
    state[uiKey].page = Math.min(
      maxPage,
      Math.max(1, state[uiKey].page + delta),
    );

    if (scope === "users") {
      renderUsers();
    } else if (scope === "roles") {
      renderRoles();
    } else {
      renderLogs();
    }
  }

  function normalizePage(scope, totalItems) {
    const uiKey = scope + "UI";
    const maxPage = Math.max(1, Math.ceil(totalItems / state[uiKey].pageSize));
    state[uiKey].page = Math.min(state[uiKey].page, maxPage);
  }

  function renderPageInfo(
    labelElement,
    prevButton,
    nextButton,
    pagination,
    noun,
  ) {
    labelElement.textContent =
      pagination.total === 0
        ? "0 " + noun + " charge"
        : pagination.start +
          "-" +
          pagination.end +
          " sur " +
          pagination.total +
          " " +
          noun +
          (pagination.total > 1 ? "s" : "");
    prevButton.disabled = !pagination.hasPrev;
    nextButton.disabled = !pagination.hasNext;
    prevButton.classList.add("pagination-button");
    nextButton.classList.add("pagination-button");
  }

  function paginate(items, page, pageSize) {
    const total = items.length;
    const safePageSize = Math.max(1, Number(pageSize || 1));
    const maxPage = Math.max(1, Math.ceil(total / safePageSize));
    const safePage = Math.min(maxPage, Math.max(1, Number(page || 1)));
    const startIndex = (safePage - 1) * safePageSize;
    const endIndex = startIndex + safePageSize;

    return {
      total: total,
      page: safePage,
      pageSize: safePageSize,
      items: items.slice(startIndex, endIndex),
      start: total ? startIndex + 1 : 0,
      end: total ? Math.min(endIndex, total) : 0,
      hasPrev: safePage > 1,
      hasNext: safePage < maxPage,
    };
  }

  function getFilteredUsers() {
    const search = state.usersUI.search.trim().toLowerCase();

    return state.users.filter(function (user) {
      const roles = user.roles || [];
      const text = [
        user.username,
        user.email,
        user.first_name,
        user.last_name,
        user.phone,
      ]
        .join(" ")
        .toLowerCase();
      const searchMatch = search === "" || text.includes(search);
      const statusMatch =
        state.usersUI.status === "all" || user.status === state.usersUI.status;
      const roleMatch =
        state.usersUI.role === "all" ||
        roles.some(function (role) {
          return role.code === state.usersUI.role;
        });

      return searchMatch && statusMatch && roleMatch;
    });
  }

  function getFilteredRoles() {
    const search = state.rolesUI.search.trim().toLowerCase();
    return state.roles.filter(function (role) {
      const haystack = [role.name, role.code, role.description]
        .join(" ")
        .toLowerCase();
      return search === "" || haystack.includes(search);
    });
  }

  function getFilteredLogs() {
    const search = state.logsUI.search.trim().toLowerCase();
    return state.logs.filter(function (log) {
      const haystack = [
        log.event_type,
        log.message,
        log.username,
        log.email,
        log.full_name,
        log.ip_address,
      ]
        .join(" ")
        .toLowerCase();
      const searchMatch = search === "" || haystack.includes(search);
      const eventMatch =
        state.logsUI.event === "all" || log.event_type === state.logsUI.event;
      return searchMatch && eventMatch;
    });
  }

  function formToObject(form) {
    const formData = new FormData(form);
    const object = {};
    formData.forEach(function (value, key) {
      if (key in object) {
        return;
      }
      object[key] = value;
    });
    return object;
  }

  function checkedValues(container) {
    return Array.from(
      container.querySelectorAll('input[type="checkbox"]:checked'),
    ).map(function (input) {
      return input.value;
    });
  }

  function getToken(type) {
    if (type === "admin") {
      return localStorage.getItem("banamur_admin_token");
    }
    if (type === "user") {
      return (
        localStorage.getItem("banamur_user_token") ||
        localStorage.getItem("banamur_admin_token")
      );
    }
    return null;
  }

  function renderStatusOptions(value) {
    return ["active", "inactive", "blocked", "pending"]
      .map(function (status) {
        return (
          '<option value="' +
          status +
          '"' +
          (status === value ? " selected" : "") +
          ">" +
          status +
          "</option>"
        );
      })
      .join("");
  }

  function statusBadgeClass(status) {
    if (status === "active") {
      return "badge-ok";
    }
    if (status === "blocked") {
      return "badge-warn";
    }
    return "badge-soft";
  }

  function checkboxChip(id, value, label, checked) {
    return (
      '<label for="' +
      escapeHtml(id) +
      '" class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">' +
      '<input id="' +
      escapeHtml(id) +
      '" type="checkbox" value="' +
      escapeHtml(value) +
      '" class="h-4 w-4 rounded border-slate-300 text-accent focus:ring-accent"' +
      (checked ? " checked" : "") +
      "><span>" +
      escapeHtml(label) +
      "</span></label>"
    );
  }

  function checkboxTile(id, value, label) {
    return (
      '<label for="' +
      escapeHtml(id) +
      '" class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">' +
      '<input id="' +
      escapeHtml(id) +
      '" type="checkbox" value="' +
      escapeHtml(value) +
      '" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-accent focus:ring-accent"><span><span class="block font-medium text-slate-900">' +
      escapeHtml(value) +
      '</span><span class="mt-1 block text-slate-500">' +
      escapeHtml(label) +
      "</span></span></label>"
    );
  }

  function shortenToken(token) {
    return token.length <= 20
      ? token
      : token.slice(0, 8) + "..." + token.slice(-8);
  }

  function endpoint(key, label, method, path, auth, body) {
    return {
      key: key,
      label: label,
      method: method,
      path: path,
      auth: auth,
      body: body || "",
    };
  }

  function jsonTemplate(object) {
    return JSON.stringify(object, null, 2);
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  init();
})();
