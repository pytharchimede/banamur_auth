<?php

require_once __DIR__ . '/../model/ApiException.php';

class JwtService
{
    private $secret;
    private $issuer;

    public function __construct()
    {
        $configuredSecret = trim((string) getenv('PROJECT_JWT_SECRET'));
        $this->secret = $configuredSecret !== ''
            ? $configuredSecret
            : hash('sha256', (string) getenv('PROJECT_DB_NAME') . '|' . (string) getenv('PROJECT_DB_USER') . '|banamur_auth');
        $this->issuer = trim((string) getenv('PROJECT_JWT_ISSUER')) ?: 'banamur_auth';
    }

    public function createAccessToken($userId, $sessionId, $expiresAt)
    {
        $payload = [
            'iss' => $this->issuer,
            'sub' => (string) $userId,
            'sid' => (int) $sessionId,
            'iat' => time(),
            'exp' => strtotime((string) $expiresAt),
            'jti' => bin2hex(random_bytes(16)),
            'token_use' => 'access',
        ];

        return $this->encode($payload);
    }

    public function decodeAccessToken($token)
    {
        $payload = $this->decode($token);

        if (($payload['token_use'] ?? null) !== 'access') {
            throw new \ApiException('JWT invalide.', 401, 'invalid_token');
        }

        if (empty($payload['sub']) || empty($payload['sid'])) {
            throw new \ApiException('JWT invalide.', 401, 'invalid_token');
        }

        return $payload;
    }

    public function looksLikeJwt($token)
    {
        return is_string($token) && substr_count($token, '.') === 2;
    }

    private function encode(array $payload)
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $signatureEncoded = $this->base64UrlEncode(
            hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $this->secret, true)
        );

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    private function decode($token)
    {
        if (!$this->looksLikeJwt($token)) {
            throw new \ApiException('JWT invalide.', 401, 'invalid_token');
        }

        $parts = explode('.', (string) $token);
        if (count($parts) !== 3) {
            throw new \ApiException('JWT invalide.', 401, 'invalid_token');
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        $header = json_decode($this->base64UrlDecode($headerEncoded), true);
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $this->secret, true)
        );

        if (!is_array($header) || !is_array($payload)) {
            throw new \ApiException('JWT invalide.', 401, 'invalid_token');
        }

        if (($header['alg'] ?? null) !== 'HS256') {
            throw new \ApiException('JWT invalide.', 401, 'invalid_token');
        }

        if (!hash_equals($expectedSignature, $signatureEncoded)) {
            throw new \ApiException('JWT invalide.', 401, 'invalid_token');
        }

        if (!isset($payload['exp']) || (int) $payload['exp'] <= time()) {
            throw new \ApiException('JWT expire.', 401, 'invalid_token');
        }

        return $payload;
    }

    private function base64UrlEncode($value)
    {
        return rtrim(strtr(base64_encode((string) $value), '+/', '-_'), '=');
    }

    private function base64UrlDecode($value)
    {
        $remainder = strlen((string) $value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr((string) $value, '-_', '+/'));
    }
}
