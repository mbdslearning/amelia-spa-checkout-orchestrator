<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Infrastructure\Cron;

use Amelia\SpaCheckoutOrchestrator\Contracts\ServiceInterface;

/**
 * Ensure Action Scheduler is available (WooCommerce bundles it).
 * This service only registers our hooks. Scheduling is explicit and idempotent.
 */
final class ActionSchedulerBootstrap implements ServiceInterface
{
    public const HOOK_PURGE_TRANSIENTS = 'amelia_spa_checkout_orchestrator/purge_transients';

    public function register(): void
    {
        add_action(self::HOOK_PURGE_TRANSIENTS, [$this, 'purge'], 10, 1);
    }

    /** @param array<string,string> $args */
    public function purge(array $args): void
    {
        $lockKey  = isset($args['lock_key']) ? (string) $args['lock_key'] : '';
        $stateKey = isset($args['state_key']) ? (string) $args['state_key'] : '';

        if ($lockKey !== '') {
            delete_transient($lockKey);
        }
        if ($stateKey !== '') {
            delete_transient($stateKey);
        }
    }
}
