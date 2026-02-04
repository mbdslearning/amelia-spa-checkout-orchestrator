<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Blocks;

use Amelia\SpaCheckoutOrchestrator\Contracts\ServiceInterface;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\Persistence\SettingsRepository;
use Amelia\SpaCheckoutOrchestrator\Support\PluginContext;

final class CheckoutLinkBlock implements ServiceInterface
{
    public function __construct(
        private readonly PluginContext $context,
        private readonly SettingsRepository $settings
    ) {
    }

    public function register(): void
    {
        add_action('init', [$this, 'blocks']);
    }

    public function blocks(): void
    {
        register_block_type($this->context->dir() . 'src/Blocks/block.json', [
            'render_callback' => [$this, 'render'],
        ]);
    }

    /** @param array<string,mixed> $attributes */
    public function render(array $attributes, string $content): string
    {
        $label = isset($attributes['label']) ? sanitize_text_field((string) $attributes['label']) : __('Checkout', $this->context->textDomain());

        $url = home_url('/' . $this->settings->slug() . '/');
        return '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }
}
