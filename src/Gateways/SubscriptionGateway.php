<?php

namespace Epayco\Woocommerce\Gateways;

use Epayco\Woocommerce\Helpers\Form;
use Epayco\Woocommerce\Helpers\PaymentStatus;
use Epayco\Woocommerce\Transactions\SubscriptionTransaction;


if (!defined('ABSPATH')) {
    exit;
}

class SubscriptionGateway extends AbstractGateway
{
    /**
     * @const
     */
    public const ID = 'woo-epayco-subscription';

    /**
     * @const
     */
    public const CHECKOUT_NAME = 'checkout-subscription';

    /**
     * @const
     */
    public const WEBHOOK_API_NAME = 'WC_Epayco_Subscription_Gateway';

    /**
     * @const
     */
    public const WEBHOOK_DONWLOAD = 'Donwload';

    /**
     * @const
     */
    public const LOG_SOURCE = 'Epayco_SubscriptionGateway';

    /**
     * SubscriptionGateway constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->adminTranslations = $this->epayco->adminTranslations->subscriptionGatewaySettings;
        $this->storeTranslations = $this->epayco->storeTranslations->subscriptionCheckout;

        $this->id        = self::ID;
        $this->icon      = $this->epayco->hooks->gateway->getGatewayIcon('icon-blue-card.png');
        $this->iconAdmin = $this->epayco->hooks->gateway->getGatewayIcon('botonsuscripciones.png');
        $this->title     = $this->epayco->storeConfig->getGatewayTitle($this, $this->adminTranslations['gateway_title']);

        $this->init_form_fields();
        $this->payment_scripts($this->id);
        $this->supports = [
            'subscriptions',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_cancellation',
            'multiple_subscriptions'
        ];

        $this->description        = $this->adminTranslations['gateway_description'];
        $this->method_title       = $this->adminTranslations['gateway_method_title'];
        $this->method_description = $this->adminTranslations['gateway_method_description'];

        $this->epayco->hooks->gateway->registerUpdateOptions($this);
        $this->epayco->hooks->gateway->registerGatewayTitle($this);
        $this->epayco->hooks->gateway->registerThankYouPage($this->id, [$this, 'renderThankYouPage']);
        $this->epayco->hooks->endpoints->registerApiEndpoint(self::WEBHOOK_API_NAME, [$this, 'webhook']);
        $this->epayco->hooks->endpoints->registerApiEndpoint(self::WEBHOOK_DONWLOAD, [$this, 'validate_epayco_request']);

    }

    /**
     * Get checkout name
     *
     * @return string
     */
    public function getCheckoutName(): string
    {
        return self::CHECKOUT_NAME;
    }

    /**
     * Init form fields for checkout configuration
     *
     * @return void
     */
    public function init_form_fields(): void
    {
        if ($this->addMissingCredentialsNoticeAsFormField()) {
            return;
        }

        parent::init_form_fields();

        $this->form_fields = array_merge($this->form_fields, [
            'config_header' => [
                'type'        => 'mp_config_title',
                'title'       => $this->adminTranslations['header_title'],
                'description' => $this->adminTranslations['header_description'],
            ],
            'card_homolog_validate' => $this->getHomologValidateNoticeOrHidden(),
            'card_settings'  => [
                'type'  => 'mp_card_info',
                'value' => [
                    'title'       => $this->adminTranslations['card_settings_title'],
                    'subtitle'    => $this->adminTranslations['card_settings_subtitle'],
                    'button_text' => $this->adminTranslations['card_settings_button_text'],
                    'button_url'  => admin_url('admin.php?page=epayco-settings'),
                    'icon'        =>  $this->epayco->hooks->gateway->getGatewayIcon('icon-info.png'),
                    'color_card'  => '',
                    'size_card'   => 'ep-card-body-size',
                    'target'      => '_self',
                ],
            ],
            'enabled' => [
                'type'         => 'mp_toggle_switch',
                'title'        => $this->adminTranslations['enabled_title'],
                'subtitle'     => $this->adminTranslations['enabled_subtitle'],
                'default'      => 'no',
                'descriptions' => [
                    'enabled'  => $this->adminTranslations['enabled_descriptions_enabled'],
                    'disabled' => $this->adminTranslations['enabled_descriptions_disabled'],
                ],
            ],
            'title' => [
                'type'        => 'text',
                'title'       => $this->adminTranslations['title_title'],
                'description' => $this->adminTranslations['title_description'],
                'default'     => $this->adminTranslations['title_default'],
                'desc_tip'    => $this->adminTranslations['title_desc_tip'],
                'class'       => 'limit-title-max-length',
            ],
            'card_info_helper' => [
                'type'  => 'title',
                'value' => '',
            ]
        ]);
    }

