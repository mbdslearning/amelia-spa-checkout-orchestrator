<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Infrastructure\WooCommerce;

use Amelia\SpaCheckoutOrchestrator\Application\TokenPayloadFactory;
use Amelia\SpaCheckoutOrchestrator\Contracts\ServiceInterface;
use Amelia\SpaCheckoutOrchestrator\Domain\Security\TokenSigner;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\Logging\Logger;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\Persistence\SettingsRepository;
use Amelia\SpaCheckoutOrchestrator\Support\Arr;

final class AmeliaRedirectAdapter implements ServiceInterface
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly TokenPayloadFactory $payloadFactory,
        private readonly TokenSigner $signer,
        private readonly Logger $logger
    ) {
    }

    public function register(): void
    {
        add_filter('amelia_wc_redirect_page', [$this, 'filterRedirect'], 10, 2);
    }

    public function filterRedirect($redirectUrl, $appointmentData)
    {
        if (!$this->settings->isEnabled()) {
            return $redirectUrl;
        }

        $appointment = is_array($appointmentData) ? $appointmentData : [];
        $contact     = $this->extractContact($appointment);

        $payload  = $this->payloadFactory->make($contact);
        $token    = $this->signer->sign($payload);
        $endpoint = home_url('/' . $this->settings->slug() . '/');
        $endpoint = add_query_arg(['ameliaSpaToken' => rawurlencode($token)], $endpoint);

        $this->logger->info('Amelia redirect rewritten to SPA endpoint', []);
        return $endpoint;
    }

    /** @return array<string,string> */
    private function extractContact(array $appointment): array
    {
        $email = Arr::deepFindString($appointment, [
            'email',
            'customerEmail',
            'customer_email',
            'customer_email_address',
        ]);

        $firstName = Arr::deepFindString($appointment, [
            'firstName',
            'first_name',
            'customerFirstName',
            'customer_first_name',
        ]);

        $lastName = Arr::deepFindString($appointment, [
            'lastName',
            'last_name',
            'customerLastName',
            'customer_last_name',
        ]);

        $phone = Arr::deepFindString($appointment, [
            'phone',
            'customerPhone',
            'customer_phone',
        ]);

        return [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'phone'      => $phone,
        ];
    }
}
