<?php

require_once __DIR__ . '/../model/ApiException.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/AuthSessionRepository.php';
require_once __DIR__ . '/../repository/AuthLogRepository.php';
require_once __DIR__ . '/../repository/ApiKeyRepository.php';
require_once __DIR__ . '/../service/UserService.php';
require_once __DIR__ . '/../service/JwtService.php';
require_once __DIR__ . '/../service/AntiBotService.php';

class AuthService
{
    private $userRepository;
    private $authSessionRepository;
    private $authLogRepository;
    private $apiKeyRepository;
    private $userService;
    private $jwtService;
    private $antiBotService;

    public function __construct()
    {
        $this->userRepository = new \UserRepository();
        $this->authSessionRepository = new \AuthSessionRepository();
        $this->authLogRepository = new \AuthLogRepository();
        $this->apiKeyRepository = new \ApiKeyRepository();
        $this->userService = new \UserService();
        $this->jwtService = new \JwtService();
        $this->antiBotService = new \AntiBotService();
    }

    public function register(array $payload, array $context)
    {
        $username = trim((string) ($payload['username'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            throw new \ApiException('Les champs username, email et password sont obligatoires.', 422, 'validation_error');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \ApiException('Adresse email invalide.', 422, 'validation_error');
        }

        if (strlen($password) < 8) {
            throw new \ApiException('Le mot de passe doit contenir au moins 8 caracteres.', 422, 'validation_error');
        }

        if ($this->userRepository->usernameExists($username)) {
            throw new \ApiException('Ce nom d\'utilisateur existe deja.', 409, 'username_exists');
        }

        if ($this->userRepository->emailExists($email)) {
            throw new \ApiException('Cet email existe deja.', 409, 'email_exists');
        }

        $user = $this->userRepository->create([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => $this->nullableTrim($payload, 'first_name'),
            'last_name' => $this->nullableTrim($payload, 'last_name'),
            'phone' => $this->nullableTrim($payload, 'phone'),
            'status' => 'active',
        ]);

        $this->userRepository->assignRoleByCode($user->getId(), 'USER');
        $this->authLogRepository->log(
            $user->getId(),
            'user_registered',
            'Nouvel utilisateur cree.',
            $context['ip_address'],
            $context['user_agent']
        );

        return [
            'message' => 'Utilisateur cree avec succes.',
            'user' => $this->buildUserPayload($user),
        ];
    }

    public function login(array $payload, array $context)
    {
        $identifier = trim((string) ($payload['identifier'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($identifier === '' || $password === '') {
            throw new \ApiException('Les champs identifier et password sont obligatoires.', 422, 'validation_error');
        }

        if ($this->shouldProtectConsoleLogin($payload)) {
            $this->antiBotService->validateConsoleLoginGuard($payload, $context);
        }

        $user = $this->userRepository->findByEmailOrUsername($identifier);
        if (!$user || !password_verify($password, $user->getPasswordHash())) {
            $this->authLogRepository->log(
                $user ? $user->getId() : null,
                'login_failed',
                'Echec de connexion.',
                $context['ip_address'],
                $context['user_agent']
            );

            throw new \ApiException('Identifiants invalides.', 401, 'invalid_credentials');
        }

        if ($user->getStatus() !== 'active') {
            throw new \ApiException('Ce compte n\'est pas actif.', 403, 'inactive_user');
        }

        if ($this->shouldProtectConsoleLogin($payload) && !$this->hasAdminConsoleAccess($user->getId())) {
            $this->authLogRepository->log(
                $user->getId(),
                'admin_login_blocked',
                'Tentative d\'acces au back-office sans role admin.',
                $context['ip_address'],
                $context['user_agent']
            );

            throw new \ApiException('Ce compte n\'a pas acces au back-office admin.', 403, 'forbidden_role');
        }

        $sessionSecret = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $sessionSecret);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));

        $sessionId = $this->authSessionRepository->create(
            $user->getId(),
            $tokenHash,
            $context['ip_address'],
            $context['user_agent'],
            $expiresAt
        );
        $jwtToken = $this->jwtService->createAccessToken($user->getId(), $sessionId, $expiresAt);

        $this->userRepository->updateLastLoginAt($user->getId());
        $freshUser = $this->userRepository->findById($user->getId());

        $this->authLogRepository->log(
            $user->getId(),
            'login_success',
            'Connexion reussie.',
            $context['ip_address'],
            $context['user_agent']
        );

        return [
            'message' => 'Connexion reussie.',
            'token' => $jwtToken,
            'token_type' => 'Bearer',
            'auth_type' => 'jwt',
            'expires_at' => $expiresAt,
            'user' => $this->buildUserPayload($freshUser),
        ];
    }

    public function issueAntiBotChallenge(array $context)
    {
        return $this->antiBotService->issueConsoleLoginChallenge($context);
    }

    public function getAuthenticatedUser($token)
    {
        $identity = $this->authenticateToken($token);

        return $this->formatIdentityResponse($identity);
    }

    public function authenticateRequest(\ApiRequest $request)
    {
        $bearerToken = $request->findBearerToken();
        if ($bearerToken !== null) {
            return $this->authenticateToken($bearerToken);
        }

        $apiKey = $request->findApiKey();
        if ($apiKey !== null) {
            return $this->authenticateApiKey($apiKey);
        }

        throw new \ApiException('Authentification requise. Utilise un Bearer token JWT ou le header X-API-Key.', 401, 'missing_authentication');
    }

    public function formatIdentityResponse(array $identity)
    {
        $response = [
            'message' => 'Utilisateur authentifie.',
            'user' => $identity['user'],
            'auth' => $identity['auth'],
        ];

        if ($identity['session'] !== null) {
            $response['session'] = [
                'id' => $identity['session']['id'],
                'expires_at' => $identity['session']['expires_at'],
                'created_at' => $identity['session']['created_at'],
            ];
        }

        if (!empty($identity['api_key'])) {
            $response['api_key'] = $identity['api_key'];
        }

        return $response;
    }

    public function authenticateToken($token)
    {
        $token = trim((string) $token);
        if ($token === '') {
            throw new \ApiException('Jeton manquant.', 401, 'missing_token');
        }

        if ($this->jwtService->looksLikeJwt($token)) {
            $payload = $this->jwtService->decodeAccessToken($token);
            $session = $this->authSessionRepository->findActiveById((int) $payload['sid']);

            if (!$session) {
                throw new \ApiException('Jeton invalide ou expire.', 401, 'invalid_token');
            }

            $user = $this->userRepository->findById((int) $session['user_id']);
            if (!$user) {
                throw new \ApiException('Utilisateur introuvable.', 404, 'user_not_found');
            }

            return $this->buildAuthenticatedIdentity($user, $session, [
                'type' => 'bearer',
                'strategy' => 'jwt',
                'token_type' => 'Bearer',
            ]);
        }

        $session = $this->authSessionRepository->findActiveByToken($token);
        if (!$session) {
            throw new \ApiException('Jeton invalide ou expire.', 401, 'invalid_token');
        }

        $user = $this->userRepository->findById((int) $session['user_id']);
        if (!$user) {
            throw new \ApiException('Utilisateur introuvable.', 404, 'user_not_found');
        }

        return $this->buildAuthenticatedIdentity($user, $session, [
            'type' => 'bearer',
            'strategy' => 'legacy_token',
            'token_type' => 'Bearer',
        ]);
    }

    public function authenticateApiKey($plainKey)
    {
        $plainKey = trim((string) $plainKey);
        if ($plainKey === '') {
            throw new \ApiException('Cle API manquante.', 401, 'missing_api_key');
        }

        $apiKey = $this->apiKeyRepository->findActiveByPlainKey($plainKey);
        if (!$apiKey) {
            throw new \ApiException('Cle API invalide ou revoquee.', 401, 'invalid_api_key');
        }

        $user = $this->userRepository->findById((int) $apiKey['user_id']);
        if (!$user) {
            throw new \ApiException('Utilisateur introuvable.', 404, 'user_not_found');
        }

        if ($user->getStatus() !== 'active') {
            throw new \ApiException('Ce compte n\'est pas actif.', 403, 'inactive_user');
        }

        $this->apiKeyRepository->touchLastUsedAt($apiKey['id']);

        return $this->buildAuthenticatedIdentity($user, null, [
            'type' => 'api_key',
            'strategy' => 'api_key',
            'header' => 'X-API-Key',
        ], [
            'id' => (int) $apiKey['id'],
            'name' => $apiKey['name'],
            'key_prefix' => $apiKey['key_prefix'],
            'expires_at' => $apiKey['expires_at'],
        ]);
    }

    public function logout($token, array $context)
    {
        $token = trim((string) $token);
        if ($token === '') {
            throw new \ApiException('Jeton manquant.', 401, 'missing_token');
        }

        $session = null;

        if ($this->jwtService->looksLikeJwt($token)) {
            $payload = $this->jwtService->decodeAccessToken($token);
            $session = $this->authSessionRepository->findActiveById((int) $payload['sid']);

            if ($session) {
                $this->authSessionRepository->revokeById((int) $session['id']);
            }
        } else {
            $session = $this->authSessionRepository->findActiveByTokenHash(hash('sha256', $token));
            if ($session) {
                $this->authSessionRepository->revokeByTokenHash(hash('sha256', $token));
            }
        }

        if (!$session) {
            throw new \ApiException('Jeton invalide ou deja revoque.', 401, 'invalid_token');
        }

        $this->authLogRepository->log(
            (int) $session['user_id'],
            'logout_success',
            'Deconnexion reussie.',
            $context['ip_address'],
            $context['user_agent']
        );

        return [
            'message' => 'Deconnexion reussie.',
        ];
    }

    private function buildUserPayload(\User $user)
    {
        $payload = $user->toSafeArray();
        $payload['roles'] = $this->userRepository->getRoles($user->getId());

        return $payload;
    }

    private function buildAuthenticatedIdentity(\User $user, $session, array $auth, ?array $apiKey = null)
    {
        $roles = $this->userRepository->getRoles($user->getId());
        $permissions = $this->userRepository->getPermissions($user->getId());

        return [
            'user_entity' => $user,
            'user' => $this->userService->getUserById($user->getId()),
            'roles' => $roles,
            'role_codes' => array_values(array_map(function ($role) {
                return $role['code'];
            }, $roles)),
            'permissions' => $permissions,
            'permission_codes' => array_values(array_map(function ($permission) {
                return $permission['code'];
            }, $permissions)),
            'session' => $session ? [
                'id' => (int) $session['id'],
                'expires_at' => $session['expires_at'],
                'created_at' => $session['created_at'],
            ] : null,
            'auth' => $auth,
            'api_key' => $apiKey,
        ];
    }

    private function nullableTrim(array $payload, $key)
    {
        if (!array_key_exists($key, $payload)) {
            return null;
        }

        $value = trim((string) $payload[$key]);

        return $value === '' ? null : $value;
    }

    private function shouldProtectConsoleLogin(array $payload)
    {
        return trim((string) ($payload['login_scope'] ?? '')) === 'admin_console';
    }

    private function hasAdminConsoleAccess($userId)
    {
        $roleCodes = $this->userRepository->getRoleCodes($userId);

        return in_array('ADMIN', $roleCodes, true) || in_array('SUPER_ADMIN', $roleCodes, true);
    }
}
