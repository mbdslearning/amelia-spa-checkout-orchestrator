<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Tests\Unit;

use Amelia\SpaCheckoutOrchestrator\Domain\Security\TokenSigner;
use PHPUnit\Framework\TestCase;

final class TokenSignerTest extends TestCase
{
    public function testSignAndVerifyRoundTrip(): void
    {
        $signer = new TokenSigner();
        $token  = $signer->sign(['v' => 1, 'exp' => time() + 60, 'c' => ['email' => 'a@example.com']]);

        $payload = $signer->verify($token);

        $this->assertIsArray($payload);
        $this->assertSame(1, $payload['v']);
        $this->assertSame('a@example.com', $payload['c']['email']);
    }
}
