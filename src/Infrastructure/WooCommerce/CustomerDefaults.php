<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator\Infrastructure\WooCommerce;

use WC_Customer;

final class CustomerDefaults
{
    /** @return array<string,string> */
    public function billingFromCustomer(?WC_Customer $customer): array
    {
        if (!$customer) {
            return $this->blankBilling();
        }

        return [
            'first_name' => (string) $customer->get_billing_first_name(),
            'last_name'  => (string) $customer->get_billing_last_name(),
            'email'      => (string) $customer->get_billing_email(),
            'phone'      => (string) $customer->get_billing_phone(),
            'address_1'  => (string) $customer->get_billing_address_1(),
            'address_2'  => (string) $customer->get_billing_address_2(),
            'city'       => (string) $customer->get_billing_city(),
            'postcode'   => (string) $customer->get_billing_postcode(),
            'country'    => (string) $customer->get_billing_country(),
            'state'      => (string) $customer->get_billing_state(),
        ];
    }

    /** @return array<string,string> */
    public function storeAddressDefault(): array
    {
        $country = (string) get_option('woocommerce_default_country', '');
        $state   = '';
        if (str_contains($country, ':')) {
            [$country, $state] = explode(':', $country, 2);
        }

        return [
            'first_name' => '',
            'last_name'  => '',
            'email'      => '',
            'phone'      => '',
            'address_1'  => (string) get_option('woocommerce_store_address', ''),
            'address_2'  => (string) get_option('woocommerce_store_address_2', ''),
            'city'       => (string) get_option('woocommerce_store_city', ''),
            'postcode'   => (string) get_option('woocommerce_store_postcode', ''),
            'country'    => $country,
            'state'      => $state,
        ];
    }

    /** @return array<string,string> */
    private function blankBilling(): array
    {
        return [
            'first_name' => '',
            'last_name'  => '',
            'email'      => '',
            'phone'      => '',
            'address_1'  => '',
            'address_2'  => '',
            'city'       => '',
            'postcode'   => '',
            'country'    => '',
            'state'      => '',
        ];
    }
}
