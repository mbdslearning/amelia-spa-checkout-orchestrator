<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Support;

final class PluginContext
{
    public function version(): string
    {
        return (string) (defined('AMELIA_SPA_CHECKOUT_ORCHESTRATOR_VERSION') ? AMELIA_SPA_CHECKOUT_ORCHESTRATOR_VERSION : '0.0.0');
    }

    public function file(): string
    {
        return (string) (defined('AMELIA_SPA_CHECKOUT_ORCHESTRATOR_FILE') ? AMELIA_SPA_CHECKOUT_ORCHESTRATOR_FILE : '');
    }

    public function dir(): string
    {
        return (string) (defined('AMELIA_SPA_CHECKOUT_ORCHESTRATOR_DIR') ? AMELIA_SPA_CHECKOUT_ORCHESTRATOR_DIR : '');
    }

    public function url(): string
    {
        return (string) (defined('AMELIA_SPA_CHECKOUT_ORCHESTRATOR_URL') ? AMELIA_SPA_CHECKOUT_ORCHESTRATOR_URL : '');
    }

    public function optionName(): string
    {
        return 'amelia_spa_checkout_orchestrator_settings';
    }

    public function textDomain(): string
    {
        return 'amelia-spa-checkout-orchestrator';
    }

    public function loggerSource(): string
    {
        return 'amelia-spa-checkout-orchestrator';
    }
}
