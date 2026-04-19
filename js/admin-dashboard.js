(function () {
  const pageKind = window.BANAMUR_DASHBOARD.page || "admin";
  const loginUrl = window.BANAMUR_DASHBOARD.loginUrl || "/login";
  const adminUrl = window.BANAMUR_DASHBOARD.adminUrl || "/admin";

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
    authFailureHandled: false,
    sessionCheckCompleted: false,
    currentSessionUser: window.BANAMUR_DASHBOARD.bootAdminUser || null,
    apiKeyDraftUserId: null,
    users: [],
    roles: [],
    permissions: [],
    logs: [],
    apiKeys: [],
    antiBot: null,
    explorerSelection: null,
    editingUserId: null,
    editingRoleId: null,
    confirmAction: null,
    createUserPreset: "developer",
    usersUI: {
      search: "",
      status: "all",
      accountType: "all",
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
      endpoint("apiKeys", "List API Keys", "GET", "/api-keys", "admin"),
      endpoint(
        "apiKeyCreate",
        "Create API Key",
        "POST",
        "/api-keys",
        "admin",
        jsonTemplate({
          name: "Integration Postman",
          user_id: 1,
          expires_in_days: 30,
        }),
      ),
      endpoint(
        "apiKeyDelete",
        "Revoke API Key",
        "DELETE",
        "/api-keys/1",
        "admin",
      ),
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
    appShell: document.getElementById("appShell"),
    loginGateHero: document.getElementById("loginGateHero"),
    sidebarShell: document.getElementById("sidebarShell"),
    workspaceHeader: document.getElementById("workspaceHeader"),
    apiBaseUrlLabel: document.getElementById("apiBaseUrlLabel"),
    sessionStatus: document.getElementById("sessionStatus"),
    sessionIdentity: document.getElementById("sessionIdentity"),
    sessionApiPermissions: document.getElementById("sessionApiPermissions"),
    sidebarUserToken: document.getElementById("sidebarUserToken"),
    sidebarAdminToken: document.getElementById("sidebarAdminToken"),
    sidebarApiKey: document.getElementById("sidebarApiKey"),
    currentViewTitle: document.getElementById("currentViewTitle"),
    currentViewDescription: document.getElementById("currentViewDescription"),
    metricUsers: document.getElementById("metricUsers"),
    metricRoles: document.getElementById("metricRoles"),
    metricLogs: document.getElementById("metricLogs"),
    metricAdmins: document.getElementById("metricAdmins"),
    metricDevelopers: document.getElementById("metricDevelopers"),
    metricApiKeys: document.getElementById("metricApiKeys"),
    responseViewer: document.getElementById("responseViewer"),
    userTokenPreview: document.getElementById("userTokenPreview"),
    adminTokenPreview: document.getElementById("adminTokenPreview"),
    apiKeyPreview: document.getElementById("apiKeyPreview"),
    antiBotPrompt: document.getElementById("antiBotPrompt"),
    antiBotGrid: document.getElementById("antiBotGrid"),
    antiBotAnswer: document.getElementById("antiBotAnswer"),
    antiBotStatus: document.getElementById("antiBotStatus"),
    dashboardAdminCount: document.getElementById("dashboardAdminCount"),
    dashboardDeveloperCount: document.getElementById("dashboardDeveloperCount"),
    dashboardApiKeysCount: document.getElementById("dashboardApiKeysCount"),
    usersTableBody: document.getElementById("usersTableBody"),
    usersSearchInput: document.getElementById("usersSearchInput"),
    usersStatusFilter: document.getElementById("usersStatusFilter"),
    usersAccountTypeFilter: document.getElementById("usersAccountTypeFilter"),
    usersRoleFilter: document.getElementById("usersRoleFilter"),
    usersPageSize: document.getElementById("usersPageSize"),
    usersPageInfo: document.getElementById("usersPageInfo"),
    usersPrevPage: document.getElementById("usersPrevPage"),
    usersNextPage: document.getElementById("usersNextPage"),
    createUserForm: document.getElementById("createUserForm"),
    createUserPresetHint: document.getElementById("createUserPresetHint"),
    developerAccessFields: document.getElementById("developerAccessFields"),
    createUserApiKeyToggle: document.getElementById("createUserApiKeyToggle"),
    createUserApiKeyName: document.getElementById("createUserApiKeyName"),
    createUserApiKeyDays: document.getElementById("createUserApiKeyDays"),
    userRolesOptions: document.getElementById("userRolesOptions"),
    rolesGrid: document.getElementById("rolesGrid"),
    rolesSearchInput: document.getElementById("rolesSearchInput"),
    rolesPageSize: document.getElementById("rolesPageSize"),
    rolesPageInfo: document.getElementById("rolesPageInfo"),
    rolesPrevPage: document.getElementById("rolesPrevPage"),
    rolesNextPage: document.getElementById("rolesNextPage"),
    createRoleForm: document.getElementById("createRoleForm"),
    permissionsOptions: document.getElementById("permissionsOptions"),
    createApiKeyForm: document.getElementById("createApiKeyForm"),
    apiKeyUserSelect: document.getElementById("apiKeyUserSelect"),
    apiKeysList: document.getElementById("apiKeysList"),
    openApiKeysButton: document.getElementById("openApiKeysButton"),
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
    toastStack: document.getElementById("toastStack"),
  };

  async function init() {
    syncAdminSessionToken();

    if (isLoginPage()) {
      sanitizeLoginPageSession();
    }

    if (document.getElementById("useAdminToken")) {
      document.getElementById("useAdminToken").checked = true;
    }
    if (elements.apiBaseUrlLabel) {
      elements.apiBaseUrlLabel.textContent = state.apiBaseUrl;
    }
    bindActions();
    renderTokens();
    if (isAdminPage()) {
      renderEndpointCatalog();
      selectEndpoint(state.endpointCatalog[0].key);
      syncRouteFromHash();
      await bootstrapAdminPage();
    }
    if (isLoginPage()) {
      await loadAntiBotChallenge();
    }
  }

  async function bootstrapAdminPage() {
    const sessionResult = await apiCall(
      "/admin/bootstrap?log_limit=" +
        encodeURIComponent(String(state.logsUI.fetchLimit)),
      {
        method: "GET",
        auth: "admin",
        silent: true,
        toastError: false,
        suppressAuthFailureHandling: true,
      },
    );

    state.sessionCheckCompleted = true;

    if (!sessionResult || !sessionResult.success) {
      if (
        sessionResult &&
        sessionResult.error &&
        ["invalid_token", "missing_authentication", "missing_token"].includes(
          sessionResult.error.code,
        )
      ) {
        clearAdminSessionState();
      }

      handleAuthenticationFailure(
        sessionResult || {
          success: false,
          error: { message: "Session admin invalide." },
        },
      );
      return;
    }

    state.currentSessionUser = sessionResult.data.user || null;
    state.users = Array.isArray(sessionResult.data.users)
      ? sessionResult.data.users
      : [];
    state.roles = Array.isArray(sessionResult.data.roles)
      ? sessionResult.data.roles
      : [];
    state.permissions = Array.isArray(sessionResult.data.permissions)
      ? sessionResult.data.permissions
      : [];
    state.logs = Array.isArray(sessionResult.data.logs)
      ? sessionResult.data.logs
      : [];
    state.apiKeys = Array.isArray(sessionResult.data.api_keys)
      ? sessionResult.data.api_keys
      : [];

    renderTokens();
    normalizePage("users", getFilteredUsers().length);
    normalizePage("roles", getFilteredRoles().length);
    normalizePage("logs", getFilteredLogs().length);
    renderUsersRoleFilter();
    renderApiKeyUserOptions();
    renderPermissionOptions();
    renderRoleOptions();
    renderUsers();
    renderRoles();
    renderLogsEventFilter();
    renderLogs();
    renderApiKeys();
    renderMetrics();
  }

  function bindActions() {
    if (isAdminPage()) {
      window.addEventListener("hashchange", syncRouteFromHash);
    }

    document.querySelectorAll("[data-route]").forEach(function (button) {
      button.addEventListener("click", function () {
        setRoute(button.getAttribute("data-route"));
      });
    });

    bindIfExists("registerForm", "submit", handleRegister);
    bindIfExists("loginForm", "submit", handleLogin);
    bindIfExists("logoutButton", "click", handleLogout);
    bindIfExists("sidebarLogoutButton", "click", handleLogout);
    bindIfExists("openApiKeysButton", "click", function () {
      openApiKeyWorkspaceForCurrentAdmin();
    });
    bindIfExists("meButton", "click", function () {
      apiCall("/auth/me", {
        method: "GET",
        auth: isAdminPage() ? "admin" : "user",
      });
    });

    bindIfExists("refreshUsersButton", "click", loadUsers);
    if (elements.createUserForm) {
      elements.createUserForm.addEventListener("submit", handleCreateUser);
    }
    if (elements.usersSearchInput) {
      elements.usersSearchInput.addEventListener("input", function (event) {
        state.usersUI.search = event.target.value;
        state.usersUI.page = 1;
        renderUsers();
      });
    }
    if (elements.usersStatusFilter) {
      elements.usersStatusFilter.addEventListener("change", function (event) {
        state.usersUI.status = event.target.value;
        state.usersUI.page = 1;
        renderUsers();
      });
    }
    if (elements.usersAccountTypeFilter) {
      elements.usersAccountTypeFilter.addEventListener(
        "change",
        function (event) {
          state.usersUI.accountType = event.target.value;
          state.usersUI.page = 1;
          renderUsers();
        },
      );
    }
    if (elements.usersRoleFilter) {
      elements.usersRoleFilter.addEventListener("change", function (event) {
        state.usersUI.role = event.target.value;
        state.usersUI.page = 1;
        renderUsers();
      });
    }
    if (elements.usersPageSize) {
      elements.usersPageSize.addEventListener("change", function (event) {
        state.usersUI.pageSize = Number(event.target.value);
        state.usersUI.page = 1;
        renderUsers();
      });
    }
    if (elements.usersPrevPage) {
      elements.usersPrevPage.addEventListener("click", function () {
        changePage("users", -1);
      });
    }
    if (elements.usersNextPage) {
      elements.usersNextPage.addEventListener("click", function () {
        changePage("users", 1);
      });
    }

    bindIfExists("refreshRolesButton", "click", loadRoles);
    if (elements.createRoleForm) {
      elements.createRoleForm.addEventListener("submit", handleCreateRole);
    }
    if (elements.rolesSearchInput) {
      elements.rolesSearchInput.addEventListener("input", function (event) {
        state.rolesUI.search = event.target.value;
        state.rolesUI.page = 1;
        renderRoles();
      });
    }
    if (elements.rolesPageSize) {
      elements.rolesPageSize.addEventListener("change", function (event) {
        state.rolesUI.pageSize = Number(event.target.value);
        state.rolesUI.page = 1;
        renderRoles();
      });
    }
    if (elements.rolesPrevPage) {
      elements.rolesPrevPage.addEventListener("click", function () {
        changePage("roles", -1);
      });
    }
    if (elements.rolesNextPage) {
      elements.rolesNextPage.addEventListener("click", function () {
        changePage("roles", 1);
      });
    }

    bindIfExists("refreshLogsButton", "click", loadLogs);
    bindIfExists("refreshApiKeysButton", "click", loadApiKeys);
    bindIfExists("reloadAntiBotButton", "click", loadAntiBotChallenge);
    if (elements.createApiKeyForm) {
      elements.createApiKeyForm.addEventListener("submit", handleCreateApiKey);
    }
    if (elements.antiBotAnswer) {
      elements.antiBotAnswer.addEventListener("input", function () {
        updateAntiBotStatus();
        highlightSelectedAntiBotCard();
      });
    }
    if (elements.logsSearchInput) {
      elements.logsSearchInput.addEventListener("input", function (event) {
        state.logsUI.search = event.target.value;
        state.logsUI.page = 1;
        renderLogs();
      });
    }
    if (elements.logsEventFilter) {
      elements.logsEventFilter.addEventListener("change", function (event) {
        state.logsUI.event = event.target.value;
        state.logsUI.page = 1;
        renderLogs();
      });
    }
    if (elements.logsFetchLimit) {
      elements.logsFetchLimit.addEventListener("change", function (event) {
        state.logsUI.fetchLimit = Number(event.target.value);
        state.logsUI.page = 1;
        loadLogs();
      });
    }
    if (elements.logsPageSize) {
      elements.logsPageSize.addEventListener("change", function (event) {
        state.logsUI.pageSize = Number(event.target.value);
        state.logsUI.page = 1;
        renderLogs();
      });
    }
    if (elements.logsPrevPage) {
      elements.logsPrevPage.addEventListener("click", function () {
        changePage("logs", -1);
      });
    }
    if (elements.logsNextPage) {
      elements.logsNextPage.addEventListener("click", function () {
        changePage("logs", 1);
      });
    }

    bindIfExists("explorerForm", "submit", handleExplorerRun);
    bindIfExists("runSelectedEndpoint", "click", function () {
      const explorerForm = document.getElementById("explorerForm");
      if (explorerForm) {
        explorerForm.requestSubmit();
      }
    });
    bindIfExists("resetExplorerBody", "click", resetExplorerBody);

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

    document.querySelectorAll("[data-user-preset]").forEach(function (button) {
      button.addEventListener("click", function () {
        applyCreateUserPreset(button.getAttribute("data-user-preset"));
      });
    });

    document
      .querySelectorAll("[data-user-filter-shortcut]")
      .forEach(function (button) {
        button.addEventListener("click", function () {
          state.usersUI.accountType = button.getAttribute(
            "data-user-filter-shortcut",
          );
          elements.usersAccountTypeFilter.value = state.usersUI.accountType;
          setRoute("users");
          renderUsers();
        });
      });

    document
      .querySelectorAll("[data-route-shortcut]")
      .forEach(function (button) {
        button.addEventListener("click", function () {
          setRoute("dashboard");
          document
            .getElementById("createApiKeyForm")
            .scrollIntoView({ behavior: "smooth", block: "start" });
        });
      });

    if (elements.confirmModalCancel) {
      elements.confirmModalCancel.addEventListener("click", closeConfirmModal);
    }
    if (elements.confirmModalSubmit) {
      elements.confirmModalSubmit.addEventListener("click", confirmModalAction);
    }
    if (elements.confirmModal) {
      elements.confirmModal.addEventListener("click", function (event) {
        if (event.target === elements.confirmModal) {
          closeConfirmModal();
        }
      });
    }
  }

  function syncRouteFromHash() {
    if (!isAdminPage()) {
      return;
    }

    const hash = window.location.hash.replace("#", "");
    const nextRoute = Object.prototype.hasOwnProperty.call(viewMeta, hash)
      ? hash
      : "dashboard";
    state.route = nextRoute;
    renderRoute();
  }

  function setRoute(route) {
    if (!isAdminPage()) {
      return;
    }

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
    if (!isAdminPage()) {
      return;
    }

    if (!hasBackOfficeAccess()) {
      state.route = "dashboard";
    }

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
    if (!isAdminPage()) {
      return;
    }

    if (state.authFailureHandled) {
      return;
    }

    await loadRoles();
    if (state.authFailureHandled) {
      return;
    }

    await loadPermissions();
    if (state.authFailureHandled) {
      return;
    }

    await loadUsers();
    if (state.authFailureHandled) {
      return;
    }

    await loadLogs();
    if (state.authFailureHandled) {
      return;
    }

    await loadApiKeys();
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
    payload.anti_bot_token = state.antiBot ? state.antiBot.token : "";
    const result = await apiCall("/auth/login", {
      method: "POST",
      body: payload,
    });

    if (!result || !result.success || !result.data || !result.data.token) {
      if (result && result.error && /^anti_bot/.test(result.error.code || "")) {
        elements.antiBotAnswer.value = "";
        await loadAntiBotChallenge();
      }
      return;
    }

    const useAdminTokenCheckbox = document.getElementById("useAdminToken");
    const tokenKey =
      useAdminTokenCheckbox && !isLoginPage() && !useAdminTokenCheckbox.checked
        ? "banamur_user_token"
        : "banamur_admin_token";
    state.authFailureHandled = false;
    state.currentSessionUser = result.data.user || null;
    localStorage.setItem(tokenKey, result.data.token);
    if (tokenKey === "banamur_admin_token") {
      writeCookie("banamur_admin_token", result.data.token, 1);
    }
    renderTokens();
    if (elements.antiBotAnswer) {
      elements.antiBotAnswer.value = "";
    }
    await loadAntiBotChallenge();

    if (tokenKey === "banamur_admin_token") {
      if (isAdminPage()) {
        setRoute("dashboard");
        await refreshReferenceData();
      } else {
        window.location.href = adminUrl;
      }
    }
  }

  async function loadAntiBotChallenge() {
    if (
      !elements.antiBotPrompt ||
      !elements.antiBotGrid ||
      !elements.antiBotStatus
    ) {
      return;
    }

    const result = await apiCall("/auth/anti-bot-challenge", {
      method: "GET",
      silent: true,
      toastSuccess: false,
    });

    state.antiBot =
      result &&
      result.success &&
      result.data &&
      result.data.anti_bot &&
      Array.isArray(result.data.anti_bot.cards)
        ? result.data.anti_bot
        : null;

    renderAntiBotChallenge();
  }

  async function handleLogout() {
    const logoutTargets = [];

    if (
      localStorage.getItem("banamur_admin_token") ||
      readCookie("banamur_admin_token")
    ) {
      logoutTargets.push("admin");
    }

    if (localStorage.getItem("banamur_user_token")) {
      logoutTargets.push("user");
    }

    for (const authType of logoutTargets) {
      await apiCall("/auth/logout", {
        method: "POST",
        auth: authType,
        silent: true,
        toastError: false,
        suppressAuthFailureHandling: true,
      });
    }

    localStorage.removeItem("banamur_user_token");
    clearAdminSessionState();
    localStorage.removeItem("banamur_api_key");
    state.authFailureHandled = false;
    state.currentSessionUser = null;
    renderTokens();
    showToast("success", "Session admin deconnectee.");
    if (isAdminPage()) {
      window.location.href = loginUrl;
      return;
    }

    setRoute("dashboard");
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
    renderApiKeyUserOptions();
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

  async function loadApiKeys() {
    const result = await apiCall("/api-keys", {
      method: "GET",
      auth: "admin",
      silent: true,
    });
    state.apiKeys =
      result && result.success && Array.isArray(result.data) ? result.data : [];
    renderApiKeyUserOptions();
    renderApiKeys();
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
      if (
        state.createUserPreset === "developer" &&
        elements.createUserApiKeyToggle.checked &&
        result.data &&
        result.data.id
      ) {
        await apiCall("/api-keys", {
          method: "POST",
          auth: "admin",
          body: {
            name:
              elements.createUserApiKeyName.value.trim() ||
              "Cle developpeur initiale",
            user_id: result.data.id,
            expires_in_days: elements.createUserApiKeyDays.value.trim() || 30,
          },
        });
      }

      event.currentTarget.reset();
      applyCreateUserPreset(state.createUserPreset);
      await Promise.all([loadUsers(), loadApiKeys(), loadLogs()]);
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

  async function handleCreateApiKey(event) {
    event.preventDefault();
    const payload = formToObject(event.currentTarget);

    if (!payload.user_id) {
      delete payload.user_id;
    }

    if (!payload.expires_in_days) {
      delete payload.expires_in_days;
    }

    const result = await apiCall("/api-keys", {
      method: "POST",
      auth: "admin",
      body: payload,
    });

    if (result && result.success && result.data && result.data.plain_key) {
      localStorage.setItem("banamur_api_key", result.data.plain_key);
      state.apiKeyDraftUserId = null;
      renderTokens();
      event.currentTarget.reset();
      if (elements.apiKeyUserSelect) {
        elements.apiKeyUserSelect.value = "";
      }
      await Promise.all([loadApiKeys(), loadLogs()]);
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
    const apiKey = getToken("apiKey");
    const userPreview = userToken ? shortenToken(userToken) : "aucun";
    const adminPreview = adminToken ? shortenToken(adminToken) : "aucun";
    const apiKeyPreview = apiKey ? shortenToken(apiKey) : "aucune";

    if (elements.userTokenPreview) {
      elements.userTokenPreview.textContent =
        userToken || "Aucun JWT utilisateur";
    }
    if (elements.adminTokenPreview) {
      elements.adminTokenPreview.textContent = adminToken || "Aucun JWT admin";
    }
    if (elements.apiKeyPreview) {
      elements.apiKeyPreview.textContent = apiKey || "Aucune cle API";
    }
    if (elements.sidebarUserToken) {
      elements.sidebarUserToken.textContent = userPreview;
    }
    if (elements.sidebarAdminToken) {
      elements.sidebarAdminToken.textContent = adminPreview;
    }
    if (elements.sidebarApiKey) {
      elements.sidebarApiKey.textContent = apiKeyPreview;
    }
    if (elements.sessionStatus) {
      elements.sessionStatus.textContent = adminToken
        ? "Admin connecte"
        : userToken
          ? "Utilisateur connecte"
          : apiKey
            ? "Acces via cle API"
            : "Non connecte";
    }

    if (elements.sessionIdentity) {
      elements.sessionIdentity.textContent = formatSessionIdentity(
        state.currentSessionUser,
      );
    }

    if (elements.sessionApiPermissions) {
      elements.sessionApiPermissions.textContent = formatApiPermissionStatus(
        state.currentSessionUser,
      );
    }

    renderAccessMode();
  }

  function renderAccessMode() {
    const hasAdminAccess = hasBackOfficeAccess();

    if (isAdminPage() && !hasAdminAccess) {
      window.location.replace(loginUrl);
      return;
    }

    if (elements.loginGateHero) {
      elements.loginGateHero.classList.toggle(
        "hidden",
        hasAdminAccess && isAdminPage(),
      );
    }

    if (elements.sidebarShell) {
      elements.sidebarShell.classList.toggle("hidden-by-lock", !hasAdminAccess);
    }
    if (elements.workspaceHeader) {
      elements.workspaceHeader.classList.toggle(
        "hidden-by-lock",
        !hasAdminAccess,
      );
    }

    document
      .querySelectorAll(".auth-private-block, .auth-session-panel")
      .forEach(function (node) {
        node.classList.toggle("hidden-by-lock", !hasAdminAccess);
      });

    const useAdminTokenCheckbox = document.getElementById("useAdminToken");
    if (!hasAdminAccess && useAdminTokenCheckbox) {
      useAdminTokenCheckbox.checked = true;
    }
  }

  function renderMetrics() {
    if (!elements.metricUsers) {
      return;
    }

    const adminUsers = getAdminUsers();
    const developerUsers = getDeveloperUsers();
    elements.metricUsers.textContent = String(state.users.length);
    elements.metricRoles.textContent = String(state.roles.length);
    elements.metricLogs.textContent = String(state.logs.length);
    elements.metricAdmins.textContent = String(adminUsers.length);
    elements.metricDevelopers.textContent = String(developerUsers.length);
    elements.metricApiKeys.textContent = String(state.apiKeys.length);
    elements.dashboardAdminCount.textContent = String(adminUsers.length);
    elements.dashboardDeveloperCount.textContent = String(
      developerUsers.length,
    );
    elements.dashboardApiKeysCount.textContent = String(state.apiKeys.length);
  }

  function renderAntiBotChallenge() {
    if (!state.antiBot) {
      elements.antiBotPrompt.textContent =
        "Impossible de charger le controle anti-robot. Recharge la page.";
      elements.antiBotGrid.innerHTML =
        '<div class="empty-state">Controle indisponible.</div>';
      elements.antiBotStatus.textContent = "Defi indisponible";
      elements.antiBotStatus.classList.remove("ready");
      return;
    }

    elements.antiBotPrompt.textContent = state.antiBot.prompt;
    elements.antiBotGrid.innerHTML = state.antiBot.cards
      .map(function (card) {
        return (
          '<button type="button" class="anti-bot-card" data-anti-bot-code="' +
          escapeHtml(card.code) +
          '"><span class="anti-bot-code">' +
          escapeHtml(card.code) +
          '</span><div class="anti-bot-meta"><span><strong>Ville</strong> ' +
          escapeHtml(card.city) +
          "</span><span><strong>Symbole</strong> " +
          escapeHtml(card.symbol) +
          "</span><span><strong>Teinte</strong> " +
          escapeHtml(card.tone) +
          "</span></div></button>"
        );
      })
      .join("");

    elements.antiBotGrid
      .querySelectorAll("[data-anti-bot-code]")
      .forEach(function (button) {
        button.addEventListener("click", function () {
          elements.antiBotAnswer.value =
            button.getAttribute("data-anti-bot-code");
          updateAntiBotStatus();
          highlightSelectedAntiBotCard();
        });
      });

    updateAntiBotStatus();
    highlightSelectedAntiBotCard();
  }

  function renderUsersRoleFilter() {
    if (!elements.usersRoleFilter) {
      return;
    }

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
    if (!elements.userRolesOptions) {
      return;
    }

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
    if (!elements.permissionsOptions) {
      return;
    }

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

  function renderApiKeyUserOptions() {
    if (!elements.apiKeyUserSelect) {
      return;
    }

    const currentValue = state.apiKeyDraftUserId
      ? String(state.apiKeyDraftUserId)
      : elements.apiKeyUserSelect.value;
    elements.apiKeyUserSelect.innerHTML = [
      '<option value="">Utilisateur connecte</option>',
    ]
      .concat(
        state.users.map(function (user) {
          return (
            '<option value="' +
            escapeHtml(String(user.id)) +
            '">' +
            escapeHtml(
              user.email + " - " + (user.first_name || user.username),
            ) +
            "</option>"
          );
        }),
      )
      .join("");

    if (currentValue) {
      elements.apiKeyUserSelect.value = currentValue;
    }
  }

  function renderApiKeys() {
    if (!elements.apiKeysList) {
      return;
    }

    if (!state.apiKeys.length) {
      elements.apiKeysList.innerHTML =
        '<div class="empty-state">Connecte un admin puis cree une premiere cle API.</div>';
      return;
    }

    elements.apiKeysList.innerHTML = state.apiKeys
      .map(function (apiKey) {
        const ownerLabel =
          apiKey.user && apiKey.user.email
            ? apiKey.user.email
            : "Utilisateur inconnu";

        return (
          '<article class="card-subtle"><div class="flex flex-wrap items-start justify-between gap-3"><div><div class="subcard-title">' +
          escapeHtml(apiKey.name) +
          '</div><p class="subcard-text">' +
          escapeHtml(ownerLabel) +
          '</p></div><button type="button" class="chip-button" data-api-key-delete="' +
          escapeHtml(String(apiKey.id)) +
          '">Revoquer</button></div><div class="mt-4 grid gap-2 text-sm text-slate-600"><div><span class="font-medium text-slate-800">Prefixe :</span> ' +
          escapeHtml(apiKey.key_prefix) +
          '</div><div><span class="font-medium text-slate-800">Creee :</span> ' +
          escapeHtml(apiKey.created_at || "-") +
          '</div><div><span class="font-medium text-slate-800">Expire :</span> ' +
          escapeHtml(apiKey.expires_at || "Jamais") +
          '</div><div><span class="font-medium text-slate-800">Derniere utilisation :</span> ' +
          escapeHtml(apiKey.last_used_at || "Jamais") +
          "</div></div></article>"
        );
      })
      .join("");

    elements.apiKeysList
      .querySelectorAll("[data-api-key-delete]")
      .forEach(function (button) {
        button.addEventListener("click", function () {
          const id = Number(button.getAttribute("data-api-key-delete"));
          const apiKey = state.apiKeys.find(function (item) {
            return item.id === id;
          });

          openConfirmModal(
            "Revoquer cette cle API ?",
            "Cette action bloquera immediatement " +
              (apiKey ? apiKey.name : "la cle selectionnee") +
              ".",
            function () {
              deleteApiKey(id);
            },
          );
        });
      });
  }

  function renderUsers() {
    if (!elements.usersTableBody) {
      return;
    }

    const filtered = getFilteredUsers();
    const pagination = paginate(
      filtered,
      state.usersUI.page,
      state.usersUI.pageSize,
    );

    if (!filtered.length) {
      elements.usersTableBody.innerHTML =
        '<tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">Aucun utilisateur ne correspond a ces filtres.</td></tr>';
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
    const accountType = getUserAccountType(user);
    const accountTypeLabel = getUserAccountTypeLabel(accountType);
    const apiKeyCount = getApiKeyCountForUser(user.id);
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
      (accountType === "admin"
        ? "badge-ok"
        : accountType === "no-role"
          ? "badge-warn"
          : "badge-soft") +
      '">' +
      escapeHtml(accountTypeLabel) +
      "</span></td>" +
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
      '<button type="button" class="chip-button" data-user-manage-roles="' +
      escapeHtml(String(user.id)) +
      '">Modifier roles</button>' +
      '<button type="button" class="chip-button" data-user-manage-api-key="' +
      escapeHtml(String(user.id)) +
      '">API key (' +
      escapeHtml(String(apiKeyCount)) +
      ")</button>" +
      '<button type="button" class="chip-button" data-user-delete="' +
      escapeHtml(String(user.id)) +
      '">Supprimer</button></div></td>' +
      "</tr>";

    if (state.editingUserId !== user.id) {
      return baseRow;
    }

    return (
      baseRow +
      '<tr><td colspan="6" class="px-5 pb-5">' +
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
      .querySelectorAll("[data-user-manage-roles]")
      .forEach(function (button) {
        button.addEventListener("click", function () {
          const id = Number(button.getAttribute("data-user-manage-roles"));
          state.editingUserId = state.editingUserId === id ? null : id;
          renderUsers();
        });
      });

    elements.usersTableBody
      .querySelectorAll("[data-user-manage-api-key]")
      .forEach(function (button) {
        button.addEventListener("click", function () {
          const id = Number(button.getAttribute("data-user-manage-api-key"));
          openApiKeyWorkspaceForUser(id);
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
    if (!elements.rolesGrid) {
      return;
    }

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

  async function deleteApiKey(id) {
    const result = await apiCall(
      "/api-keys/" + encodeURIComponent(String(id)),
      {
        method: "DELETE",
        auth: "admin",
      },
    );

    if (result && result.success) {
      await Promise.all([loadApiKeys(), loadLogs()]);
    }
  }

  function renderLogsEventFilter() {
    if (!elements.logsEventFilter) {
      return;
    }

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
    if (!elements.logsTimeline) {
      return;
    }

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
    if (!elements.endpointList) {
      return;
    }

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
    if (!elements.endpointList || !elements.explorerMethod) {
      return;
    }

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
    if (state.explorerSelection && elements.explorerBody) {
      elements.explorerBody.value = state.explorerSelection.body;
    }
  }

  async function apiCall(path, options) {
    const method = options.method || "GET";
    const headers = { Accept: "application/json" };
    const token = getToken(options.auth);
    const shouldUseCookieSession =
      isAdminPage() &&
      options.auth &&
      options.auth !== "none" &&
      options.auth !== "apiKey" &&
      !token &&
      !!readCookie("banamur_admin_token");

    if (options.auth && options.auth !== "none") {
      if (!token && !shouldUseCookieSession) {
        const missingTokenPayload = {
          success: false,
          error: {
            message:
              options.auth === "apiKey"
                ? "Cle API manquante pour cette action."
                : "Token manquant pour cette action.",
          },
        };
        if (!options.silent) {
          setResponse(missingTokenPayload);
        }
        return missingTokenPayload;
      }

      if (options.auth === "apiKey") {
        headers["X-API-Key"] = token;
      } else if (!shouldUseCookieSession) {
        headers.Authorization = "Bearer " + token;
      }
    }

    const fetchOptions = {
      method: method,
      headers: headers,
      credentials: "same-origin",
    };
    if (options.body !== undefined && method !== "GET" && method !== "DELETE") {
      headers["Content-Type"] = "application/json";
      fetchOptions.body = JSON.stringify(options.body);
    }

    let response;
    try {
      response = await fetch(state.apiBaseUrl + path, fetchOptions);
    } catch (error) {
      const payload = {
        success: false,
        error: {
          code: "network_error",
          message: "Impossible de joindre l'API.",
        },
      };

      if (!options.silent) {
        setResponse(payload);
      }
      if (options.toastError !== false) {
        showToast("error", payload.error.message);
      }

      return payload;
    }

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

    if (
      !options.suppressAuthFailureHandling &&
      shouldHandleAuthenticationFailure(payload, options)
    ) {
      handleAuthenticationFailure(payload);
      return payload;
    }

    if (!payload.success && options.toastError !== false) {
      showToast(
        "error",
        payload.error && payload.error.message
          ? payload.error.message
          : "Action impossible.",
      );
    }

    if (payload.success && !options.silent && options.toastSuccess !== false) {
      showToast("success", extractSuccessMessage(payload, method, path));
    }

    return payload;
  }

  function setResponse(payload) {
    if (elements.responseViewer) {
      elements.responseViewer.textContent = JSON.stringify(payload, null, 2);
    }
  }

  function shouldHandleAuthenticationFailure(payload, options) {
    if (!payload || payload.success || !payload.error) {
      return false;
    }

    if (!options.auth || options.auth === "none" || options.auth === "apiKey") {
      return false;
    }

    return [
      "invalid_token",
      "missing_authentication",
      "missing_authorization_header",
      "missing_token",
    ].includes(payload.error.code);
  }

  function handleAuthenticationFailure(payload) {
    if (state.authFailureHandled) {
      return;
    }

    state.authFailureHandled = true;
    setResponse(payload);

    const message =
      payload.error && payload.error.message
        ? payload.error.message
        : "Session invalide.";
    showToast("error", message);

    if (isAdminPage()) {
      document
        .querySelectorAll(".auth-private-block, .auth-session-panel")
        .forEach(function (node) {
          node.classList.add("hidden-by-lock");
        });
    }
  }

  function showToast(type, message) {
    if (!elements.toastStack) {
      return;
    }

    const toast = document.createElement("div");
    toast.className = "toast " + type;
    toast.innerHTML =
      '<div class="toast-title">' +
      escapeHtml(type === "error" ? "Attention" : "Succes") +
      '</div><div class="toast-message">' +
      escapeHtml(message) +
      "</div>";
    elements.toastStack.appendChild(toast);

    window.setTimeout(function () {
      toast.remove();
    }, 4200);
  }

  function openConfirmModal(title, message, action) {
    if (!elements.confirmModal) {
      return;
    }

    state.confirmAction = action;
    elements.confirmModalTitle.textContent = title;
    elements.confirmModalMessage.textContent = message;
    elements.confirmModal.classList.remove("hidden");
  }

  function closeConfirmModal() {
    if (!elements.confirmModal) {
      return;
    }

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
      const accountTypeMatch =
        state.usersUI.accountType === "all" ||
        getUserAccountType(user) === state.usersUI.accountType;
      const roleMatch =
        state.usersUI.role === "all" ||
        roles.some(function (role) {
          return role.code === state.usersUI.role;
        });

      return searchMatch && statusMatch && accountTypeMatch && roleMatch;
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
      return getAdminSessionToken();
    }
    if (type === "user") {
      return (
        localStorage.getItem("banamur_user_token") || getAdminSessionToken()
      );
    }
    if (type === "apiKey") {
      return localStorage.getItem("banamur_api_key");
    }
    return null;
  }

  function hasBackOfficeAccess() {
    return !!getAdminSessionToken();
  }

  function bindIfExists(id, eventName, handler) {
    const element = document.getElementById(id);
    if (element) {
      element.addEventListener(eventName, handler);
    }
  }

  function writeCookie(name, value, days) {
    const expires = new Date(Date.now() + days * 86400000).toUTCString();
    document.cookie =
      name +
      "=" +
      encodeURIComponent(value) +
      "; expires=" +
      expires +
      "; path=/; SameSite=Lax";
  }

  function clearCookie(name) {
    document.cookie =
      name + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax";
  }

  function readCookie(name) {
    const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const match = document.cookie.match(
      new RegExp("(?:^|; )" + escapedName + "=([^;]*)"),
    );

    return match ? decodeURIComponent(match[1]) : "";
  }

  function getAdminSessionToken() {
    const bootToken = window.BANAMUR_DASHBOARD.bootAdminToken || "";
    const cookieToken = readCookie("banamur_admin_token");
    const localToken = localStorage.getItem("banamur_admin_token") || "";
    const preferredToken = cookieToken || localToken || bootToken;

    if (!preferredToken) {
      return null;
    }

    if (preferredToken !== localToken) {
      try {
        localStorage.setItem("banamur_admin_token", preferredToken);
      } catch (error) {
        return preferredToken;
      }
    }

    if (preferredToken !== cookieToken && preferredToken === bootToken) {
      writeCookie("banamur_admin_token", preferredToken, 1);
    }

    return preferredToken;
  }

  function clearAdminSessionState() {
    try {
      localStorage.removeItem("banamur_admin_token");
    } catch (error) {}

    clearCookie("banamur_admin_token");

    if (window.BANAMUR_DASHBOARD) {
      window.BANAMUR_DASHBOARD.bootAdminToken = "";
      window.BANAMUR_DASHBOARD.bootAdminUser = null;
    }
  }

  function syncAdminSessionToken() {
    if (!isAdminPage()) {
      return;
    }

    getAdminSessionToken();
  }

  function sanitizeLoginPageSession() {
    if (readCookie("banamur_admin_token")) {
      return;
    }

    localStorage.removeItem("banamur_admin_token");
    localStorage.removeItem("banamur_user_token");
  }

  function isLoginPage() {
    return pageKind === "login";
  }

  function isAdminPage() {
    return pageKind === "admin";
  }

  function applyCreateUserPreset(type) {
    state.createUserPreset = type === "admin" ? "admin" : "developer";

    const hint =
      state.createUserPreset === "admin"
        ? "Preset admin: active, role ADMIN coche. Ideal pour les superviseurs du back-office."
        : "Preset developpeur: role USER coche, option de cle API disponible juste apres creation.";
    elements.createUserPresetHint.textContent = hint;
    elements.developerAccessFields.classList.toggle(
      "hidden",
      state.createUserPreset !== "developer",
    );

    elements.createUserForm.querySelector('select[name="status"]').value =
      "active";

    elements.userRolesOptions
      .querySelectorAll('input[type="checkbox"]')
      .forEach(function (input) {
        input.checked =
          state.createUserPreset === "admin"
            ? input.value === "ADMIN"
            : input.value === "USER";
      });
  }

  function getUserAccountType(user) {
    const roleCodes = getUserRoleCodes(user);

    return roleCodes.includes("ADMIN") || roleCodes.includes("SUPER_ADMIN")
      ? "admin"
      : roleCodes.length === 0
        ? "no-role"
        : "developer";
  }

  function getUserRoleCodes(user) {
    return (user.roles || []).map(function (role) {
      return role.code;
    });
  }

  function getUserAccountTypeLabel(accountType) {
    if (accountType === "admin") {
      return "admin";
    }

    if (accountType === "no-role") {
      return "sans role";
    }

    return "developpeur";
  }

  function getAdminUsers() {
    return state.users.filter(function (user) {
      return getUserAccountType(user) === "admin";
    });
  }

  function getDeveloperUsers() {
    return state.users.filter(function (user) {
      return getUserAccountType(user) === "developer";
    });
  }

  function getApiKeyCountForUser(userId) {
    return state.apiKeys.filter(function (apiKey) {
      return Number(apiKey.user_id) === Number(userId);
    }).length;
  }

  function openApiKeyWorkspaceForUser(userId) {
    const user = state.users.find(function (item) {
      return item.id === userId;
    });

    state.apiKeyDraftUserId = userId;
    setRoute("dashboard");
    renderRoute();
    renderApiKeyUserOptions();

    if (user && elements.createApiKeyForm) {
      const nameInput =
        elements.createApiKeyForm.querySelector('[name="name"]');
      if (nameInput && !nameInput.value.trim()) {
        nameInput.value =
          "Cle API - " + (user.email || user.username || userId);
      }
    }

    focusApiKeyWorkspace();
  }

  function openApiKeyWorkspaceForCurrentAdmin() {
    if (state.currentSessionUser && state.currentSessionUser.id) {
      openApiKeyWorkspaceForUser(state.currentSessionUser.id);
      return;
    }

    setRoute("dashboard");
    renderRoute();
    focusApiKeyWorkspace();
  }

  function focusApiKeyWorkspace() {
    window.requestAnimationFrame(function () {
      if (elements.createApiKeyForm) {
        elements.createApiKeyForm.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }

      if (elements.apiKeyUserSelect) {
        elements.apiKeyUserSelect.focus();
      }
    });
  }

  function formatSessionIdentity(user) {
    if (!user) {
      return "Profil non charge.";
    }

    const identity = user.email || user.username || "Utilisateur inconnu";
    const roleCodes = getUserRoleCodes(user);

    if (roleCodes.length === 0) {
      return identity + " | roles: aucun";
    }

    return identity + " | roles: " + roleCodes.join(", ");
  }

  function formatApiPermissionStatus(user) {
    if (!user) {
      return "Permissions API: profil non charge.";
    }

    const permissionCodes = (user.permissions || []).map(function (permission) {
      return permission.code;
    });

    const canReadApiKeys = permissionCodes.includes("api_key.read");
    const canManageApiKeys = permissionCodes.includes("api_key.manage");

    return (
      "Permissions API: lecture " +
      (canReadApiKeys ? "autorisee" : "refusee") +
      " | generation/revocation " +
      (canManageApiKeys ? "autorisee" : "refusee")
    );
  }

  function updateAntiBotStatus() {
    const value = elements.antiBotAnswer.value.trim().toUpperCase();
    if (value === "") {
      elements.antiBotStatus.textContent =
        "Clique une carte puis verifie le code";
      elements.antiBotStatus.classList.remove("ready");
      return;
    }

    elements.antiBotStatus.textContent = "Code selectionne : " + value;
    elements.antiBotStatus.classList.add("ready");
  }

  function highlightSelectedAntiBotCard() {
    const selectedCode = elements.antiBotAnswer.value.trim().toUpperCase();
    elements.antiBotGrid
      .querySelectorAll("[data-anti-bot-code]")
      .forEach(function (button) {
        button.classList.toggle(
          "is-active",
          button.getAttribute("data-anti-bot-code") === selectedCode,
        );
      });
  }

  function extractSuccessMessage(payload, method, path) {
    if (payload && payload.data && typeof payload.data.message === "string") {
      return payload.data.message;
    }

    return method + " " + path + " execute avec succes.";
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
