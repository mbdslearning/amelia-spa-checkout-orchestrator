<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Activation;

use Amelia\SpaCheckoutOrchestrator\Infrastructure\Persistence\SettingsRepository;
use Amelia\SpaCheckoutOrchestrator\Support\PluginContext;

final class Activator
{
    public function activate(): void
    {
        $context  = new PluginContext();
        $settings = new SettingsRepository($context);
        $settings->ensureDefaults();

        // Rewrite rules.
        $slug = $settings->slug();
        add_rewrite_rule('^' . preg_quote($slug, '#') . '/?$', 'index.php?amelia_spa_checkout_orchestrator=1', 'top');
        add_rewrite_tag('%amelia_spa_checkout_orchestrator%', '1');

        update_option('amelia_spa_checkout_orchestrator_db_version', '1', false);

        flush_rewrite_rules();
    }
}
