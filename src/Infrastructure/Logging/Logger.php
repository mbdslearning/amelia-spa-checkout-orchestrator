<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Infrastructure\Logging;

use Amelia\SpaCheckoutOrchestrator\Infrastructure\Persistence\SettingsRepository;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

final class Logger extends AbstractLogger
{
    public function __construct(
        private readonly SettingsRepository $settings
    ) {
    }

    public function log($level, $message, array $context = []): void
    {
        if (!$this->settings->isDebugEnabled()) {
            return;
        }

        $safeContext = $this->redact($context);
        $line        = (string) $message;

        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->log((string) $level, $line . ' ' . wp_json_encode($safeContext), ['source' => $this->settings->loggerSource()]);
            return;
        }

        $level = in_array((string) $level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG], true)
            ? (string) $level
            : LogLevel::INFO;

        error_log('[' . $this->settings->loggerSource() . '][' . $level . '] ' . $line . ' ' . wp_json_encode($safeContext));
    }

    /** @param array<string,mixed> $context */
    private function redact(array $context): array
    {
        $keys = [
            'token',
            'cart_token',
            'Cart-Token',
            'authorization',
            'Authorization',
            'secret',
            'api_key',
            'webhook_secret',
            'email',
            'phone',
        ];

        foreach ($keys as $k) {
            if (array_key_exists($k, $context)) {
                $context[$k] = '[redacted]';
            }
        }

        return $context;
    }
}
