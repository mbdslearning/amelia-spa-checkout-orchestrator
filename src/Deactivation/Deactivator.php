<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Deactivation;

final class Deactivator
{
    public function deactivate(): void
    {
        flush_rewrite_rules();
    }
}
