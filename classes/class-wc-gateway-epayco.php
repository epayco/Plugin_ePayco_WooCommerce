<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
require_once EPAYCO_PLUGIN_CLASS_PATH . 'class-wc-transaction-epayco.php';

class WC_Gateway_Epayco extends WC_Payment_Gateway
{

    public static $logger;

    /**
     * Settings
     */
    public static $_settings = array();
    public const PAYMENTS_IDS = 'epayco_meta_data';
    /**
     * Constructor for the gateway.
     *
     * @access public
     * @return void
     */
    public function __construct()
    {

        $this->id = 'epayco';
        //$this->version = '8.2.2';
        $this->icon = apply_filters('woocommerce_' . $this->id . '_icon', EPAYCO_PLUGIN_URL . 'assets/images/paymentLogo.svg' );
        $this->method_title         = __('ePayco Checkout Gateway', 'woo-epayco-gateway');
        $this->method_description   = __('Acepta tarjetas de credito, depositos y transferencias.', 'woo-epayco-gateway');
        //$this->order_button_text = __('Pay', 'epayco_woocommerce');
        $this->has_fields           = false;
        $this->supports         = array(
            'products',
            'refunds',
        );
        // Load the settings
        $this->init_form_fields();
        $this->init_settings();
        // Define user set variables
        self::$_settings = get_option('woocommerce_epayco_settings');
        $this->title            = $this->get_option('title');
        //$this->max_monto = $this->get_option('monto_maximo');
        $this->description      = $this->get_option('description');
        // Actions
        add_action('valid-' . $this->id . '-standard-ipn-request', array($this, 'successful_request'));
        add_action('ePayco_init_validation', array($this, 'ePayco_successful_validation'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Payment listener/API hook
        add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'check_ipn_response'));
        add_action('woocommerce_api_' . strtolower(get_class($this) . "Validation"), array($this, 'validate_ePayco_request'));

        add_action('woocommerce_checkout_create_order' . $this->id, array($this, 'add_expiration'));

        //Cron
        add_action('woocommerce_epayco_cleanup_draft_orders', [$this, 'delete_epayco_expired_draft_orders']);
        add_action('woocommerc_epayco_cron_hook', [$this, 'woocommerc_epayco_cron_job_funcion']);

        add_action('admin_init', [$this, 'install']);

        if (! $this->is_valid_for_use()) {
            $this->enabled = false;
        }

        if (empty(self::$logger)) {
            if (version_compare(WC_VERSION, '3.0', '<')) {
                self::$logger = new WC_Logger();
            } else {
                self::$logger = wc_get_logger();
            }
        }
    }





    /**
     * Installation related logic for Draft order functionality.
     *
     * @internal
     */
    public function install()
    {
        $this->maybe_create_cronjobs();
    }

    /**
     * Maybe create cron events.
     */
    protected function maybe_create_cronjobs()
    {
        $cron_data = $this->settings['cron_data'] == "yes" ? true : false;
        if ($cron_data) {
            if (function_exists('as_next_scheduled_action') && false === as_next_scheduled_action('woocommerce_epayco_cleanup_draft_orders')) {
                as_schedule_recurring_action(time() + 3600, 3600, 'woocommerce_epayco_cleanup_draft_orders');
            }
        }
    }


    public function woocommerc_epayco_cron_job_funcion()
    {
        if(isset($this->settings['cron_data'])){
            $cron_data = $this->settings['cron_data'] == "yes" ? true : false;
            if ($cron_data) {
                $this->getEpaycoORders();
                $this->getWoocommercePendigsORders();
            }
        }
    }

    /**
     * Delete draft orders older than a day in batches of 20.
     *
     * Ran on a daily cron schedule.
     *
     * @internal
     */
    public function delete_epayco_expired_draft_orders()
    {
        $this->getEpaycoORders();
        $this->getWoocommercePendigsORders();
    }



