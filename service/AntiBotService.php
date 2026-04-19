<?php

require_once __DIR__ . '/../model/ApiException.php';
require_once __DIR__ . '/../repository/AuthLogRepository.php';

class AntiBotService
{
    private $authLogRepository;
    private $secret;
    private $issuer;

    public function __construct()
    {
        $this->authLogRepository = new \AuthLogRepository();
        $configuredSecret = trim((string) getenv('PROJECT_ANTI_BOT_SECRET'));
        $this->secret = $configuredSecret !== ''
            ? $configuredSecret
            : hash('sha256', 'anti-bot|' . (string) getenv('PROJECT_DB_NAME') . '|' . (string) getenv('PROJECT_DB_USER'));
        $this->issuer = trim((string) getenv('PROJECT_ANTI_BOT_ISSUER')) ?: 'banamur_auth_admin_console';
    }

    public function issueConsoleLoginChallenge(array $context)
    {
        $cards = $this->buildCards();
        $targetCard = $cards[random_int(0, count($cards) - 1)];
        $issuedAt = time();
        $expiresAt = $issuedAt + 180;
        $nonce = bin2hex(random_bytes(12));

        return [
            'message' => 'Defi anti-robot pret.',
            'anti_bot' => [
                'brand' => 'Lagune Shield',
                'mode' => 'human-grid',
                'title' => 'Controle anti-robot maison',
                'prompt' => 'Repere la carte ' . $targetCard['tone'] . ', symbole ' . $targetCard['symbol'] . ', ville ' . $targetCard['city'] . ', puis tape son code exact.',
                'token' => $this->encodeToken([
                    'iss' => $this->issuer,
                    'purpose' => 'admin_console_login',
                    'nonce' => $nonce,
                    'fingerprint' => $this->buildFingerprint($context),
                    'answer_hash' => $this->hashAnswer($targetCard['code'], $nonce),
                    'iat' => $issuedAt,
                    'exp' => $expiresAt,
                    'min_wait_seconds' => 3,
                ]),
                'expires_at' => date('Y-m-d H:i:s', $expiresAt),
                'min_wait_seconds' => 3,
                'cards' => $cards,
                'trap_field_name' => 'company_site',
            ],
        ];
    }

    public function validateConsoleLoginGuard(array $payload, array $context)
    {
        $ipAddress = $context['ip_address'] ?? null;
        if ($this->authLogRepository->countRecentByIpAndEvents($ipAddress, [
            'login_failed',
            'anti_bot_failed',
            'anti_bot_rate_limited',
            'anti_bot_honeypot',
        ], 15) >= 10) {
            $this->authLogRepository->log(null, 'anti_bot_rate_limited', 'Trop de tentatives recentes sur le login admin.', $ipAddress, $context['user_agent'] ?? null);
            throw new \ApiException('Trop de tentatives recentes. Attends quelques minutes puis recharge le defi.', 429, 'too_many_attempts');
        }

        if (trim((string) ($payload['company_site'] ?? '')) !== '') {
            $this->authLogRepository->log(null, 'anti_bot_honeypot', 'Tentative bloquee par le champ piege.', $ipAddress, $context['user_agent'] ?? null);
            throw new \ApiException('Verification anti-robot echouee.', 403, 'anti_bot_failed');
        }

        $answer = strtoupper(trim((string) ($payload['anti_bot_answer'] ?? '')));
        $token = trim((string) ($payload['anti_bot_token'] ?? ''));

        if ($answer === '' || $token === '') {
            $this->authLogRepository->log(null, 'anti_bot_failed', 'Defi anti-robot incomplet.', $ipAddress, $context['user_agent'] ?? null);
            throw new \ApiException('Le defi anti-robot doit etre complete avant la connexion admin.', 422, 'anti_bot_required');
        }

        $challenge = $this->decodeToken($token);
        if (($challenge['purpose'] ?? '') !== 'admin_console_login') {
            throw new \ApiException('Defi anti-robot invalide.', 403, 'anti_bot_failed');
        }

        if (!hash_equals((string) ($challenge['fingerprint'] ?? ''), $this->buildFingerprint($context))) {
            $this->authLogRepository->log(null, 'anti_bot_failed', 'Empreinte de defi anti-robot invalide.', $ipAddress, $context['user_agent'] ?? null);
            throw new \ApiException('Ce defi anti-robot ne correspond plus a ce navigateur. Recharge-le.', 403, 'anti_bot_failed');
        }

        if ((int) ($challenge['iat'] ?? 0) + (int) ($challenge['min_wait_seconds'] ?? 0) > time()) {
            $this->authLogRepository->log(null, 'anti_bot_rate_limited', 'Defi resolu trop rapidement.', $ipAddress, $context['user_agent'] ?? null);
            throw new \ApiException('Prends quelques secondes pour resoudre le defi avant de te connecter.', 429, 'anti_bot_too_fast');
        }

        if (!preg_match('/^[A-Z]{3}-\d{3}$/', $answer)) {
            $this->authLogRepository->log(null, 'anti_bot_failed', 'Format de reponse anti-robot invalide.', $ipAddress, $context['user_agent'] ?? null);
            throw new \ApiException('Le code anti-robot doit respecter le format AAA-000.', 422, 'anti_bot_failed');
        }

        if (!hash_equals((string) ($challenge['answer_hash'] ?? ''), $this->hashAnswer($answer, (string) ($challenge['nonce'] ?? '')))) {
            $this->authLogRepository->log(null, 'anti_bot_failed', 'Mauvaise reponse au defi anti-robot.', $ipAddress, $context['user_agent'] ?? null);
            throw new \ApiException('Mauvaise reponse au defi anti-robot. Recharge le controle et recommence.', 403, 'anti_bot_failed');
        }
    }

