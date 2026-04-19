<?php

require_once __DIR__ . '/../model/ApiException.php';
require_once __DIR__ . '/../repository/ApiKeyRepository.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/AuthLogRepository.php';

class ApiKeyService
{
    private $apiKeyRepository;
    private $userRepository;
    private $authLogRepository;

    public function __construct()
    {
        $this->apiKeyRepository = new \ApiKeyRepository();
        $this->userRepository = new \UserRepository();
        $this->authLogRepository = new \AuthLogRepository();
    }

    public function listKeys(array $filters = [])
    {
        return array_map(function ($apiKey) {
            return $this->buildApiKeyPayload($apiKey);
        }, $this->apiKeyRepository->findAll($filters));
    }

    public function createKey(array $payload, array $identity, array $context)
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $targetUserId = (int) ($payload['user_id'] ?? $identity['user_entity']->getId());

        if ($name === '') {
            throw new \ApiException('Le nom de la cle API est obligatoire.', 422, 'validation_error');
        }

        $targetUser = $this->userRepository->findById($targetUserId);
        if (!$targetUser) {
            throw new \ApiException('Utilisateur cible introuvable.', 404, 'user_not_found');
        }

        $plainKey = 'ban_' . bin2hex(random_bytes(24));
        $keyId = $this->apiKeyRepository->create(
            $targetUser->getId(),
            $name,
            substr($plainKey, 0, 12),
            hash('sha256', $plainKey),
            $this->normalizeExpiresAt($payload)
        );

        $apiKey = $this->apiKeyRepository->findById($keyId);

        $this->authLogRepository->log(
            $identity['user_entity']->getId(),
            'api_key_created',
            'Cle API creee pour ' . $targetUser->getEmail() . '.',
            $context['ip_address'] ?? null,
            $context['user_agent'] ?? null
        );

        return [
            'message' => 'Cle API creee avec succes. Copie-la maintenant: elle ne sera plus reaffichee en clair.',
            'plain_key' => $plainKey,
            'api_key' => $this->buildApiKeyPayload($apiKey),
        ];
    }

    public function revokeKey($apiKeyId, array $identity, array $context)
    {
        $apiKey = $this->apiKeyRepository->findById($apiKeyId);
        if (!$apiKey) {
            throw new \ApiException('Cle API introuvable.', 404, 'api_key_not_found');
        }

        if (!$this->apiKeyRepository->revokeById($apiKeyId)) {
            throw new \ApiException('Cette cle API est deja revoquee.', 409, 'api_key_already_revoked');
        }

        $this->authLogRepository->log(
            $identity['user_entity']->getId(),
            'api_key_revoked',
            'Cle API revoquee: ' . $apiKey['name'] . '.',
            $context['ip_address'] ?? null,
            $context['user_agent'] ?? null
        );

        return [
            'message' => 'Cle API revoquee avec succes.',
        ];
    }

    private function buildApiKeyPayload(array $apiKey)
    {
        return [
            'id' => (int) $apiKey['id'],
            'user_id' => (int) $apiKey['user_id'],
            'name' => $apiKey['name'],
            'key_prefix' => $apiKey['key_prefix'],
            'masked_key' => $apiKey['key_prefix'] . '...' . substr(hash('sha256', (string) $apiKey['key_prefix']), 0, 6),
            'last_used_at' => $apiKey['last_used_at'],
            'expires_at' => $apiKey['expires_at'],
            'revoked_at' => $apiKey['revoked_at'],
            'created_at' => $apiKey['created_at'],
            'user' => [
                'username' => $apiKey['username'],
                'email' => $apiKey['email'],
                'first_name' => $apiKey['first_name'],
                'last_name' => $apiKey['last_name'],
                'status' => $apiKey['status'],
            ],
        ];
    }

    private function normalizeExpiresAt(array $payload)
    {
        $expiresAt = trim((string) ($payload['expires_at'] ?? ''));
        if ($expiresAt !== '') {
            $timestamp = strtotime($expiresAt);
            if ($timestamp === false) {
                throw new \ApiException('Format expires_at invalide.', 422, 'validation_error');
            }

            return date('Y-m-d H:i:s', $timestamp);
        }

        $expiresInDays = trim((string) ($payload['expires_in_days'] ?? ''));
        if ($expiresInDays === '') {
            return null;
        }

        $days = (int) $expiresInDays;
        if ($days < 1 || $days > 365) {
            throw new \ApiException('expires_in_days doit etre compris entre 1 et 365.', 422, 'validation_error');
        }

        return date('Y-m-d H:i:s', strtotime('+' . $days . ' days'));
    }
}