    /**
     * Added gateway scripts
     *
     * @param string $gatewaySection
     *
     * @return void
     */
    public function payment_scripts(string $gatewaySection): void
    {
        parent::payment_scripts($gatewaySection);

        if ($this->canCheckoutLoadScriptsAndStyles()) {
            $this->registerCheckoutScripts();
        }
    }

    /**
     * Register checkout scripts
     *
     * @return void
     */
    public function registerCheckoutScripts(): void
    {
        parent::registerCheckoutScripts();
        $lang = get_locale();
        $lang = explode('_', $lang);
        $lang = $lang[0];
        $this->epayco->hooks->scripts->registerCheckoutScript(
            'wc_epayco_subscription_page',
            $this->epayco->helpers->url->getJsAsset('checkouts/subscription/ep-subscription-page')
        );

        $this->epayco->hooks->scripts->registerCheckoutScript(
            'wc_epayco_subscription_elements',
            $this->epayco->helpers->url->getJsAsset('checkouts/subscription/ep-subscription-elements')
        );

        $this->epayco->hooks->scripts->registerCheckoutScript(
            'wc_epayco_subscription_checkout',
            $this->epayco->helpers->url->getJsAsset('checkouts/subscription/ep-subscription-checkout'),
            [
                'site_id' => 'epayco',
                'public_key_epayco'        => $this->epayco->sellerConfig->getCredentialsPublicKeyPayment(),
                'lang' => $lang
            ]
        );
    }

    /**
     * Render gateway checkout template
     *
     * @return void
     */
    public function payment_fields(): void
    {
        $this->epayco->hooks->template->getWoocommerceTemplate(
            'public/checkout/subscription-checkout.php',
            $this->getPaymentFieldsParams()
        );
    }

