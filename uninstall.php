<?php
/**
 * Uninstall handler.
 *
 * @package AmeliaSpaCheckoutOrchestrator
 */

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$optionName = 'amelia_spa_checkout_orchestrator_settings';
$settings   = get_option($optionName, []);

$retain = is_array($settings) && isset($settings['retain_data']) && $settings['retain_data'] === 'yes';
if ($retain) {
    return;
}

delete_option($optionName);
delete_option('amelia_spa_checkout_orchestrator_db_version');
