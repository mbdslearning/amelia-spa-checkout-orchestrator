<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Application;

use Amelia\SpaCheckoutOrchestrator\Infrastructure\Persistence\SettingsRepository;

final class TokenPayloadFactory
{
    public function __construct(private readonly SettingsRepository $settings)
    {
    }

    /** @param array<string,string> $contact */
    public function make(array $contact): array
    {
        $ttl = $this->settings->tokenTtlSeconds();
        return [
            'v'   => 1,
            'exp' => time() + $ttl,
            'c'   => [
                'first_name' => sanitize_text_field($contact['first_name'] ?? ''),
                'last_name'  => sanitize_text_field($contact['last_name'] ?? ''),
                'email'      => sanitize_email($contact['email'] ?? ''),
                'phone'      => sanitize_text_field($contact['phone'] ?? ''),
            ],
        ];
    }
}
