<?php

namespace Epayco\Woocommerce\Hooks;


use Exception;
use Epayco\Woocommerce\Gateways\AbstractGateway;
use Epayco\Woocommerce\Helpers\Url;
use Epayco\Woocommerce\Funnel\Funnel;
use Epayco\Woocommerce\Configs\Store;


if (!defined('ABSPATH')) {
    exit;
}
class Gateway
{
    public const GATEWAY_ICON_FILTER = 'woo_epayco_icon';
    private Store $store;
    private Url $url;
    private Funnel $funnel;

    /**
     * Gateway constructor
     * @param Store $store
     * @param Url $url
     * @param Funnel $funnel
     */
      public function __construct(
          Store $store,
          Url $url,
          Funnel $funnel
      ) {
          $this->store        = $store;
          $this->url          = $url;
          $this->funnel       = $funnel;
      }
  /**
     * Verify if gateway is enabled and available
     *
     * @param AbstractGateway $gateway
     *
     * @return bool
     */    public function isEnabled(AbstractGateway $gateway): bool
    {
        return $gateway->is_available();
    }

    /**
     * Register gateway on Woocommerce if it is valid
     *
     * @param string $gatewayClass
     *
     * @return void
     */
    public function registerGateway(string $gatewayClass): void
    {
        if (call_user_func([$gatewayClass, 'isAvailable'])) {
            $this->store->addAvailablePaymentGateway($gatewayClass);

            add_filter('woocommerce_payment_gateways', function ($methods) use ($gatewayClass) {
                $methods[] = $gatewayClass;
                return $methods;
            });
        }
    }

    /**
     * Register gateway title
     *
     * @param AbstractGateway $gateway
     *
     * @return void
     * @throws Exception
     */
    public function registerGatewayTitle(AbstractGateway $gateway): void
    {
        add_filter('woocommerce_gateway_title', function ($title, $id) use ($gateway) {
            if (!preg_match('/woo-epayco/', $id)) {
                return $title;
            }

            if ($gateway->id !== $id) {
                return $title;
            }

            return $title;
        }, 10, 2);
    }

    /**
     * Register update options
     *
     * @param AbstractGateway $gateway
     *
     * @return void
     */
    public function registerUpdateOptions(AbstractGateway $gateway): void
    {
        add_action('woocommerce_update_options_payment_gateways_' . $gateway->id, function () use ($gateway) {
            $gateway->init_settings();

            $postData   = $gateway->get_post_data();
            $formFields = $this->getCustomFormFields($gateway);

            foreach ($formFields as $key => $field) {
                if ($gateway->get_field_type($field) !== 'config_title') {
                    $gateway->settings[$key] = $gateway->get_field_value($key, $field, $postData);
                }
            }

            $optionKey       = $gateway->get_option_key();
            $sanitizedFields = apply_filters('woocommerce_settings_api_sanitized_fields_' . $gateway->id, $gateway->settings);
            update_option($optionKey, $sanitizedFields);

        });
    }

    /**
     * Add action for checkout tab on settings in woocommerce
     *
     * @return void
     */
    public function registerSaveCheckoutSettings(): void
    {
        if (empty($this->url->getCurrentSection())) {
            add_action('woocommerce_settings_save_checkout', function () {
                $this->funnel->updateStepPaymentMethods();
            });
        }
    }

    /**
     * Register checkout custom billing fields
     *
     * @param AbstractGateway $gateway
     *
     * @return void
     */
    public function registerCustomBillingFieldOptions(): void
    {
        add_filter( 'woocommerce_checkout_fields', function( $fields ) {
            $fields['billing']['billing_custom_field'] = array(
                'type'        => 'text',
                'label'       => 'Campo Personalizado',
                'placeholder' => 'Escribe algo...',
                'required'    => true,
                'class'       => array( 'form-row-wide' ),
                'clear'       => true,
            );

            return $fields;
        });
    }

    /**
     * Register gateway receipt
     *
     * @param string $id
     * @param mixed $callback
     * @return void
     */
    public function registerGatewayReceiptPage(string $id, $callback): void
    {
        add_action('woocommerce_receipt_' . $id, $callback);
    }


    /**
     * Handles custom components for better integration with native hooks
     *
     * @param $gateway
     *
     * @return array
     */
    public function getCustomFormFields($gateway): array
    {
        $formFields = $gateway->get_form_fields();

        foreach ($formFields as $key => $field) {
            if ('mp_checkbox_list' === $field['type']) {
                $formFields += $this->separateCheckboxes($formFields[$key]);
                unset($formFields[$key]);
            }

            if ('mp_actionable_input' === $field['type'] && !isset($formFields[$key . '_checkbox'])) {
                $formFields[$key . '_checkbox'] = ['type' => 'checkbox'];
            }

            if ('mp_toggle_switch' === $field['type']) {
                $formFields[$key]['type'] = 'checkbox';
            }
        }

        return $formFields;
    }

    /**
     * Separates multiple exPayments checkbox into an array
     *
     * @param array $exPayments
     *
     * @return array
     */
    public function separateCheckboxes(array $exPayments): array
    {
        $paymentMethods = [];

        foreach ($exPayments['payment_method_types'] as $paymentMethodsType) {
            $paymentMethods += $this->separateCheckboxesList($paymentMethodsType['list']);
        }

        return $paymentMethods;
    }

    /**
    * Separates multiple exPayments checkbox into an array
    *
    * @param array $exPaymentsList
    *
    * @return array
    */
    public function separateCheckboxesList(array $exPaymentsList): array
    {
        $paymentMethods = [];

        foreach ($exPaymentsList as $payment) {
            $paymentMethods[$payment['id']] = $payment;
        }

        return $paymentMethods;
    }

    /**
     * Register thank you page
     *
     * @param string $id
     * @param mixed $callback
     *
     * @return void
     */
    public function registerThankYouPage(string $id, $callback): void
    {
        add_action('woocommerce_thankyou_' . $id, $callback);
    }

    /**
     * Register available payment gateways
     *
     * @return void
     */
    public function registerAvailablePaymentGateway(): void
    {
        add_filter('woocommerce_available_payment_gateways', function ($methods) {
            $enable = true;
            if (class_exists('WC_Subscriptions_Cart')) {
                $enable = \WC_Subscriptions_Cart::cart_contains_subscription();
            }

            if (!$enable && isset($methods['woo-epayco-subscription'])){
                unset($methods['woo-epayco-subscription']);
            }
            return $methods;
        });
    }

    /**
     * Get gateway icon
     *
     * @param string $iconName
     *
     * @return string
     */
    public function getGatewayIcon(string $iconName): string
    {
        $path = $this->url->getPluginFileUrl("assets/images/icons/$iconName", '.png', true);
        return apply_filters(self::GATEWAY_ICON_FILTER, $path);
    }

    /**
     * Register gateway receipt
     *
     * @param string $id
     * @param mixed $callback
     * @return void
     */
    public function registerGatewayDownloadPurchase(string $id, $callback): void
    {
        add_action('ePayco_init_validation' . $id, $callback);
    }
}