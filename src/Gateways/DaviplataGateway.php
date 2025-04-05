<?php

namespace Epayco\Woocommerce\Gateways;

use Exception;
use Epayco\Woocommerce\Exceptions\InvalidCheckoutDataException;
use Epayco\Woocommerce\Helpers\Form;
use Epayco\Woocommerce\Transactions\DaviplataTransaction;
use WP_User;

if (!defined('ABSPATH')) {
    exit;
}

class DaviplataGateway extends AbstractGateway
{
    /**
     * ID
     *
     * @const
     */
    public const ID = 'woo-epayco-daviplata';

    /**
     * @const
     */
    public const CHECKOUT_NAME = 'checkout-daviplata';

    /**
     * @const
     */
    public const WEBHOOK_API_NAME = 'WC_WooEpayco_Daviplata_Gateway';

    /**
     * @const
     */
    public const WEBHOOK_DONWLOAD = 'Donwload';

    /**
     * @const
     */
    public const LOG_SOURCE = 'Epayco_DaviplataGateway';

    const CASH_ENTITIES = [
        [
            "id" =>"EF",
            "name" =>"efecty"
        ],
        [
            "id" =>"GA",
            "name" =>"gana"
        ],
        [
            "id" =>"PR",
            "name" =>"puntored"
        ],
        [
            "id" =>"RS",
            "name" =>"redservi"
        ],
        [
            "id" =>"SR",
            "name" =>"sured"
        ]
    ];