    private function buildCards()
    {
        $cities = ['Abidjan', 'Bouake', 'Korhogo', 'Yamoussoukro', 'San Pedro', 'Man', 'Daloa', 'Gagnoa'];
        $symbols = ['Lagune', 'Cacao', 'Palmier', 'Masque', 'Baobab', 'Basilique', 'Ivoire', 'Akwaba'];
        $tones = ['corail', 'ebene', 'lagune', 'safran', 'cacao', 'emeraude', 'sable', 'indigo'];
        shuffle($cities);
        shuffle($symbols);
        shuffle($tones);

        $cards = [];
        for ($index = 0; $index < 6; $index++) {
            $cards[] = [
                'code' => strtoupper(substr($cities[$index], 0, 3)) . '-' . str_pad((string) random_int(101, 999), 3, '0', STR_PAD_LEFT),
                'city' => $cities[$index],
                'symbol' => $symbols[$index],
                'tone' => $tones[$index],
            ];
        }

        return $cards;
    }

    private function encodeToken(array $payload)
    {
        $encodedPayload = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, $this->secret, true));

        return $encodedPayload . '.' . $signature;
    }

    private function decodeToken($token)
    {
        $parts = explode('.', (string) $token);
        if (count($parts) !== 2) {
            throw new \ApiException('Defi anti-robot invalide.', 403, 'anti_bot_failed');
        }

        list($encodedPayload, $signature) = $parts;
        $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, $this->secret, true));

        if (!hash_equals($expectedSignature, $signature)) {
            throw new \ApiException('Defi anti-robot invalide.', 403, 'anti_bot_failed');
        }

        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);
        if (!is_array($payload) || ($payload['iss'] ?? null) !== $this->issuer) {
            throw new \ApiException('Defi anti-robot invalide.', 403, 'anti_bot_failed');
        }

        if ((int) ($payload['exp'] ?? 0) <= time()) {
            throw new \ApiException('Le defi anti-robot a expire. Recharge-le.', 403, 'anti_bot_expired');
        }

        return $payload;
    }

    private function hashAnswer($answer, $nonce)
    {
        return hash_hmac('sha256', strtoupper(trim((string) $answer)), $this->secret . '|' . $nonce);
    }

    private function buildFingerprint(array $context)
    {
        return hash('sha256', trim((string) ($context['ip_address'] ?? '')) . '|' . trim((string) ($context['user_agent'] ?? '')));
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
