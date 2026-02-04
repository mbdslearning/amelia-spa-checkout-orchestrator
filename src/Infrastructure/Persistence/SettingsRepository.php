<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Infrastructure\Persistence;

use Amelia\SpaCheckoutOrchestrator\Support\PluginContext;

final class SettingsRepository
{
    public function __construct(private readonly PluginContext $context)
    {
    }

    /** @return array<string,mixed> */
    public function all(): array
    {
        $opt = get_option($this->context->optionName(), []);
        return is_array($opt) ? $opt : [];
    }

    /** @param array<string,mixed> $settings */
    public function update(array $settings): void
    {
        update_option($this->context->optionName(), $settings, false);
    }

    public function ensureDefaults(): void
    {
        $opt = get_option($this->context->optionName(), null);
        if (is_array($opt)) {
            return;
        }

        $this->update([
            'enabled'       => 'no',
            'slug'          => 'amelia-spa-checkout',
            'fallback_url'  => '',
            'token_ttl'     => 900,
            'debug'         => 'no',
            'retain_data'   => 'no',
            'lock_ttl'      => 300,
            'state_ttl'     => 600,
        ]);
    }

    public function isEnabled(): bool
    {
        $s = $this->all();
        return isset($s['enabled']) && $s['enabled'] === 'yes';
    }

    public function isDebugEnabled(): bool
    {
        $s = $this->all();
        return isset($s['debug']) && $s['debug'] === 'yes';
    }

    public function slug(): string
    {
        $s = $this->all();
        $slug = isset($s['slug']) ? (string) $s['slug'] : 'amelia-spa-checkout';
        $slug = sanitize_title($slug);
        return $slug !== '' ? $slug : 'amelia-spa-checkout';
    }

    public function fallbackUrl(): string
    {
        $s = $this->all();
        $url = isset($s['fallback_url']) ? (string) $s['fallback_url'] : '';
        if ($url !== '') {
            return esc_url_raw($url);
        }
        return function_exists('wc_get_cart_url') ? (string) wc_get_cart_url() : (string) home_url('/');
    }

    public function tokenTtlSeconds(): int
    {
        $s = $this->all();
        $ttl = isset($s['token_ttl']) ? absint($s['token_ttl']) : 900;
        if ($ttl < 120) {
            $ttl = 120;
        }
        if ($ttl > 7200) {
            $ttl = 7200;
        }
        return (int) $ttl;
    }

    public function lockTtlSeconds(): int
    {
        $s = $this->all();
        $ttl = isset($s['lock_ttl']) ? absint($s['lock_ttl']) : 300;
        if ($ttl < 60) {
            $ttl = 60;
        }
        if ($ttl > 1800) {
            $ttl = 1800;
        }
        return (int) $ttl;
    }

    public function stateTtlSeconds(): int
    {
        $s = $this->all();
        $ttl = isset($s['state_ttl']) ? absint($s['state_ttl']) : 600;
        if ($ttl < 60) {
            $ttl = 60;
        }
        if ($ttl > 7200) {
            $ttl = 7200;
        }
        return (int) $ttl;
    }

    public function loggerSource(): string
    {
        return $this->context->loggerSource();
    }
}
