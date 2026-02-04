<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Infrastructure\WooCommerce;

use Amelia\SpaCheckoutOrchestrator\Contracts\ServiceInterface;
use Amelia\SpaCheckoutOrchestrator\Domain\Security\TokenSigner;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\Logging\Logger;
use WP_REST_Request;
use WC_Order;

final class StoreApiCheckoutHook implements ServiceInterface
{
    public function __construct(
        private readonly TokenSigner $signer,
        private readonly Logger $logger
    ) {
    }

    public function register(): void
    {
        add_action(
            'woocommerce_store_api_checkout_update_order_from_request',
            [$this, 'updateOrderFromRequest'],
            10,
            2
        );
    }

    public function updateOrderFromRequest($order, $request): void
    {
        if (!$order instanceof WC_Order || !$request instanceof WP_REST_Request) {
            return;
        }

        if ($order->get_payment_method() !== 'paymongo_checkout') {
            return;
        }

        $paymentData = $request->get_param('payment_data');
        if (!is_array($paymentData)) {
            return;
        }

        $token = '';
        foreach ($paymentData as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (($row['key'] ?? '') === 'amelia_spa_token') {
                $token = (string) ($row['value'] ?? '');
                break;
            }
        }

        if ($token === '') {
            return;
        }

        $payload = $this->signer->verify(rawurldecode($token));
        if (is_wp_error($payload)) {
            $this->logger->warning('Invalid amelia_spa_token in Store API checkout', ['err' => $payload->get_error_message()]);
            return;
        }

        $order->update_meta_data('_amelia_spa_token', (string) $token);
        $order->save();
    }
}
