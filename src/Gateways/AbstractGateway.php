<?php

namespace Epayco\Woocommerce\Gateways;

use Epayco\Woocommerce\Helpers\Form;
use Epayco\Woocommerce\Interfaces\EpaycoGatewayInterface;
use Epayco\Woocommerce\WoocommerceEpayco;
use Exception;
use Epayco\Woocommerce\Exceptions\RejectedPaymentException;
use WC_Payment_Gateway;
use TCPDF;
use Epayco as EpaycoSdk;
abstract class AbstractGateway extends WC_Payment_Gateway implements EpaycoGatewayInterface
{
    public const ID = '';

    public const CHECKOUT_NAME = '';

    public const WEBHOOK_API_NAME = '';

    public const LOG_SOURCE = '';

    public array $adminTranslations;

    public WoocommerceEpayco $epayco;

    /**
     * Abstract Gateway constructor
     * @throws Exception
     */
    public function __construct()
    {
        global $epayco;

        $this->epayco = $epayco;

        $this->has_fields = true;
        $this->supports   = [ 'products', 'refunds' ];
        $this->init_settings();
    }

    /**
     * Init form fields for checkout configuration
     *
     * @return void
     */
    public function init_form_fields(): void
    {
        $this->form_fields = [];
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

        if ($this->canAdminLoadScriptsAndStyles($gatewaySection)) {
            $this->registerAdminScripts();
        }
        if ($this->canCheckoutLoadScriptsAndStyles()) {
            $this->registerCheckoutScripts();
        }
    }

    /**
     * Check if admin scripts and styles can be loaded
     *
     * @param string $gatewaySection
     *
     * @return bool
     */
    public function canAdminLoadScriptsAndStyles(string $gatewaySection): bool
    {
        return $this->epayco->hooks->admin->isAdmin() && ( $this->epayco->helpers->url->validatePage('wc-settings') &&
                $this->epayco->helpers->url->validateSection($gatewaySection)
            );
    }

    /**
     * Check if admin scripts and styles can be loaded
     *
     * @return bool
     */
    public function canCheckoutLoadScriptsAndStyles(): bool
    {
        return $this->epayco->hooks->gateway->isEnabled($this) &&
            ! $this->epayco->helpers->url->validateQueryVar('order-received');
    }

    /**
     * Register admin scripts
     *
     * @return void
     */
    public function registerAdminScripts()
    {
        $this->epayco->hooks->scripts->registerAdminScript(
            'wc_epayco_admin_components',
            $this->epayco->helpers->url->getJsAsset('admin/ep-admin-configs')
        );

        $this->epayco->hooks->scripts->registerAdminStyle(
            'wc_epayco_admin_components',
            $this->epayco->helpers->url->getCssAsset('admin/ep-admin-configs')
        );
    }

    /**
     * Register checkout scripts
     *
     * @return void
     */
    public function registerCheckoutScripts(): void
    {
        $this->epayco->hooks->scripts->registerCheckoutScript(
            'wc_epayco_checkout_crypto',
            $this->epayco->helpers->url->getJsAsset('checkouts/creditcard/crypto-v3.1.2')
        );

        $this->epayco->hooks->scripts->registerCheckoutScript(
            'wc_epayco_token_sdk',
            $this->epayco->helpers->url->getJsAsset('checkouts/creditcard/library')
            //"https://cms.epayco.io/js/library.js"
        );

        $this->epayco->hooks->scripts->registerCheckoutScript(
            'wc_epayco_checkout_components',
            $this->epayco->helpers->url->getJsAsset('checkouts/ep-plugins-components'),
            [
                'ep_json_url' => EP_PLUGIN_URL,
                'lang' => substr(get_locale(), 0, 2)
            ]
        );

        $this->epayco->hooks->scripts->registerCheckoutStyle(
            'wc_epayco_checkout_components',
            $this->epayco->helpers->url->getCssAsset('checkouts/ep-plugins-components')
        );

        $this->epayco->hooks->scripts->registerCheckoutScript(
            'wc_epayco_checkout_update',
            $this->epayco->helpers->url->getJsAsset('checkouts/ep-checkout-update')
        );
    }