    /**
     * DaviplataGateway constructor
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->adminTranslations = $this->epayco->adminTranslations->daviplataGatewaySettings;
        $this->storeTranslations = $this->epayco->storeTranslations->daviplataCheckout;

        $this->id        = self::ID;
        //$this->icon      = $this->getCheckoutIcon();
        //$this->iconAdmin = $this->getCheckoutIcon(true);
        $this->icon      = $this->epayco->hooks->gateway->getGatewayIcon('icon-daviplata.png');
        $this->iconAdmin = $this->epayco->hooks->gateway->getGatewayIcon('DPA50.png');
        $this->title     = $this->epayco->storeConfig->getGatewayTitle($this, 'Daviplata');

        $this->init_form_fields();
        $this->payment_scripts($this->id);

        $this->description        = $this->adminTranslations['gateway_description'];
        $this->method_title       = $this->adminTranslations['method_title'];
        $this->method_description = $this->description;

        $this->epayco->hooks->gateway->registerUpdateOptions($this);
        $this->epayco->hooks->gateway->registerGatewayTitle($this);
        $this->epayco->hooks->gateway->registerThankYouPage($this->id, [$this, 'renderThankYouPage']);
        $this->epayco->hooks->endpoints->registerApiEndpoint(self::WEBHOOK_DONWLOAD, [$this, 'validate_epayco_request']);
        $this->epayco->hooks->endpoints->registerApiEndpoint(self::WEBHOOK_API_NAME, [$this, 'webhook']);


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
                    'enabled'  => $this->adminTranslations['enabled_enabled'],
                    'disabled' => $this->adminTranslations['enabled_disabled'],
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

        $this->epayco->hooks->scripts->registerCheckoutScript(
            'wc_epayco_daviplata_page',
            $this->epayco->helpers->url->getJsAsset('checkouts/daviplata/ep-daviplata-page')
        );

        $this->epayco->hooks->scripts->registerCheckoutScript(
            'wc_epayco_daviplata_elements',
            $this->epayco->helpers->url->getJsAsset('checkouts/daviplata/ep-daviplata-elements')
        );

        $this->epayco->hooks->scripts->registerCheckoutScript(
            'wc_epayco_daviplata_checkout',
            $this->epayco->helpers->url->getJsAsset('checkouts/daviplata/ep-daviplata-checkout'),
            [
                'site_id' => '',
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
            'public/checkout/daviplata-checkout.php',
            $this->getPaymentFieldsParams()
        );
    }

    /**
     * Get Payment Fields params
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    public function getPaymentFieldsParams(): array
    {
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
            //'test_mode_link_src'               => $this->links['docs_integration_test'],
            'input_name_label'                 => $this->storeTranslations['input_name_label'],
            'input_name_helper'                => $this->storeTranslations['input_name_helper'],
            'input_email_label'                => $this->storeTranslations['input_email_label'],
            'input_email_helper'               => $this->storeTranslations['input_email_helper'],
            'input_address_label'              => $this->storeTranslations['input_address_label'],
            'input_address_helper'             => $this->storeTranslations['input_address_helper'],
            'input_ind_phone_label'            => $this->storeTranslations['input_ind_phone_label'],
            'input_ind_phone_helper'           => $this->storeTranslations['input_ind_phone_helper'],
            'person_type_label'                => $this->storeTranslations['person_type_label'],
            'input_document_label'             => $this->storeTranslations['input_document_label'],
            'input_document_helper'            => $this->storeTranslations['input_document_helper'],
            'input_country_label'              => $this->storeTranslations['input_country_label'],
            'input_country_helper'             => $this->storeTranslations['input_country_helper'],
            'daviplata_text_label'                => $this->storeTranslations['daviplata_text_label'],
            'input_table_button'               => $this->storeTranslations['input_table_button'],
            'payment_methods'                  => [],
            'input_helper_label'               => $this->storeTranslations['input_helper_label'],
            'terms_and_conditions_label'       => $this->storeTranslations['terms_and_conditions_label'],
            'terms_and_conditions_description' => $this->storeTranslations['terms_and_conditions_description'],
            'terms_and_conditions_link_text'   => $this->storeTranslations['terms_and_conditions_link_text'],
            'terms_and_conditions_link_src'    => 'https://epayco.com/terminos-y-condiciones-usuario-pagador-comprador/',
            'personal_data_processing_link_text'    => $this->storeTranslations['personal_data_processing_link_text'],
            'and_the'   => $this->storeTranslations['and_the'],
            'personal_data_processing_link_src'    => 'https://epayco.com/tratamiento-de-datos/',
            'site_id'                          => '',
            'city'                          => $city,
            'customer_title'              => $this->storeTranslations['customer_title'],
            'logo' =>       $this->epayco->hooks->gateway->getGatewayIcon('logo.png'),
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
            $checkout = $this->getCheckoutEpaycoDaviplata($order);

            parent::process_payment($order_id);

            if (
                !empty($checkout['cellphonetype'])
            ) {
                $redirect_url =get_site_url() . "/";
                $redirect_url = add_query_arg( 'wc-api', self::WEBHOOK_API_NAME, $redirect_url );
                $redirect_url = add_query_arg( 'order_id', $order_id, $redirect_url );
                $confirm_url = $redirect_url.'&confirmation=1';
                $checkout['confirm_url'] = $confirm_url;
                $checkout['response_url'] = $order->get_checkout_order_received_url();
                $payment_method_id= $checkout["payment_method_id"]??$checkout[""]["payment_method_id"];
                $key = array_search( $payment_method_id, array_column(self::CASH_ENTITIES, 'name'));
                $checkout['paymentMethod'] = self::CASH_ENTITIES[$key]['id'];
                $this->transaction = new DaviplataTransaction($this, $order, $checkout);
                $response          = $this->transaction->createDaviplataPayment($order, $checkout);

                if (is_array($response) && $response['success']) {
                    $ref_payco = $response['data']['refPayco']??$response['data']['ref_payco'];
                    if (isset($ref_payco)) {
                        $this->epayco->orderMetadata->updatePaymentsOrderMetadata($order,[$ref_payco]);
                        $response['urlPayment'] = 'https://vtex.epayco.io/daviplata?refPayco='.$ref_payco;
                        $this->epayco->hooks->order->setDaviplataMetadata($order, $response);
                        $description = sprintf(
                            "ePayco: %s <a target='_blank' href='%s'>%s</a>",
                            $this->storeTranslations['congrats_title'],
                            $response['urlPayment'],
                            $this->storeTranslations['congrats_subtitle']
                        );
                        $this->epayco->hooks->order->addOrderNote($order, $description, 1);
                    }
                    $this->epayco->orderMetadata->updatePaymentsOrderMetadata($order,[$response['data']['refPayco']]);


                    if (in_array(strtolower($response['data']['estatus']),["pendiente","pending"])) {
                        $order->update_status("on-hold");
                        $this->epayco->woocommerce->cart->empty_cart();
                        //$urlReceived = $order->get_checkout_order_received_url();
                        $return = [
                            'result'   => 'success',
                            'redirect' =>  $response['urlPayment'],
                        ];
                        return $return;
                    }
                }else{
                    $messageError = $response['message']?? $response['titleResponse'];
                    $errorMessage = "";
                    if (isset($response['data']['errors'])) {
                        $errors = $response['data']['errors'];
                        foreach ($errors as $error) {
                            $errorMessage = $error['errorMessage'] . "\n";
                        }
                    } elseif (isset($response['data']['error'])) {
                        $errores = $response['data']['error'];
                        foreach ($errores as $error) {
                            $errorMessage = $error['errorMessage'] . "\n";
                        }
                    }elseif (isset($response['data']['errores'])) {
                        $errores = $response['data']['errores'];
                        foreach ($errores as $error) {
                            $errorMessage = $error['errorMessage'] . "\n";
                        }
                    }elseif (isset($response['data']['error']['errores'])) {
                        $errores = $response['data']['error']['errores'];
                        foreach ($errores as $error) {
                            $errorMessage = $error['errorMessage'] . "\n";
                        }
                    }
                    $processReturnFailMessage = $messageError. " " . $errorMessage;
                    return $this->returnFail($processReturnFailMessage, $order);
                }

            }else{
                throw new InvalidCheckoutDataException('exception : Unable to process payment on ' . __METHOD__);
            }
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
     * Get checkout epayco daviplata
     *
     * @param $order
     *
     * @return array
     */
    private function getCheckoutEpaycoDaviplata($order): array
    {
        $checkout = [];

        if (isset($_POST['epayco_daviplata'])) {
            $checkout = Form::sanitizedPostData('epayco_daviplata');
            $this->epayco->orderMetadata->markPaymentAsBlocks($order, "no");
        } else {
            $checkout = $this->processBlocksCheckoutData('epayco_daviplata', Form::sanitizedPostData());
            $this->epayco->orderMetadata->markPaymentAsBlocks($order, "yes");
        }

        return $checkout;
    }