    /**
     * Get Payment Fields params
     *
     * @return array
     */
    public function getPaymentFieldsParams(): array
    {
        $idioma = substr(get_locale(), 0, 2);
        if($idioma == 'es'){
            $termsAndCondiction = 'Términos y condiciones';
        }else{
            $termsAndCondiction = 'Terms and conditions';
        }
        if (strpos($this->storeTranslations['input_country_helper'], "Ciudad") !== false) {
            $city = "Ciudad";
        } else {
            $city = "City";
        }
        return [
            'test_mode'                        => $this->epayco->storeConfig->isTestMode(),
            'test_mode_title'                  => $this->storeTranslations['test_mode_title'],
            'test_mode_description'            => $this->storeTranslations['test_mode_description'],
            'test_mode_link_text'              => $this->storeTranslations['test_mode_link_text'],
            'card_detail'                      => $this->storeTranslations['card_detail'],
            //'test_mode_link_src'               => $this->links['docs_integration_test'],
            'card_form_title'                  => $this->storeTranslations['card_form_title'],
            'card_holder_name_input_label'     => $this->storeTranslations['card_holder_name_input_label'],
            'card_holder_name_input_helper'    => $this->storeTranslations['card_holder_name_input_helper'],
            'card_number_input_label'          => $this->storeTranslations['card_number_input_label'],
            'card_number_input_helper'         => $this->storeTranslations['card_number_input_helper'],
            'card_expiration_input_label'      => $this->storeTranslations['card_expiration_input_label'],
            'card_expiration_input_helper'     => $this->storeTranslations['card_expiration_input_helper'],
            'card_expiration_input_invalid_length' => $this->storeTranslations['input_helper_message_expiration_date_invalid_value'],
            'customer_data'                       => $this->storeTranslations['customer_data'],
            'card_security_code_input_label'   => $this->storeTranslations['card_security_code_input_label'],
            'card_security_code_input_helper'  => $this->storeTranslations['card_security_code_input_helper'],
            'card_security_code_input_invalid_length' => $this->storeTranslations['input_helper_message_security_code_invalid_length'],
            'card_customer_title'              => $this->storeTranslations['card_customer_title'],
            'card_document_input_label'        => $this->storeTranslations['card_document_input_label'],
            'card_document_input_helper'       => $this->storeTranslations['card_document_input_helper'],
            'card_holder_address_input_label'   => $this->storeTranslations['card_holder_address_input_label'],
            'card_holder_address_input_helper'  => $this->storeTranslations['card_holder_address_input_helper'],
            'card_holder_email_input_label'    => $this->storeTranslations['card_holder_email_input_label'],
            'card_holder_email_input_helper'   => $this->storeTranslations['card_holder_email_input_helper'],
            'card_holder_email_input_invalid'   => $this->storeTranslations['input_helper_message_card_holder_email'],
            'input_ind_phone_label'            => $this->storeTranslations['input_ind_phone_label'],
            'input_ind_phone_helper'           => $this->storeTranslations['input_ind_phone_helper'],
            'input_country_label'              => $this->storeTranslations['input_country_label'],
            'input_country_helper'             => $this->storeTranslations['input_country_helper'],
            'terms_and_conditions_label'       => $this->storeTranslations['terms_and_conditions_label'],
            'terms_and_conditions_description' => $this->storeTranslations['terms_and_conditions_description'],
            'terms_and_conditions_link_text'   => $this->storeTranslations['terms_and_conditions_link_text'],
            //'terms_and_conditions_link_text'   => $termsAndCondiction,
            'terms_and_conditions_link_src'    => 'https://epayco.com/terminos-y-condiciones-usuario-pagador-comprador/',
            'personal_data_processing_link_text'    => $this->storeTranslations['personal_data_processing_link_text'],
            'and_the'   => $this->storeTranslations['and_the'],
            'personal_data_processing_link_src'    => 'https://epayco.com/tratamiento-de-datos/',
            'site_id'                          => 'epayco',
            'city'                          => $city,
            'customer_title'              => $this->storeTranslations['customer_title'],
            'logo' =>       $this->epayco->hooks->gateway->getGatewayIcon('logo.png'),
            'icon_info' =>       $this->epayco->hooks->gateway->getGatewayIcon('icon-info.png'),
            'icon_warning' =>       $this->epayco->hooks->gateway->getGatewayIcon('warning.png'),
        ];
    }