    /**
     * Render gateway checkout template
     *
     * @return void
     */
    public function payment_fields(): void
    {
    }

    /**
     * Validate gateway checkout form fields
     *
     * @return bool
     */
    public function validate_fields(): bool
    {
        return true;
    }

    /**
     * Process payment and create woocommerce order
     *
     * @param $order_id
     *
     * @return array
     * @throws Exception
     */
    public function process_payment($order_id): array
    {
        return [];
    }

    /**
     * Receive gateway webhook notifications
     *
     * @return void
     */
    public function webhook(): void
    {
        global $woocommerce;
        $order_id_info = trim(sanitize_text_field($_GET['order_id']));
        $order_id_explode = explode('=',$order_id_info);
        $order_id_rpl  = str_replace('?ref_payco','',$order_id_explode);
        $order_id = $order_id_rpl[0];
        $order = new \WC_Order($order_id);
        $data = Form::sanitizedGetData();
        $params = $data;
        if(is_null($params['x_ref_payco'])){
            $params = $_POST;
        }
        //$params = $_POST;
        $x_signature = trim(sanitize_text_field($params['x_signature']));
        $x_cod_transaction_state =intval(trim(sanitize_text_field($params['x_cod_transaction_state'])));
        $x_ref_payco = trim(sanitize_text_field($params['x_ref_payco']));
        $x_transaction_id = trim(sanitize_text_field($params['x_transaction_id']));
        $x_amount = trim(sanitize_text_field($params['x_amount']));
        $x_currency_code = trim(sanitize_text_field($params['x_currency_code']));
        $x_test_request = trim(sanitize_text_field($params['x_test_request']));
        $x_approval_code = trim(sanitize_text_field($params['x_approval_code']));
        $x_franchise = trim(sanitize_text_field($params['x_franchise']));
        $x_fecha_transaccion = trim(sanitize_text_field($params['x_fecha_transaccion']));
        $x_id_invoice = trim(sanitize_text_field($params['x_id_invoice']));
        if ($order_id != "" && $x_ref_payco != "") {
            $authSignature = $this->authSignature($x_ref_payco, $x_transaction_id, $x_amount, $x_currency_code);
        }
        if($x_franchise == 'DP' || $x_franchise == 'DaviPlata'){
            $bodyRequest= [
                "filter"=>[
                    //"referencePayco"=>$paymentInfo
                    "referenceClient"=>$x_id_invoice
                ]
            ];
            $epaycoSdk = $this->getSdkInstance();
            $transactionDetails = $epaycoSdk->transaction->get($bodyRequest,true,"POST");
            $transactionInfo = json_decode(json_encode($transactionDetails), true);

            if (empty($transactionInfo)) {
                return;
            }

            if (is_array($transactionInfo)) {
                foreach ((array) $transactionInfo as $transaction) {
                    $daviplataTransactionData["data"] = $transaction;
                }
            }
            $transaciton = end($daviplataTransactionData["data"]);
            $x_ref_payco = $transaciton['referencePayco'];
            switch ($transaciton['status']) {
                case "Aceptada":
                    {
                        $x_cod_transaction_state = 1;
                    }
                    break;
                case "Rechazada":
                case "Fallida":
                case "abandonada":
                case "Cancelada":
                    {
                        $x_cod_transaction_state = 2;
                    }
                    break;
                case "Pendiente":
                case "retenido":
                    {
                        $x_cod_transaction_state = 3;
                    }
                    break;
                case "Reversada":
                    {
                        $x_cod_transaction_state = 6;
                    }
                    break;
                default:
                {
                    $x_cod_transaction_state = 0;
                }
            }
        }

        $isTestPluginMode = $this->epayco->storeConfig->isTestMode();
        $modo = $isTestPluginMode?'Prueba':'Producción';
        $current_state = $order->get_status();
        if(floatval($order->get_total()) == floatval($x_amount)){
            if($isTestPluginMode){
                $validation = true;
            }
            if(!$isTestPluginMode ){
                if($x_cod_transaction_state == 1){
                    $validation = true;
                }else{
                    if($x_cod_transaction_state != 1){
                        $validation = true;
                    }else{
                        $validation = false;
                    }
                }

            }
        }else{
            $validation = false;
        }

        if($authSignature == $x_signature){
            switch ($x_cod_transaction_state) {
                case 1: {
                    $message = 'Pago Proccesado ' .$x_ref_payco;
                    if($current_state !== "processing"){
                        if($current_state == "failed" ||
                            $current_state == "canceled"
                        ){
                            /*wc_reduce_stock_levels($order_id);
                            wc_increase_stock_levels($order_id);*/
                        }
                        $order->update_status("processing");
                    }

                }break;
                case 2:
                case 4:
                case 10:
                case 11:{
                    $message = 'Pago Cancelado ' .$x_ref_payco;
                    $order->update_status('cancelled');
                }break;
                case 3:
                case 7:{
                    $message = 'Pago Pendiente ' .$x_ref_payco;
                    $order->update_status("on-hold");
                }break;
                case 6: {
                    $message = 'Pago Reversada ' .$x_ref_payco;
                    $order->update_status('refunded');
                    $order->add_order_note('Pago Reversado');
                    $this->restore_order_stock($order->get_id());
                    echo "6";
                } break;
                default: {
                    $message = 'Pago fallido ' .$x_ref_payco;
                    $order->update_status('failed');
                    $order->add_order_note('Pago fallido o abandonado');
                }
            }
            update_post_meta( $order->get_id(), 'refPayco', esc_attr($x_ref_payco));
            update_post_meta( $order->get_id(), 'modo', esc_attr($modo));
            update_post_meta( $order->get_id(), 'fecha', esc_attr($x_fecha_transaccion));
            update_post_meta( $order->get_id(), 'franquicia', esc_attr($x_franchise));
            update_post_meta( $order->get_id(), 'autorizacion', esc_attr($x_approval_code));
        }else{
            $message = 'Firma no valida';
        }
        echo $message;
        die();
    }

