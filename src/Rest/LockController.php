<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Rest;

use Amelia\SpaCheckoutOrchestrator\Contracts\ServiceInterface;
use Amelia\SpaCheckoutOrchestrator\Domain\Security\TokenSigner;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\Logging\Logger;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\Persistence\SettingsRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class LockController implements ServiceInterface
{
    public const NAMESPACE = 'amelia-spa/v2';

    public function __construct(
        private readonly TokenSigner $signer,
        private readonly SettingsRepository $settings,
        private readonly Logger $logger
    ) {
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'routes']);
    }

    public function routes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/lock',
            [
                'methods'             => 'POST',
                'permission_callback' => [$this, 'permission'],
                'callback'            => [$this, 'lock'],
                'args'                => [
                    'token'        => ['required' => true, 'type' => 'string'],
                    'stage'        => ['required' => false, 'type' => 'string'],
                    'redirect_url' => ['required' => false, 'type' => 'string'],
                ],
            ]
        );
    }

    public function permission(WP_REST_Request $request): bool
    {
        // This endpoint is intentionally public but requires a valid signed token.
        $token = (string) $request->get_param('token');
        $token = rawurldecode($token);

        $payload = $this->signer->verify($token);
        return !is_wp_error($payload);
    }

    public function lock(WP_REST_Request $request): WP_REST_Response
    {
        $token = rawurldecode((string) $request->get_param('token'));
        $payload = $this->signer->verify($token);

        if (is_wp_error($payload)) {
            return new WP_REST_Response(['ok' => false, 'reason' => 'bad_token'], 400);
        }

        $lockKey  = $this->lockKey($token);
        $stateKey = $this->stateKey($token);

        $lock = get_transient($lockKey);
        if (!is_array($lock)) {
            $lock = [
                'created_at' => time(),
                'stage'      => '',
            ];
            set_transient($lockKey, $lock, $this->settings->lockTtlSeconds());
        }

        $stage = (string) $request->get_param('stage');
        $redir = (string) $request->get_param('redirect_url');

        $state = get_transient($stateKey);
        if (!is_array($state)) {
            $state = [];
        }

        $changed = false;
        if ($stage !== '') {
            $state['stage'] = sanitize_key($stage);
            $changed = true;
        }
        if ($redir !== '') {
            $state['redirect_url'] = esc_url_raw($redir);
            $changed = true;
        }
        if ($changed) {
            set_transient($stateKey, $state, $this->settings->stateTtlSeconds());
        }

        return new WP_REST_Response([
            'ok'       => true,
            'lock'     => $lock,
            'state'    => $state,
            'lock_key' => $lockKey,   // returned for integrator diagnostics; not secret.
            'state_key'=> $stateKey,  // returned for integrator diagnostics; not secret.
        ], 200);
    }

    private function lockKey(string $token): string
    {
        return 'amelia_spa_lock_' . substr(hash('sha256', $token), 0, 32);
    }

    private function stateKey(string $token): string
    {
        return 'amelia_spa_state_' . substr(hash('sha256', $token), 0, 32);
    }
}
