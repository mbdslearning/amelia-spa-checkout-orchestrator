<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Domain\Security;

use WP_Error;

final class TokenSigner
{
    private const ALG = 'sha256';

    public function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return $decoded !== false ? $decoded : '';
    }

    public function sign(array $payload): string
    {
        $json = wp_json_encode($payload);
        $body = $this->base64UrlEncode($json !== false ? $json : '{}');
        $sig  = hash_hmac(self::ALG, $body, $this->hmacKey(), true);
        return $body . '.' . $this->base64UrlEncode($sig);
    }

    /** @return array<string,mixed>|WP_Error */
    public function verify(string $token)
    {
        $token = trim($token);
        if ($token === '' || !str_contains($token, '.')) {
            return new WP_Error('bad_token', 'Malformed token.');
        }

        [$body, $sig] = explode('.', $token, 2);
        if ($body === '' || $sig === '') {
            return new WP_Error('bad_token', 'Malformed token.');
        }

        $expected = hash_hmac(self::ALG, $body, $this->hmacKey(), true);
        $given    = $this->base64UrlDecode($sig);

        if ($given === '' || !hash_equals($expected, $given)) {
            return new WP_Error('bad_token', 'Invalid token signature.');
        }

        $json = $this->base64UrlDecode($body);
        if ($json === '') {
            return new WP_Error('bad_token', 'Invalid token payload.');
        }

        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            return new WP_Error('bad_token', 'Invalid token payload.');
        }

        if (isset($payload['exp']) && is_numeric($payload['exp']) && time() > (int) $payload['exp']) {
            return new WP_Error('expired_token', 'Token expired.');
        }

        return $payload;
    }

    private function hmacKey(): string
    {
        // Stable derived key; independent of runtime salts rotation constraints.
        $salt = (string) wp_salt('amelia_spa_checkout_orchestrator');
        return hash('sha256', $salt, true);
    }
}