    public function authSignature($x_ref_payco, $x_transaction_id, $x_amount, $x_currency_code){
        $pCustId = $this->epayco->sellerConfig->getCredentialsPCustId();
        $pKey = $this->epayco->sellerConfig->getCredentialsPkey();
        $signature = hash('sha256',
            trim($pCustId).'^'
            .trim($pKey).'^'
            .$x_ref_payco.'^'
            .$x_transaction_id.'^'
            .$x_amount.'^'
            .$x_currency_code
        );
        return $signature;
    }

    private function getSdkInstance()
    {

        $lang = get_locale();
        $lang = explode('_', $lang);
        $lang = $lang[0];
        $public_key = $this->epayco->sellerConfig->getCredentialsPublicKeyPayment();
        $private_key = $this->epayco->sellerConfig->getCredentialsPrivateKeyPayment();
        $isTestMode = $this->epayco->storeConfig->isTestMode()?"true":"false";
        return new EpaycoSdk\Epayco(
            [
                "apiKey" => $public_key,
                "privateKey" => $private_key,
                "lenguage" => strtoupper($lang),
                "test" => $isTestMode
            ]
        );
    }


    /**
    * Receive gateway webhook notifications
    *
    * @return void
    */
    public function validate_epayco_request(): void
    {
        if (isset($_GET['refPayco'])) {
            $refPayco = htmlspecialchars($_GET['refPayco']);
            $data = [
                'Estado' => htmlspecialchars($_GET['estado'] ?? ''),
                'Referencia' => $refPayco,
                'Fecha' => htmlspecialchars($_GET['fecha'] ?? ''),
                'Franquicia' => htmlspecialchars($_GET['franquicia'] ?? ''),
                'Autorización' => htmlspecialchars($_GET['autorizacion'] ?? ''),
                'Valor' => '$' . htmlspecialchars($_GET['valor'] ?? ''),
                'Descuento' => '$' . htmlspecialchars($_GET['descuento'] ?? ''),
                'Descripción' => htmlspecialchars($_GET['descripcion'] ?? ''),
                'IP' => htmlspecialchars($_GET['ip'] ?? ''),
                'Respuesta' => htmlspecialchars($_GET['respuesta'] ?? ''),
            ];
            $colores = [
                'aceptada' => [103, 201, 64],
                'rechazada' => [225, 37, 27],
                'pendiente' => [255, 209, 0],
            ];
            $color = $colores[strtolower($data['Estado'])] ?? [0, 0, 0];
            $titulo = 'Transacción ' . ucfirst(strtolower($data['Estado']));

            try {

                $pdf = new TCPDF();
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                $pdf->AddPage();



                $pdf->Image('https://multimedia.epayco.co/plugins-sdks/logo-negro-epayco.png', 80, 15, 50, '', '', '', 'T');
                $pdf->Ln(20);


                $pdf->SetFont('helvetica', 'B', 16);
                $pdf->SetTextColor($color[0], $color[1], $color[2]);
                $pdf->Cell(0, 10, $titulo, 0, 1, 'C');


                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('helvetica', '', 12);
                $pdf->Cell(0, 10, 'Referencia ePayco: ' . $refPayco, 0, 1, 'C');
                $pdf->Cell(0, 10, 'Fecha: ' . $data['Fecha'], 0, 1, 'C');
                $pdf->Ln(10);

                $pdf->SetFillColor(249, 249, 249);
                $pdf->SetDrawColor(229, 229, 229);

                foreach ($data as $key => $value) {
                    $pdf->Cell(50, 10, $key, 1, 0, 'L', true);
                    $pdf->Cell(0, 10, $value, 1, 1, 'L', false);
                }


                if (ob_get_length()) {
                    ob_end_clean();
                }


                if (headers_sent()) {
                    throw new Exception('Error: Los encabezados ya fueron enviados.');
                }


                $pdf->Output('Factura-' . $refPayco . '.pdf', 'D');
                exit;

            } catch (Exception $e) {
                error_log($e->getMessage());
                exit('Error al generar el PDF. Consulte el log del servidor.');
            }

        }
        return;
    }
    /**
     * Verify if the gateway is available
     *
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return true;
    }

    /**
     * If the seller is homologated, it returns an array of an empty $form_fields field.
     * If not, then return a notice to inform that the seller must be homologated to be able to sell.
     *
     * @return array
     */
    protected function getHomologValidateNoticeOrHidden(): array
    {
        if ($this->epayco->sellerConfig->getHomologValidate()) {
            return [
                'type'  => 'title',
                'value' => '',
            ];
        }

        return [
            'type'  => 'mp_card_info',
            'value' => [
                'title'       => $this->epayco->adminTranslations->credentialsSettings['card_homolog_title'],
                'subtitle'    => $this->epayco->adminTranslations->credentialsSettings['card_homolog_subtitle'],
                'button_text' => $this->epayco->adminTranslations->credentialsSettings['card_homolog_button_text'],
                'button_url'  => admin_url('admin.php?page=epayco-settings'),
                'icon'        => 'ep-icon-badge-warning',
                'color_card'  => '',
                'size_card'   => 'ep-card-body-payments-error',
                'target'      => '_blank',
            ]
        ];
    }