    function is_valid_for_use()
    {
        if (! in_array(get_woocommerce_currency(), array('COP', 'USD'), true)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Admin Panel Options
     *
     * @since 6.0.0
     */
    public function admin_options()
    {
        $validation_url = get_site_url() . "/";
        $validation_url = add_query_arg('wc-api', get_class($this) . "Validation", $validation_url);
        $logo_url = get_site_url() . "/";
        $logo_url = add_query_arg('wc-api', get_class($this) . "ChangeLogo", $logo_url);
        ?>


        <div class="container-fluid">
        <div class="panel panel-default" style="">
            <img src="<?php echo EPAYCO_PLUGIN_URL . '/assets/images/logoepayco.svg' ?>" >
            <div id="path_upload" hidden>
                <?php esc_html_e($logo_url, 'text_domain'); ?>
            </div>
            <div id="path_images" hidden>
                <?php echo EPAYCO_PLUGIN_URL . 'assets/images' ?>
            </div>
            <div id="path_validate" hidden>
                <?php esc_html_e($validation_url, 'text_domain'); ?>
            </div>
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i><?php esc_html_e('Configuración Epayco', 'woo-epayco-gateway'); ?></h3>
            </div>ePayco
        </div>
        <div style="color: #31708f; background-color: #d9edf7; border-color: #bce8f1;padding: 10px;border-radius: 5px;">
            <?php esc_html_e('Este módulo le permite aceptar pagos seguros por la plataforma de pagos ePayco.Si el cliente decide pagar por ePayco, el estado del pedido cambiará a ', 'woo-epayco-gateway'); ?><b>
                <?php esc_html_e(' Esperando Pago', 'woo-epayco-gateway'); ?></b>.
            <br><?php esc_html_e('Cuando el pago sea Aceptado o Rechazado ePayco envía una confirmación a la tienda para cambiar el estado del pedido.', 'woo-epayco-gateway'); ?>
        </div>

        <?php if ($this->is_valid_for_use()) : ?>
        <table class="form-table epayco-table">
            <?php
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="woocommerce_epayco_enabled"><?php esc_html_e('Validar llaves', 'woo-epayco-gateway'); ?></label>
                    <span hidden id="public_key">0</span>
                    <span hidden id="private_key">0</span>
                <td class="forminp">
                    <form method="post" action="#">
                        <label for="woocommerce_epayco_enabled">
                        </label>
                        <input type="button" class="button-primary woocommerce-save-button validar" value="Validar">
                        <p class="description">
                            <?php esc_html_e('Validación de llaves PUBLIC_KEY y PRIVATE_KEY', 'woo-epayco-gateway'); ?>
                        </p>
                    </form>
                    <br>
                    <!-- The Modal -->
                    <div id="myModal" class="modal">
                        <!-- Modal content -->
                        <div class="modal-content">
                            <span class="close">&times;</span>
                            <center>
                                <img src="<?php echo EPAYCO_PLUGIN_URL . '/assets/images/logo_warning.png' ?>">
                            </center>
                            <p><strong><?php esc_html_e('Llaves de comercio inválidas', 'woo-epayco-gateway'); ?></strong> </p>
                            <p><?php esc_html_e('Las llaves Public Key, Private Key insertadas', 'woo-epayco-gateway'); ?><br><?php esc_html_e('del comercio son inválidas.', 'woo-epayco-gateway'); ?><br><?php esc_html_e('Consúltelas en el apartado de integraciones', 'woo-epayco-gateway'); ?> <br><?php esc_html_e('Llaves API en su Dashboard ePayco.', 'woo-epayco-gateway'); ?>,</p>
                        </div>
                    </div>

                </td>
                </th>
            </tr>
        </table><!--/.form-table-->
    <?php
    else :
        $currencies          = array('USD', 'COP');
        $formated_currencies = '';

        foreach ($currencies as $currency) {
            $formated_currencies .= $currency . ', ';
        }
        ?>

        </div>






        <div class="inline error">
            <p><strong><?php esc_html_e('Gateway Disabled', 'woo-epayco-gateway');
                    ?>
                </strong>:
                <?php
                esc_html_e('Servired/Epayco only support ', 'woo-epayco-gateway');
                echo esc_html($formated_currencies);
                ?>
            </p>
        </div>
    <?php
    endif;
    }

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields()
    {
        $this->form_fields = include dirname(__FILE__) . '/epayco-settings.php';
        $epayco_langs   = array(
            '1'      => 'Español',
            '2'      => 'English - Inglés'
        );

        foreach ($epayco_langs as $epayco_lang => $valor) {
            $this->form_fields['epayco_lang']['options'][$epayco_lang] = $valor;
        }
    }

    function get_pages($title = false, $indent = true)
    {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) $page_list[] = $title;
        foreach ($wp_pages as $page) {
            $prefix = '';
            // show indented child pages?
            if ($indent) {
                $has_parent = $page->post_parent;
                while ($has_parent) {
                    $prefix .=  ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            // add to page list array array
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
    }

    /**
     * Generate the epayco form
     *
     * @param mixed $order_id
     * @return string
     */
    function generate_epayco_form($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);
        $descripcionParts = array();

        $iva = 0;
        $ico = 0;

        foreach ($order->get_items('tax') as $item_id => $item) {
            $tax_label = trim(strtolower($item->get_label()));
            $tax_name = trim(strtolower($order->get_items_tax_classes()[0]));
            if ($tax_label == 'iva' || $tax_name == 'iva' ) {
                $iva = round($order->get_total_tax(), 2);
            }

            if ($tax_label == 'ico'|| $tax_name == 'ico') {
                $ico = round($order->get_total_tax(), 2);
            }
        }

        //$iva = $iva !== 0 ? $iva :$order->get_total_tax();

        $base_tax = ($iva !== 0) ? ($order->get_total() - $order->get_total_tax()): (($ico !== 0) ? ($order->get_total() - $order->get_total_tax()): $order->get_subtotal() );

        foreach ($order->get_items() as $product) {
            $clearData = str_replace('_', ' ', $this->string_sanitize($product['name']));
            $descripcionParts[] = $clearData;
        }

        $descripcion = implode(' - ', $descripcionParts);
        $currency = strtolower(get_woocommerce_currency());
        $testMode = $this->settings['epayco_testmode'] == "yes" ? "true" : "false";
        $basedCountry = WC()->countries->get_base_country();
        $external = $this->settings['epayco_type_checkout'];
        $redirect_url = get_site_url() . "/";
        $redirect_url = add_query_arg('wc-api', get_class($this), $redirect_url);
        $redirect_url = add_query_arg('order_id', $order_id, $redirect_url);
        $myIp = $this->getCustomerIp();
        $lang = $this->settings['epayco_lang'] == 1 ? "es" : "en";
        if ($this->get_option('epayco_url_confirmation') == 0) {
            $confirm_url = $redirect_url . '&confirmation=1';
        } else {
            $confirm_url = get_permalink($this->get_option('epayco_url_confirmation'));
        }

        $name_billing = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $address_billing = $order->get_billing_address_1();
        $phone_billing = @$order->billing_phone;
        $email_billing = @$order->billing_email;

        //Busca si ya se restauro el stock
        if (!EpaycoOrder::ifExist($order_id)) {
            //si no se restauro el stock restaurarlo inmediatamente
            EpaycoOrder::create($order_id, 1);
            //$this->restore_order_stock($order->get_id(),"decrease");
        }
        $orderStatus = "pending";
        $current_state = $order->get_status();
        if ($current_state != $orderStatus) {
            $order->update_status($orderStatus);
            //$this->restore_order_stock($order->get_id(),"decrease");
        }
        echo sprintf(
            '
                    <script
                       src="https://checkout.epayco.co/checkout.js">
                    </script>
                    <script> var handler = ePayco.checkout.configure({
                        key: "%s",
                        test: %s
                    })
                    var date = new Date().getTime();
                    var bntPagar = document.getElementById("btn_epayco");
                    var data = {
                        name: "%s",
                        description: "%s",
                        invoice: "%s",
                        currency: "%s",
                        amount: "%s".toString(),
                        tax_base: "%s".toString(),
                        tax: "%s".toString(),
                        taxIco: "%s".toString(),
                        country: "%s",
                        lang: "%s",
                        external: "%s",
                        confirmation: "%s",
                        response: "%s",
                        name_billing: "%s",
                        address_billing: "%s",
                        email_billing: "%s",
                        mobilephone_billing: "%s",
                        autoclick: "true",
                        ip: "%s",
                        test: "%s".toString(),
                        extra1: "%s",
                        extras_epayco:{extra5:"p19"},
                        method_confirmation: "POST",
                        checkout_version:"1"
                    }
                    const apiKey = "%s";
                    const privateKey = "%s";
                    var openNewChekout = function () {
                        if(localStorage.getItem("invoicePayment") == null){
                            localStorage.setItem("invoicePayment", data.invoice);
                            makePayment(privateKey,apiKey,data, data.external == "true"?true:false)
                        }else{
                            if(localStorage.getItem("invoicePayment") != data.invoice){
                                localStorage.removeItem("invoicePayment");
                                localStorage.setItem("invoicePayment", data.invoice);
                                makePayment(privateKey,apiKey,data, data.external == "true"?true:false)
                            }else{
                                makePayment(privateKey,apiKey,data, data.external == "true"?true:false)
                            }
                        }
                    }
                    var makePayment = function (privatekey, apikey, info, external) {
                        const headers = { "Content-Type": "application/json" } ;
                        headers["privatekey"] = privatekey;
                        headers["apikey"] = apikey;
                        var payment =   function (){
                            return  fetch("https://cms.epayco.co/checkout/payment/session", {
                                method: "POST",
                                body: JSON.stringify(info),
                                headers
                            })
                                .then(res =>  res.json())
                                .catch(err => {
                                    console.log(err.message);
                                    bntPagar.style.pointerEvents = "auto";
                                    bntPagar.style.opacity = "1";
                                });
                        }
                        payment()
                            .then(session => {
                                bntPagar.style.pointerEvents = "all";
                                if(session.data.sessionId != undefined){
                                    localStorage.removeItem("sessionPayment");
                                    localStorage.setItem("sessionPayment", session.data.sessionId);
                                    const handlerNew = window.ePayco.checkout.configure({
                                        sessionId: session.data.sessionId,
                                        external: external,
                                    });
                                    handlerNew.openNew()
                                }else{
                                    handler.open(data)
                                }
                            })
                            .catch(error => {
                                console.log(error.message);
                                bntPagar.style.pointerEvents = "auto";
                                bntPagar.style.opacity = "1";
                            });
                    }
                    var openChekout = function () {
                        //handler.open(data);
                        bntPagar.style.pointerEvents = "none";
                        bntPagar.style.opacity = "0.5";
                        openNewChekout()
                    }
                    bntPagar.addEventListener("click", openChekout);
            	    openChekout()
                </script>
                </form>
                </center>
        ',
            trim($this->settings['epayco_publickey']),
            $testMode,
            $descripcion,
            $descripcion,
            $order->get_id(),
            $currency,
            $order->get_total(),
            $base_tax,
            $iva,
            $ico,
            $basedCountry,
            $lang,
            $external,
            $confirm_url,
            $redirect_url,
            $name_billing,
            $address_billing,
            $email_billing,
            $phone_billing,
            $myIp,
            $testMode,
            $order->get_id(),
            trim($this->settings['epayco_publickey']),
            trim($this->settings['epayco_privatekey'])
        );
        return '<form  method="post" id="appGateway">
		        </form>';
    }

    /**
     * @param WC_Order $order
     * @param bool $single
     *
     * @return mixed
     */
    public function getPaymentsIdMeta(WC_Order $order, bool $single = true)
    {
        return $order->get_meta(self::PAYMENTS_IDS, $single);
    }

    /**
     * @param WC_Order $order
     * @param mixed $value
     *
     * @return void
     */
    public function setPaymentsIdData(WC_Order $order, $value): void
    {
        try {
            $logger = new WC_Logger();
            if ( $order instanceof WC_Order ) {
                $order->add_meta_data( self::PAYMENTS_IDS, $value );
                $order->save();
            }
        } catch (\Exception $ex) {
            $error_message = "Unable to update batch of orders on action got error: {$ex->getMessage()}";
            $logger->add($this->id,$error_message);
        }
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    function process_payment($order_id)
    {
        $order = new WC_Order($order_id);
        return array(
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        );
    }

    /**
     * Output for the order received page.
     * @param $order_id
     * @return void
     */
    function receipt_page($order_id)
    {
        echo ' <div class="loader-container">
                    <div class="loading"></div>
                </div>
                <p style="text-align: center;" class="epayco-title">
                    <span class="animated-points">' . esc_html__('Cargando métodos de pago', 'woo-epayco-gateway') . '</span>
                    <br><small class="epayco-subtitle"> ' . esc_html__('', 'woo-epayco-gateway') . '</small>
                </p>';

        if ($this->settings['epayco_lang'] === "2") {
            $epaycoButtonImage = 'https://multimedia.epayco.co/epayco-landing/btns/Boton-epayco-color-Ingles.png';
        } else {
            $epaycoButtonImage = 'https://multimedia.epayco.co/epayco-landing/btns/Boton-epayco-color1.png';
        }
        echo '<p>       
                 <center>
                    <a id="btn_epayco" href="#">
                        <img src="' . $epaycoButtonImage . '">
                    </a>
                 </center> 
               </p>';
        echo $this->generate_epayco_form($order_id);
    }

    /**
     * Check for Epayco HTTP Notification
     *
     * @return void
     */
    function check_ipn_response()
    {
        @ob_clean();
        $post = stripslashes_deep($_POST);
        if (true) {
            header('HTTP/1.1 200 OK');
            do_action('valid-' . $this->id . '-standard-ipn-request', $post);
        } else {
            wp_die('Do not access this page directly (ePayco)');
        }
    }

    function validate_ePayco_request()
    {
        @ob_clean();
        if (! empty($_REQUEST)) {
            header('HTTP/1.1 200 OK');
            do_action("ePayco_init_validation", $_REQUEST);
        } else {
            wp_die('Do not access this page directly (ePayco)');
        }
    }

    /**
     * Successful Payment!
     *
     * @access public
     * @param array $posted
     * @return void
     */
    function successful_request($validationData)
    {
        try {
            global $woocommerce;
            $order_id_info = sanitize_text_field($_GET['order_id']);
            $order_id_explode = explode('=', $order_id_info);
            $order_id_rpl = str_replace('?ref_payco', '', $order_id_explode);
            $order_id = $order_id_rpl[0];
            $order = new WC_Order($order_id);
            $isConfirmation = sanitize_text_field($_GET['confirmation']) == 1;
            if ($isConfirmation) {
                $x_signature = sanitize_text_field($_REQUEST['x_signature']);
                $x_cod_transaction_state = sanitize_text_field($_REQUEST['x_cod_transaction_state']);
                $x_ref_payco = sanitize_text_field($_REQUEST['x_ref_payco']);
                $x_transaction_id = sanitize_text_field($_REQUEST['x_transaction_id']);
                $x_amount = sanitize_text_field($_REQUEST['x_amount']);
                $x_currency_code = sanitize_text_field($_REQUEST['x_currency_code']);
                $x_test_request = trim(sanitize_text_field($_REQUEST['x_test_request']));
                $x_approval_code = trim(sanitize_text_field($_REQUEST['x_approval_code']));
                $x_franchise = trim(sanitize_text_field($_REQUEST['x_franchise']));
                $x_fecha_transaccion = trim(sanitize_text_field($_REQUEST['x_fecha_transaccion']));
            } else {
                $ref_payco = sanitize_text_field($_REQUEST['ref_payco']);
                if (empty($ref_payco)) {
                    $ref_payco = $order_id_rpl[1];
                }
                if (!$ref_payco) {
                    $explode = explode('=', $order_id);
                    $ref_payco = $explode[1];
                }
                if (!$ref_payco || $ref_payco=='undefined') {
                    wp_safe_redirect(wc_get_checkout_url());
                    exit();
                }
                $jsonData = $this->getRefPayco($ref_payco);
                if(is_null($jsonData)){
                    sleep(3);
                    $jsonData = $this->getRefPayco($ref_payco);
                }
                $validationData = $jsonData['data'];
                $x_signature = trim($validationData['x_signature']);
                $x_cod_transaction_state = (int)trim($validationData['x_cod_transaction_state']) ?
                    (int)trim($validationData['x_cod_transaction_state']) : (int)trim($validationData['x_cod_response']);
                $x_ref_payco = trim($validationData['x_ref_payco']);
                $x_transaction_id = trim($validationData['x_transaction_id']);
                $x_amount = trim($validationData['x_amount']);
                $x_currency_code = trim($validationData['x_currency_code']);
                $x_test_request = trim($validationData['x_test_request']);
                $x_approval_code = trim($validationData['x_approval_code']);
                $x_franchise = trim($validationData['x_franchise']);
                $x_fecha_transaccion = trim($validationData['x_fecha_transaccion']);
            }
            $epaycoOrder = [
                'refPayco' => $x_ref_payco
            ];
            $paymentsIdMetadata = $this->getPaymentsIdMeta($order);
            if (empty($paymentsIdMetadata)) {
                $this->setPaymentsIdData($order, implode(', ', $epaycoOrder));
            }
            foreach ($epaycoOrder as $paymentId) {
                $paymentDetailMetadata = $order->get_meta($paymentId);
                if (empty($paymentDetailMetadata)) {
                    $order->update_meta_data(self::PAYMENTS_IDS, $paymentId);
                    $order->save();
                }
            }
            // Validamos la firma
            if ($order_id != "" && $x_ref_payco != "") {
                $authSignature = $this->authSignature($x_ref_payco, $x_transaction_id, $x_amount, $x_currency_code);
            }
            $message = '';
            $messageClass = '';
            $current_state = $order->get_status();
            $isTestTransaction = $x_test_request == 'TRUE' ? "yes" : "no";
            update_option('epayco_order_status', $isTestTransaction);
            $isTestMode = get_option('epayco_order_status') == "yes" ? "true" : "false";
            $isTestPluginMode =$this->settings['epayco_testmode'];
            if (floatval($order->get_total()) == floatval($x_amount)) {
                if ("yes" == $isTestPluginMode) {
                    $validation = true;
                }
                if ("no" == $isTestPluginMode) {
                    if ($x_cod_transaction_state == 1) {
                        $validation = true;
                    } else {
                        if ($x_cod_transaction_state != 1) {
                            $validation = true;
                        } else {
                            $validation = false;
                        }
                    }
                }
            } else {
                $validation = false;
            }
            if ($authSignature == $x_signature && $validation) {
                Epayco_Transaction_Handler::handle_transaction($order, [
                    'x_cod_transaction_state' => $x_cod_transaction_state,
                    'x_ref_payco'             => $x_ref_payco,
                    'x_fecha_transaccion'     => $x_fecha_transaccion,
                    'x_franchise'             => $x_franchise,
                    'x_approval_code'         => $x_approval_code,
                    'is_confirmation'         => $isConfirmation,
                ], [
                    'test_mode'               => $isTestMode,
                    'end_order_state'         => $this->settings['epayco_endorder_state'],
                    'cancel_order_state'      => $this->settings['epayco_cancelled_endorder_state'],
                    'reduce_stock_pending'    => $this->get_option('epayco_reduce_stock_pending'),
                ]);


                //validar si la transaccion esta pendiente y pasa a rechazada y ya habia descontado el stock
                if (($current_state == 'on-hold' || $current_state == 'pending') && ((int)$x_cod_transaction_state == 2 || (int)$x_cod_transaction_state == 4) && EpaycoOrder::ifStockDiscount($order_id)) {
                    //si no se restauro el stock restaurarlo inmediatamente
                    // Epayco_Transaction_Handler::restore_stock($order_id);
                };
            } else {

                if (
                    $current_state == "epayco-processing" ||
                    $current_state == "epayco-completed" ||
                    $current_state == "processing" ||
                    $current_state == "completed"
                ) {
                } else {
                    $message = 'Firma no valida';
                    $orderStatus = 'epayco-failed';
                    if ($x_cod_transaction_state != 1 && !empty($x_cod_transaction_state)) {
                        if (
                            $current_state == "epayco_failed" ||
                            $current_state == "epayco_cancelled" ||
                            $current_state == "failed" ||
                            $current_state == "epayco-cancelled" ||
                            $current_state == "epayco-failed"||
                            $current_state == "pending"
                        ) {
                        } else {
                            Epayco_Transaction_Handler::restore_stock($order_id);
                            $order->update_status($orderStatus);
                            $messageClass = 'error';
                        }
                    }
                    echo $x_cod_transaction_state . " firma no valida: " . $validation;
                }

            }

            if (isset($_REQUEST['confirmation'])) {
                echo $x_cod_transaction_state;
                exit();
            } else {
                if ($this->get_option('epayco_url_response') == 0) {
                    $redirect_url = $order->get_checkout_order_received_url();
                } else {
                    $woocommerce->cart->empty_cart();
                    $redirect_url = get_permalink($this->get_option('epayco_url_response'));
                    $redirect_url = add_query_arg(['ref_payco' => $ref_payco], $redirect_url);
                }
            }

            $arguments = array();
            foreach ($validationData as $key => $value) {
                $arguments[$key] = $value;
            }

            unset($arguments["wc-api"]);
            $arguments['msg'] = urlencode($message);
            $arguments['type'] = $messageClass;
            $response_data = $this->settings['response_data'] == "yes" ? true : false;

            if ($response_data) {
                $redirect_url = add_query_arg($arguments, $redirect_url);
            }

            wp_redirect($redirect_url);
        }catch (\Exception $ex) {
            $error_message = "successful_request got error: {$ex->getMessage()}";
            self::$logger->add($this->id, $error_message);
            throw new Exception($error_message);
        }
    }

    public function getRefPayco($refPayco)
    {
        $url = 'https://apify.epayco.co/validation/v1/reference/' . $refPayco;
        $response = wp_remote_get($url);
        $body = wp_remote_retrieve_body($response);
        $jsonData = @json_decode($body, true);
        return $jsonData;
    }

    public function authSignature($x_ref_payco, $x_transaction_id, $x_amount, $x_currency_code)
    {
        $signature = hash(
            'sha256',
            trim($this->settings['epayco_customerid']) . '^'
            . trim($this->settings['epayco_secretkey']) . '^'
            . $x_ref_payco . '^'
            . $x_transaction_id . '^'
            . $x_amount . '^'
            . $x_currency_code
        );
        return $signature;
    }

    function add_expiration($order, $data)
    {
        $items_count  = WC()->cart->get_cart_contents_count();

        $order->update_meta_data('expiration_date', date('Y-m-d H:i:s', strtotime('+' . ($items_count * 60) . ' minutes')));
    }

    /**
     * @param $validationData
     */
    function ePayco_successful_validation($validationData)
    {
        $username = sanitize_text_field($validationData['epayco_publickey']);
        $password = sanitize_text_field($validationData['epayco_privatey']);
        $response = wp_remote_post('https://eks-apify-service.epayco.io/login', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
            ),
        ));
        $data = json_decode(wp_remote_retrieve_body($response));
        if ($data->token) {
            echo "success";
            exit();
        }
    }

    function string_sanitize($string, $force_lowercase = true, $anal = false)
    {

        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]", "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;", "â€”", "â€“", ",", "<", ".", ">", "/", "?");
        $clean = trim(str_replace($strip, "", strip_tags($string)));
        $clean = preg_replace('/\s+/', "_", $clean);
        $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean;
        return $clean;
    }


    public function getCustomerIp()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    public function getEpaycoORders()
    {
        try {
            $orders = wc_get_orders(array(
                'limit'    => -1,
                'status'   => 'on-hold',
                'meta_query' => array(
                    'key' => self::PAYMENTS_IDS
                )
            ));
            $ref_payco_list = [];
            foreach ($orders as $order) {
                $ref_payco = $this->syncOrderStatus($order);
                if ($ref_payco) {
                    $ref_payco_list[] = $ref_payco;
                }
            }
            if (is_array($ref_payco_list) && !empty($ref_payco_list)) {
                $token = $this->epyacoBerarToken();
                if ($token) {
                    foreach ($ref_payco_list as $ref_payco) {
                        $path = "payment/transaction";
                        $data = [ "referencePayco" => $ref_payco];
                        $epayco_status = $this->getEpaycoStatusOrder($path,$data, $token);
                        if ($epayco_status['success']) {
                            if (isset($epayco_status['data']) && is_array($epayco_status['data'])) {
                                $this->epaycoUploadOrderStatus($epayco_status);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $ex) {
            $error_message = "Unable to update batch of orders on action got error: {$ex->getMessage()}";
            self::$logger->add($this->id, $error_message);
            throw new Exception($error_message);
        }
    }

    public function getWoocommercePendigsORders(){
        try {
            $orders = wc_get_orders([
                'limit'        => -1, 
                'status'       => 'pending', 
                'payment_method' => 'epayco',
                'orderby'      => 'date',
                'order'        => 'DESC',
            ]);
            $token = $this->epyacoBerarToken();
            foreach ($orders as $order) {
                $orderId = $order->get_id();
                if ($token) {
                    $path = "transaction/detail";
                    $data = [ "filter" => ["referenceClient" => $orderId]];
                    $epayco_status = $this->getEpaycoStatusOrder($path,$data, $token);
                    if ($epayco_status['success']) {
                        if (isset($epayco_status['data']) && is_array($epayco_status['data'])) {
                            foreach ($epayco_status['data'] as $epaycoData) {
                                $refPayco = $epaycoData['referencePayco'];
                            }
                            $epaycoOrder = [
                                'refPayco' => $refPayco
                            ];
                            $paymentsIdMetadata = $this->getPaymentsIdMeta($order);
                            if (empty($paymentsIdMetadata)) {
                                $this->setPaymentsIdData($order, implode(', ', $epaycoOrder));
                            }
                            foreach ($epaycoOrder as $paymentId) {
                                $paymentDetailMetadata = $order->get_meta($paymentId);
                                if (empty($paymentDetailMetadata)) {
                                    $order->update_meta_data(self::PAYMENTS_IDS, $paymentId);
                                    $order->save();
                                }
                            }
                            $path = "payment/transaction";
                            $data = [ "referencePayco" => $refPayco];
                            $epayco_status = $this->getEpaycoStatusOrder($path,$data, $token);
                            if ($epayco_status['success']) {
                                if (isset($epayco_status['data']) && is_array($epayco_status['data'])) {
                                    $this->epaycoUploadOrderStatus($epayco_status);
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $ex) {
            $error_message = "Unable to update batch of orders on action got error: {$ex->getMessage()}";
            self::$logger->add($this->id, $error_message);
            throw new Exception($error_message);
        }
    }

    public function getEpaycoStatusOrder($path,$data, $token)
    {
        if ($token) {
            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer '.$token['token'],
            ];
            return $this->epayco_realizar_llamada_api($path, $data, $headers);
            
        }
    }

    public function epaycoUploadOrderStatus($epayco_status)
    {
        $order_id = isset($epayco_status['data']['transaction']['extra1']) ?$epayco_status['data']['transaction']['extra1'] : null;
        //$x_cod_transaction_state = isset($epayco_status['data']['x_cod_transaction_state']) ? $epayco_status['data']['x_cod_transaction_state'] : null;
        $status = isset($epayco_status['data']['transaction']['status']) ? $epayco_status['data']['transaction']['status'] : null;
        $ePaycoStatus = strtolower($status);
        $x_ref_payco = isset($epayco_status['data']['transaction']['refPayco']) ? $epayco_status['data']['transaction']['refPayco'] : null;
        $x_fecha_transaccion = isset($epayco_status['data']['transaction']['date']) ? $epayco_status['data']['transaction']['date'] : null;
        $x_franchise = isset($epayco_status['data']['transaction']['bank']) ? $epayco_status['data']['transaction']['bank'] : null;
        $x_approval_code = isset($epayco_status['data']['transaction']['autorizacion']) ? $epayco_status['data']['transaction']['autorizacion'] : null;
        $x_cod_transaction_state =  isset($epayco_status['data']['transaction']['codeResponse']) ? $epayco_status['data']['transaction']['codeResponse'] :$this->get_epayco_estado_codigo_detallado($ePaycoStatus);
        $isTestMode = get_option('epayco_order_status') == "yes" ? "true" : "false";
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                Epayco_Transaction_Handler::handle_transaction($order, [
                    'x_cod_transaction_state' => $x_cod_transaction_state,
                    'x_ref_payco'             => $x_ref_payco,
                    'x_fecha_transaccion'     => $x_fecha_transaccion,
                    'x_franchise'             => $x_franchise,
                    'x_approval_code'         => $x_approval_code,
                    'is_confirmation'         => true,
                ], [
                    'test_mode'               => $isTestMode,
                    'end_order_state'         => $this->settings['epayco_endorder_state'],
                    'cancel_order_state'      => $this->settings['epayco_cancelled_endorder_state'],
                    'reduce_stock_pending'    => $this->get_option('epayco_reduce_stock_pending'),
                ]);
            }
        }
    }

    public function get_epayco_estado_codigo_detallado($estado_texto) {
        $estado_texto = strtolower(trim($estado_texto));

        switch ($estado_texto) {
            case 'aprobada':
            case 'aceptada':
                return 1;

            case 'abandonada':
                return 10;

            case 'fallida':
                return 4;

            case 'cancelada':
            case 'rechazada':
                return 2;

            case 'pendiente':
                return 3;

            case 'retenido':
                return 7;

            case 'reversada':
            case 'reversado':
                return 6;

            default:
                return 0;
        }
    }

    public function syncOrderStatus(\WC_Order $order): string
    {
        $paymentsIds   = explode(',', $order->get_meta(self::PAYMENTS_IDS));
        $lastPaymentId = trim(end($paymentsIds));
        if ($lastPaymentId) {
            return $lastPaymentId;
        } else {
            return false;
        }
    }

    public function epyacoBerarToken()
    {
        $publicKey = $this->settings['epayco_publickey'];
        $privateKey = $this->settings['epayco_privatekey'];

        if (!isset($_COOKIE[$publicKey])) {
            $token = base64_encode($publicKey . ":" . $privateKey);
            $bearer_token = $token;
            $cookie_value = $bearer_token;
            setcookie($publicKey, $cookie_value, time() + (60 * 14), "/");
        } else {
            $bearer_token = $_COOKIE[$publicKey];
        }

        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => "Basic " . $bearer_token
        );
        return $this->epayco_realizar_llamada_api("login", [], $headers);
    }

    public function epayco_realizar_llamada_api($path, $data, $headers, $method = 'POST')
    {
        $url = 'https://apify.epayco.co/' . $path;

        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => json_encode($data),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            self::$logger->add($this->id, "Error al hacer la llamada a la API de ePayco: " . $error_message);
            error_log("Error al hacer la llamada a la API de ePayco: " . $error_message);
            return false;
        } else {
            $response_body = wp_remote_retrieve_body($response);
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code == 200) {
                $responseTransaction = json_decode($response_body, true);
                return $responseTransaction;
            } else {
                self::$logger->add($this->id,"Error en la respuesta de la API de ePayco, código de estado: " . $status_code);
                error_log("Error en la respuesta de la API de ePayco, código de estado: " . $status_code);
                return false;
            }
        }
    }
}