    /**
     * Get Epayco Icon
     *
     * @param bool $adminVersion
     *
     * @return string
     */
    private function getCheckoutIcon(bool $adminVersion = false): string
    {
        $iconName = 'icon-daviplata.png';
        return $this->epayco->hooks->gateway->getGatewayIcon($iconName . ($adminVersion ? '-admin' : ''));
    }



    /**
     * Render thank you page
     *
     * @param $order_id
     */
    public function renderThankYouPage($order_id): void
    {
        $order        = wc_get_order($order_id);
        $transactionDetails  =  $this->epayco->orderMetadata->getDaviplataTransactionDetailsMeta($order);
        $lastPaymentId  =  $this->epayco->orderMetadata->getPaymentsIdMeta($order);
        $daviplata_data = json_decode($transactionDetails, true);
        $paymentInfo = json_decode(json_encode($lastPaymentId), true);
        if (empty($paymentInfo) && empty($daviplata_data)) {
            return;
        }
        $referenceClient = $daviplata_data['data']['invoice'];
        $this->transaction = new  DaviplataTransaction($this, $order, []);
        $bodyRequest= [
            "filter"=>[
                //"referencePayco"=>$paymentInfo
                "referenceClient"=>$referenceClient
            ]
        ];
        //$transactionDetails = $this->transaction->sdk->transaction->get($paymentInfo);
        $transactionDetails = $this->transaction->sdk->transaction->get($bodyRequest,true,"POST");
        $transactionInfo = json_decode(json_encode($transactionDetails), true);

        if (empty($transactionInfo)) {
            return;
        }

        if (is_array($transactionInfo)) {
            foreach ((array) $transactionInfo as $transaction) {
                $daviplataTransactionData["data"] = $transaction;
            }
        }
        $daviplataTransaction = [
            "success" => true,
            "data" => end($daviplataTransactionData["data"])
        ];
        $transaction = $this->transaction->returnParameterToThankyouPage($daviplataTransaction, $this);

        if (empty($transaction)) {
            return;
        }

        $this->epayco->hooks->template->getWoocommerceTemplate(
            'public/order/order-received.php',
            $transaction
        );

        /*$this->epayco->hooks->template->getWoocommerceTemplate(
            'public/order/epayco-order-received.php',
            [
                'print_daviplata_label'  => '',
                'print_daviplata_link'  => '',
                'transaction_details' => $transactionDetails,
            ]
        );*/
    }
}