    /**
     * Add a "missing credentials" notice into the $form_fields array if there ir no credentials configured.
     * Returns true when the notice is added to the array, and false otherwise.
     *
     * @return bool
     */
    protected function addMissingCredentialsNoticeAsFormField(): bool
    {
        if (empty($this->epayco->sellerConfig->getCredentialsPublicKeyPayment()) || empty($this->epayco->sellerConfig->getCredentialsPrivateKeyPayment())) {
            $this->form_fields = [
                'card_info_validate' => [
                    'type'  => 'mp_card_info',
                    'value' => [
                        'title'       => '',
                        'subtitle'    => $this->epayco->adminTranslations->credentialsSettings['card_info_subtitle'],
                        'button_text' => $this->epayco->adminTranslations->credentialsSettings['card_info_button_text'],
                        'button_url'  => admin_url('admin.php?page=epayco-settings'),
                        'icon'        => 'ep-icon-badge-warning',
                        'color_card'  => 'ep-alert-color-error',
                        'size_card'   => 'ep-card-body-size',
                        'target'      => '_self',
                    ]
                ]
            ];

            return true;
        }

        return false;
    }

    /**
     * Process if result is fail
     *
     * @param Exception $e
     * @param string $message
     * @param string $source
     * @param array $context
     * @param bool $notice
     *
     * @return array
     */
    public function processReturnFail(Exception $e, string $message, string $source, array $context = [], bool $notice = false): array
    {

        $errorMessages = [
            "Invalid test user email" => $this->epayco->storeTranslations->commonMessages['invalid_users'],
            "Invalid users involved" => $this->epayco->storeTranslations->commonMessages['invalid_users'],
            "Invalid operators users involved" => $this->epayco->storeTranslations->commonMessages['invalid_operators'],
            "exception" => $this->epayco->storeTranslations->buyerRefusedMessages['buyer_default'],
            "400" => $this->epayco->storeTranslations->buyerRefusedMessages['buyer_default'],
        ];

        foreach ($errorMessages as $keyword => $replacement) {
            if (strpos($message, $keyword) !== false) {
                $message = $replacement;
                break;
            }
        }

        if ($notice) {
            $this->epayco->helpers->notices->storeNotice($message, 'error');
        }

        return [
            'result'   => 'fail',
            'redirect' => '',
            'message'  => $message,
        ];
    }

