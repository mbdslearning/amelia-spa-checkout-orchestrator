<?php
/**
 * Endpoint template (intentionally blank visual output).
 *
 * @package AmeliaSpaCheckoutOrchestrator
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/** @var array<string,mixed> $bootstrap */
$bootstrap = $bootstrap ?? [];

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <?php wp_head(); ?>
  <style>
    html, body { margin: 0; padding: 0; background: transparent; }
  </style>
</head>
<body>
  <div id="amelia-spa-checkout-orchestrator-bootstrap" data-bootstrap="<?php echo esc_attr(wp_json_encode($bootstrap)); ?>"></div>
  <?php wp_footer(); ?>
</body>
</html>
