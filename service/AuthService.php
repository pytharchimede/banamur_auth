<?php

require_once __DIR__ . '/../model/ApiException.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/AuthSessionRepository.php';
require_once __DIR__ . '/../repository/AuthLogRepository.php';
require_once __DIR__ . '/../service/UserService.php';

class AuthService
{
    private $userRepository;
    private $authSessionRepository;
    private $authLogRepository;
    private $userService;

    public function __construct()
    {
        $this->userRepository = new \UserRepository();
        $this->authSessionRepository = new \AuthSessionRepository();
        $this->authLogRepository = new \AuthLogRepository();
        $this->userService = new \UserService();
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

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));

        $this->authSessionRepository->create(
            $user->getId(),
            $tokenHash,
            $context['ip_address'],
            $context['user_agent'],
            $expiresAt
        );

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
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt,
            'user' => $this->buildUserPayload($freshUser),
        ];
    }

    public function getAuthenticatedUser($token)
    {
        $identity = $this->authenticateToken($token);

        return [
            'message' => 'Utilisateur authentifie.',
            'user' => $identity['user'],
            'session' => [
                'expires_at' => $identity['session']['expires_at'],
                'created_at' => $identity['session']['created_at'],
            ],
        ];
    }

    public function authenticateToken($token)
    {
        $token = trim((string) $token);
        if ($token === '') {
            throw new \ApiException('Jeton manquant.', 401, 'missing_token');
        }

        $session = $this->authSessionRepository->findActiveByToken($token);
        if (!$session) {
            throw new \ApiException('Jeton invalide ou expire.', 401, 'invalid_token');
        }

        $user = $this->userRepository->findById((int) $session['user_id']);
        if (!$user) {
            throw new \ApiException('Utilisateur introuvable.', 404, 'user_not_found');
        }

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
            'session' => $session,
        ];
    }

    public function logout($token, array $context)
    {
        $token = trim((string) $token);
        if ($token === '') {
            throw new \ApiException('Jeton manquant.', 401, 'missing_token');
        }

        $session = $this->authSessionRepository->findActiveByTokenHash(hash('sha256', $token));
        if (!$session) {
            throw new \ApiException('Jeton invalide ou deja revoque.', 401, 'invalid_token');
        }

        $this->authSessionRepository->revokeByTokenHash(hash('sha256', $token));
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

    private function nullableTrim(array $payload, $key)
    {
        if (!array_key_exists($key, $payload)) {
            return null;
        }

        $value = trim((string) $payload[$key]);

        return $value === '' ? null : $value;
    }
}