    /**
     * Handle With Rejectec Payment Status
     *
     * @param $response
     *
     */
    public function handleWithRejectPayment($response)
    {
        if ($response['status'] === 'rejected') {
            $statusDetail = $response['status_detail'];

            $errorMessage = $this->getRejectedPaymentErrorMessage($statusDetail);

            throw new RejectedPaymentException($errorMessage);
        }
    }

    /**
     * Get payment rejected error message
     *
     * @param string $statusDetail statusDetail.
     *
     * @return string
     */
    public function getRejectedPaymentErrorMessage($statusDetail)
    {
        return $this->epayco->storeTranslations->buyerRefusedMessages['buyer_' . $statusDetail] ??
            $this->epayco->storeTranslations->buyerRefusedMessages['buyer_default'];
    }

    /**
     * Process if result is fail
     *
     * @param string $message
     * @param mixed $context
     *
     * @return array
     */
    public function returnFail(string $message, $context): array
    {
        wc_add_notice($message, 'error');
        if (version_compare(WOOCOMMERCE_VERSION, '2.1', '>=')) {
            $redirect = array(
                'result'   => 'fail',
                'message'  => $message,
                'redirect' => add_query_arg('order-pay', $context->get_id(), add_query_arg('key', $context->order_key, get_permalink(woocommerce_get_page_id('pay'))))
            );
        } else {
            $redirect = array(
                'result'   => 'fail',
                'message'  => $message,
                'redirect' => add_query_arg('order', $context->get_id(), add_query_arg('key', $context->order_key, get_permalink(woocommerce_get_page_id('pay'))))
            );
        }

        return $redirect;
    }

    /**
     * Process blocks checkout data
     *
     * @param $prefix
     * @param $postData
     *
     * @return array
     */
    public function processBlocksCheckoutData($prefix, $postData): array
    {
        $checkoutData = [];

        foreach ($postData as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $newKey                  = substr($key, strlen($prefix));
                $checkoutData[ $newKey ] = $value;
            }
        }