    /**
     * Process payment and create woocommerce order
     *
     * @param $order_id
     *
     * @return array
     */
    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);
        try {
            $checkout = $this->getCheckoutEpaycoSubscription($order);

            parent::process_payment($order_id);

            $checkout['token'] = $checkout['cardTokenId'] ?? $checkout['cardtokenid'] ?? '';
            if (
                !empty($checkout['token'])
            ) {
                $this->transaction = new SubscriptionTransaction($this, $order, $checkout);
                $redirect_url =get_site_url() . "/";
                $redirect_url = add_query_arg( 'wc-api', self::WEBHOOK_API_NAME, $redirect_url );
                $redirect_url = add_query_arg( 'order_id', $order_id, $redirect_url );
                $confirm_url = $redirect_url.'&confirmation=1';
                $checkout['confirm_url'] = $confirm_url;
                $checkout['response_url'] = $order->get_checkout_order_received_url();
                $response = $this->transaction->createSubscriptionPayment($order_id, $checkout);
                $response = json_decode(json_encode($response), true);
                if (is_array($response) && $response['success']) {
                    $ref_payco = $response['ref_payco'][0];
                    if (in_array(strtolower($response['estado'][0]),["pendiente","pending"])) {
                        $this->epayco->orderMetadata->updatePaymentsOrderMetadata($order, [$ref_payco]);
                        $order->update_status("on-hold");
                        $this->epayco->woocommerce->cart->empty_cart();
                        $urlReceived = $order->get_checkout_order_received_url();
                        $return = [
                            'result'   => 'success',
                            'redirect' => $urlReceived,
                        ];
                    }
                    if (in_array(strtolower($response['estado'][0]),["aceptada","acepted","aprobada"])) {
                        $this->epayco->orderMetadata->updatePaymentsOrderMetadata($order, [$ref_payco]);
                        $order->update_status("processing");
                        $this->epayco->woocommerce->cart->empty_cart();
                        $urlReceived = $order->get_checkout_order_received_url();
                        $return = [
                            'result'   => 'success',
                            'redirect' => $urlReceived,
                        ];
                    }if (in_array(strtolower($response['estado'][0]),["rechazada","fallida","cancelada","abandonada"])) {
                        $urlReceived = wc_get_checkout_url();
                        $return = [
                            'result'   => 'fail',
                            'message' => $response['message'][0],
                            'redirect' => $urlReceived,
                        ];
                    }
                    return $return;
                }else{
                    $messageError = $response['message'];
                    $errorMessage = "";
                    if (isset($response['data']['errors'])) {
                        $errors = $response['data']['errors'];
                        foreach ($errors as $error) {
                            $errorMessage = $error['errorMessage'] . "\n";
                        }
                    } elseif (isset($response['data']['error']['errores'])) {
                        $errores = $response['data']['error']['errores'];
                        foreach ($errores as $error) {
                            $errorMessage = $error['errorMessage'] . "\n";
                        }
                    }
                    $processReturnFailMessage = $messageError. " " . $errorMessage;
                    return $this->returnFail($processReturnFailMessage, $order);
                }
            }

            throw new InvalidCheckoutDataException('exception : Unable to process payment on ' . __METHOD__);
        } catch (\Exception $e) {
            return $this->processReturnFail(
                $e,
                $e->getMessage(),
                self::LOG_SOURCE,
                (array) $order,
                true
            );
        }
    }

    /**
     * Get checkout epayco credits
     *
     * @param $order
     *
     * @return array
     */
    private function getCheckoutEpaycoSubscription($order): array
    {
        $checkout = [];

        if (isset($_POST['epayco_subscription'])) {
            $checkout = Form::sanitizedPostData('epayco_subscription');
            $this->epayco->orderMetadata->markPaymentAsBlocks($order, "no");
        } else {
            $checkout = $this->processBlocksCheckoutData('epayco_subscription', Form::sanitizedPostData());
            $this->epayco->orderMetadata->markPaymentAsBlocks($order, "yes");
        }

        return $checkout;
    }

    /**
     * Render thank you page
     *
     * @param $order_id
     */
    public function renderThankYouPage($order_id): void
    {
        $order        = wc_get_order($order_id);
        $lastPaymentId  =  $this->epayco->orderMetadata->getPaymentsIdMeta($order);
        $paymentInfo = json_decode(json_encode($lastPaymentId), true);

        if (empty($paymentInfo)) {
            return;
        }
        $data = array(
            "filter" => array("referencePayco" => $paymentInfo),
            "success" =>true
        );
        $this->transaction = new SubscriptionTransaction($this, $order, []);
        $transactionDetails = $this->transaction->sdk->transaction->get($paymentInfo);
        $transactionInfo = json_decode(json_encode($transactionDetails), true);

        if (empty($transactionInfo)) {
            return;
        }
        if(!$transactionInfo['success']){
            return;
        }
        $transaction = $this->transaction->returnParameterToThankyouPage($transactionInfo, $this);

        if (empty($transaction)) {
            return;
        }

        $this->epayco->hooks->template->getWoocommerceTemplate(
            'public/order/order-received.php',
            $transaction
        );
    }

}
