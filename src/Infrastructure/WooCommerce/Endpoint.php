<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Infrastructure\WooCommerce;

use Amelia\SpaCheckoutOrchestrator\Contracts\ServiceInterface;
use Amelia\SpaCheckoutOrchestrator\Domain\Security\TokenSigner;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\Logging\Logger;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\Persistence\SettingsRepository;
use Amelia\SpaCheckoutOrchestrator\Support\PluginContext;
use WP_Error;
use WP_Query;

final class Endpoint implements ServiceInterface
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly TokenSigner $signer,
        private readonly Logger $logger,
        private readonly PluginContext $context,
        private readonly CustomerDefaults $defaults
    ) {
    }

    public function register(): void
    {
        add_action('init', [$this, 'addRewrite']);
        add_action('template_redirect', [$this, 'maybeRender'], 0);
        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
    }

    public function addRewrite(): void
    {
        $slug = $this->settings->slug();
        add_rewrite_rule('^' . preg_quote($slug, '#') . '/?$', 'index.php?amelia_spa_checkout_orchestrator=1', 'top');
        add_rewrite_tag('%amelia_spa_checkout_orchestrator%', '1');
    }

    public function registerAssets(): void
    {
        $assetFile = $this->context->dir() . 'assets/build/endpoint.asset.php';
        $asset     = file_exists($assetFile) ? require $assetFile : ['dependencies' => [], 'version' => $this->context->version()];

        wp_register_script(
            'amelia-spa-checkout-orchestrator-endpoint',
            $this->context->url() . 'assets/build/endpoint.js',
            $asset['dependencies'] ?? [],
            (string) ($asset['version'] ?? $this->context->version()),
            true
        );
    }

    public function maybeRender(): void
    {
        if ((string) get_query_var('amelia_spa_checkout_orchestrator', '') !== '1') {
            return;
        }

        if (!$this->settings->isEnabled()) {
            wp_safe_redirect($this->settings->fallbackUrl());
            exit;
        }

        $token = isset($_GET['ameliaSpaToken']) ? (string) wp_unslash($_GET['ameliaSpaToken']) : '';
        $token = rawurldecode($token);

        $payload = $this->signer->verify($token);
        if (is_wp_error($payload)) {
            $this->logger->warning('Invalid/expired token on endpoint', ['err' => $payload->get_error_message()]);
            wp_safe_redirect($this->settings->fallbackUrl());
            exit;
        }

        $store = $this->defaults->storeAddressDefault();

        $customer = null;
        if (function_exists('WC') && WC() && isset(WC()->customer) && WC()->customer) {
            $customer = WC()->customer;
        }
        $custBilling = $this->defaults->billingFromCustomer($customer);

        $contact = is_array($payload['c'] ?? null) ? $payload['c'] : [];

        $bootstrap = [
            'token'           => $token,
            'contact'         => [
                'first_name' => (string) ($contact['first_name'] ?? ''),
                'last_name'  => (string) ($contact['last_name'] ?? ''),
                'email'      => (string) ($contact['email'] ?? ''),
                'phone'      => (string) ($contact['phone'] ?? ''),
            ],
            'defaults'        => [
                'store'    => $store,
                'customer' => $custBilling,
            ],
            'rest'            => [
                'lockUrl'   => esc_url_raw(rest_url(\Amelia\SpaCheckoutOrchestrator\Rest\LockController::NAMESPACE . '/lock')),
                'nonce'     => wp_create_nonce('wp_rest'),
            ],
            'settings'        => [
                'slug'         => $this->settings->slug(),
                'fallback_url' => $this->settings->fallbackUrl(),
            ],
        ];

        status_header(200);
        nocache_headers();

        wp_enqueue_script('amelia-spa-checkout-orchestrator-endpoint');

        $template = $this->context->dir() . 'templates/endpoint.php';
        $bootstrapVar = $bootstrap;

        // Isolate template scope.
        (static function (string $templatePath, array $bootstrap) : void {
            require $templatePath;
        })($template, $bootstrapVar);

        exit;
    }
}