        return $checkoutData;
    }

    /**
     * Generate custom toggle switch component
     *
     * @param string $key
     * @param array $settings
     *
     * @return string
     */
    public function generate_mp_toggle_switch_html(string $key, array $settings): string
    {
        return $this->epayco->hooks->template->getWoocommerceTemplateHtml(
            'admin/components/toggle-switch.php',
            [
                'field_key'   => $this->get_field_key($key),
                'field_value' => $this->epayco->hooks->options->getGatewayOption($this, $key, $settings['default']),
                'settings'    => $settings,
            ]
        );
    }

    /**
     * Generate custom toggle switch component
     *
     * @param string $key
     * @param array $settings
     *
     * @return string
     */
    public function generate_mp_checkbox_list_html(string $key, array $settings): string
    {

        return $this->epayco->hooks->template->getWoocommerceTemplateHtml(
            'admin/components/checkbox-list.php',
            [
                'field_key' => $this->get_field_key($key),
                'settings'  => $settings,
            ]
        );
    }

    /**
     * Generate custom header component
     *
     * @param string $key
     * @param array $settings
     *
     * @return string
     */
    public function generate_mp_config_title_html(string $key, array $settings): string
    {
        return $this->epayco->hooks->template->getWoocommerceTemplateHtml(
            'admin/components/config-title.php',
            [
                'field_key'   => $this->get_field_key($key),
                'field_value' => null,
                'settings'    => $settings,
            ]
        );
    }

    /**
     * Generating custom actionable input component
     *
     * @param string $key
     * @param array $settings
     *
     * @return string
     */
    public function generate_mp_actionable_input_html(string $key, array $settings): string
    {
        return $this->epayco->hooks->template->getWoocommerceTemplateHtml(
            'admin/components/actionable-input.php',
            [
                'field_key'          => $this->get_field_key($key),
                'field_key_checkbox' => $this->get_field_key($key . '_checkbox'),
                'field_value'        => $this->epayco->hooks->options->getGatewayOption($this, $key),
                'enabled'            => $this->epayco->hooks->options->getGatewayOption($this, $key . '_checkbox'),
                'custom_attributes'  => $this->get_custom_attribute_html($settings),
                'settings'           => $settings,
                'allowedHtmlTags'    => $this->epayco->helpers->strings->getAllowedHtmlTags(),
            ]
        );
    }

    /**
     * Generating custom card info component
     *
     * @param string $key
     * @param array $settings
     *
     * @return string
     */
    public function generate_mp_card_info_html(string $key, array $settings): string
    {
        return $this->epayco->hooks->template->getWoocommerceTemplateHtml(
            'admin/components/card-info.php',
            [
                'field_key'   => $this->get_field_key($key),
                'field_value' => null,
                'settings'    => $settings,
            ]
        );
    }

    /**
     * Generating custom preview component
     *
     * @param string $key
     * @param array $settings
     *
     * @return string
     */
    public function generate_mp_preview_html(string $key, array $settings): string
    {
        return $this->epayco->hooks->template->getWoocommerceTemplateHtml(
            'admin/components/preview.php',
            [
                'field_key'   => $this->get_field_key($key),
                'field_value' => null,
                'settings'    => $settings,
            ]
        );
    }

    /**
     * Generating support link component
     *
     * @param string $key
     * @param array $settings
     *
     * @return string
     */
    public function generate_mp_support_link_html(string $key, array $settings): string
    {
        return $this->epayco->hooks->template->getWoocommerceTemplateHtml(
            'admin/components/support-link.php',
            [
                'field_key'   => $this->get_field_key($key),
                'field_value' => null,
                'settings'    => $settings,
            ]
        );
    }

    /**
     * Generating tooltip selection component
     *
     * @param string $key
     * @param array $settings
     *
     * @return string
     */
    public function generate_mp_tooltip_selection_html(string $key, array $settings): string
    {
        return $this->epayco->hooks->template->getWoocommerceTemplateHtml(
            'admin/components/tooltip-selection.php',
            [
                'field_key'   => $this->get_field_key($key),
                'field_value' => null,
                'settings'    => $settings,
            ]
        );
    }

    /**
     * Generating credits checkout example component
     *
     * @param string $key
     * @param array $settings
     *
     * @return string
     */
    public function generate_mp_credits_checkout_example_html(string $key, array $settings): string
    {
        return $this->epayco->hooks->template->getWoocommerceTemplateHtml(
            'admin/components/credits-checkout-example.php',
            [
                'field_key'   => $this->get_field_key($key),
                'field_value' => null,
                'settings'    => $settings,
            ]
        );
    }



}