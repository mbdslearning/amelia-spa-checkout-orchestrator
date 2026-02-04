<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Admin;

use Amelia\SpaCheckoutOrchestrator\Contracts\ServiceInterface;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\Persistence\SettingsRepository;
use Amelia\SpaCheckoutOrchestrator\Support\PluginContext;

final class SettingsPage implements ServiceInterface
{
    private const GROUP = 'amelia_spa_checkout_orchestrator';

    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly PluginContext $context
    ) {
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'settings']);
    }

    public function menu(): void
    {
        add_menu_page(
            __('Amelia SPA Checkout', $this->context->textDomain()),
            __('Amelia SPA Checkout', $this->context->textDomain()),
            'manage_options',
            'amelia-spa-checkout-orchestrator',
            [$this, 'render'],
            'dashicons-randomize',
            58
        );
    }

    public function settings(): void
    {
        register_setting(self::GROUP, $this->context->optionName(), ['sanitize_callback' => [$this, 'sanitize']]);

        add_settings_section(
            'main',
            __('Main Settings', $this->context->textDomain()),
            function (): void {
                echo '<p>' . esc_html__('Headless SPA redirect checkout orchestrator for Amelia → Woo Cart → Store API → PayMongo (paymongo_checkout).', $this->context->textDomain()) . '</p>';
            },
            self::GROUP
        );

        $this->fieldCheckbox('enabled', __('Enable', $this->context->textDomain()));
        $this->fieldText('slug', __('SPA page slug', $this->context->textDomain()), 'amelia-spa-checkout');
        $this->fieldText('fallback_url', __('Fallback URL', $this->context->textDomain()), '');
        $this->fieldNumber('token_ttl', __('Token TTL (seconds)', $this->context->textDomain()), 900, 120, 7200);
        $this->fieldNumber('lock_ttl', __('Lock TTL (seconds)', $this->context->textDomain()), 300, 60, 1800);
        $this->fieldNumber('state_ttl', __('State TTL (seconds)', $this->context->textDomain()), 600, 60, 7200);
        $this->fieldCheckbox('debug', __('Debug logging', $this->context->textDomain()));
        $this->fieldCheckbox('retain_data', __('Retain data on uninstall', $this->context->textDomain()));
    }

    /** @param array<string,mixed> $raw */
    public function sanitize(array $raw): array
    {
        if (!current_user_can('manage_options')) {
            return $this->settings->all();
        }

        return [
            'enabled'      => isset($raw['enabled']) ? 'yes' : 'no',
            'slug'         => sanitize_title((string) ($raw['slug'] ?? 'amelia-spa-checkout')),
            'fallback_url' => esc_url_raw((string) ($raw['fallback_url'] ?? '')),
            'token_ttl'    => $this->clampInt($raw['token_ttl'] ?? 900, 120, 7200),
            'lock_ttl'     => $this->clampInt($raw['lock_ttl'] ?? 300, 60, 1800),
            'state_ttl'    => $this->clampInt($raw['state_ttl'] ?? 600, 60, 7200),
            'debug'        => isset($raw['debug']) ? 'yes' : 'no',
            'retain_data'  => isset($raw['retain_data']) ? 'yes' : 'no',
        ];
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', $this->context->textDomain()));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Amelia SPA Checkout Orchestrator', $this->context->textDomain()) . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::GROUP);
        do_settings_sections(self::GROUP);
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    private function fieldCheckbox(string $key, string $label): void
    {
        add_settings_field(
            $key,
            $label,
            function () use ($key): void {
                $opt = $this->settings->all();
                $checked = isset($opt[$key]) && $opt[$key] === 'yes';
                echo '<label><input type="checkbox" name="' . esc_attr($this->context->optionName()) . '[' . esc_attr($key) . ']" value="yes" ' . checked(true, $checked, false) . ' /> ' . esc_html__('Yes', $this->context->textDomain()) . '</label>';
            },
            self::GROUP,
            'main'
        );
    }

    private function fieldText(string $key, string $label, string $placeholder): void
    {
        add_settings_field(
            $key,
            $label,
            function () use ($key, $placeholder): void {
                $opt = $this->settings->all();
                $val = isset($opt[$key]) ? (string) $opt[$key] : '';
                echo '<input type="text" class="regular-text" name="' . esc_attr($this->context->optionName()) . '[' . esc_attr($key) . ']" value="' . esc_attr($val) . '" placeholder="' . esc_attr($placeholder) . '" />';
            },
            self::GROUP,
            'main'
        );
    }

    private function fieldNumber(string $key, string $label, int $default, int $min, int $max): void
    {
        add_settings_field(
            $key,
            $label,
            function () use ($key, $default, $min, $max): void {
                $opt = $this->settings->all();
                $val = isset($opt[$key]) ? (int) $opt[$key] : $default;
                echo '<input type="number" min="' . esc_attr((string) $min) . '" max="' . esc_attr((string) $max) . '" class="small-text" name="' . esc_attr($this->context->optionName()) . '[' . esc_attr($key) . ']" value="' . esc_attr((string) $val) . '" />';
            },
            self::GROUP,
            'main'
        );
    }

    private function clampInt(mixed $value, int $min, int $max): int
    {
        $n = absint($value);
        if ($n < $min) {
            return $min;
        }
        if ($n > $max) {
            return $max;
        }
        return $n;
    }
}
