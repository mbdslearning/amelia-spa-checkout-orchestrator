<?php
/**
 * Plugin Name: Amelia SPA Checkout Orchestrator (Woo Store API → PayMongo)
 * Description: Headless redirect checkout bridge for Amelia → Woo Cart → Store API Checkout → PayMongo Checkout Session (gateway: paymongo_checkout).
 * Version: 2.0.0
 * Author: Your Team
 * Requires at least: 6.2
 * Requires PHP: 8.1
 * Text Domain: amelia-spa-checkout-orchestrator
 * Domain Path: /languages
 *
 * @package AmeliaSpaCheckoutOrchestrator
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Amelia\SpaCheckoutOrchestrator\Plugin;

define('AMELIA_SPA_CHECKOUT_ORCHESTRATOR_VERSION', '2.0.0');
define('AMELIA_SPA_CHECKOUT_ORCHESTRATOR_FILE', __FILE__);
define('AMELIA_SPA_CHECKOUT_ORCHESTRATOR_DIR', plugin_dir_path(__FILE__));
define('AMELIA_SPA_CHECKOUT_ORCHESTRATOR_URL', plugin_dir_url(__FILE__));

require_once AMELIA_SPA_CHECKOUT_ORCHESTRATOR_DIR . 'vendor/autoload.php';

$plugin = new Plugin();
$plugin->register();

register_activation_hook(__FILE__, static function (): void {
    (new Amelia\SpaCheckoutOrchestrator\Activation\Activator())->activate();
});

register_deactivation_hook(__FILE__, static function (): void {
    (new Amelia\SpaCheckoutOrchestrator\Deactivation\Deactivator())->deactivate();
});
