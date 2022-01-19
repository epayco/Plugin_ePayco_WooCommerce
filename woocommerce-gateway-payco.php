<?php
/**
 * @since             1.0.0
 * @package           ePayco_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       ePayco Gateway WooCommerce
 * Description:       Plugin ePayco Gateway for WooCommerce.
 * Version:           5.4.0
 * Author:            ePayco
 * Author URI:        http://epayco.co
 *Lice
 * Text Domain:       epayco-woocommerce
 * Domain Path:       /languages
 */


if (!defined('WPINC')) {
    die;
}


require_once(dirname(__FILE__) . '/lib/EpaycoOrder.php');
//require_once(dirname(__FILE__) . '/style.css');
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('plugins_loaded', 'init_epayco_woocommerce', 0);
    function init_epayco_woocommerce()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }
        class WC_ePayco extends WC_Payment_Gateway
        {
            public $max_monto;
            public function __construct()
            {
                $this->id = 'epayco';
                $this->icon = plugin_dir_url(__FILE__).'lib/logo.png';
                $this->method_title = __('ePayco Checkout Gateway', 'epayco_woocommerce');
                $this->method_description = __('Acepta tarjetas de credito, depositos y transferencias.', 'epayco_woocommerce');
                $this->order_button_text = __('Pagar', 'epayco_woocommerce');
                $this->has_fields = false;
                $this->supports = array('products');
                $this->init_form_fields();
                $this->init_settings();
                $this->msg['message']   = "";
                $this->msg['class']     = "";
                $this->title = $this->get_option('epayco_title');
                $this->epayco_customerid = $this->get_option('epayco_customerid');
                $this->epayco_secretkey = $this->get_option('epayco_secretkey');
                $this->epayco_publickey = $this->get_option('epayco_publickey');
                $this->monto_maximo = $this->get_option('monto_maximo');
                $this->max_monto = $this->get_option('monto_maximo');
                $this->description = $this->get_option('description');
                $this->epayco_testmode = $this->get_option('epayco_testmode');
                if ($this->get_option('epayco_reduce_stock_pending') !== null ) {
                    $this->epayco_reduce_stock_pending = $this->get_option('epayco_reduce_stock_pending');
                }else{
                    $this->epayco_reduce_stock_pending = "yes";
                }
                $this->epayco_type_checkout=$this->get_option('epayco_type_checkout');
                $this->epayco_endorder_state=$this->get_option('epayco_endorder_state');
                $this->epayco_url_response=$this->get_option('epayco_url_response');
                $this->epayco_url_confirmation=$this->get_option('epayco_url_confirmation');
                $this->epayco_lang=$this->get_option('epayco_lang')?$this->get_option('epayco_lang'):'es';
                $this->response_data = $this->get_option('response_data');
                add_filter('woocommerce_thankyou_order_received_text', array(&$this, 'order_received_message'), 10, 2 );
                add_action('ePayco_init', array( $this, 'ePayco_successful_request'));
                add_action('woocommerce_receipt_' . $this->id, array(&$this, 'receipt_page'));
                add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_ePayco_response' ) );
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                add_action('wp_ajax_nopriv_returndata',array($this,'datareturnepayco_ajax'));
                add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
               
                if ($this->epayco_testmode == "yes") {
                    if (class_exists('WC_Logger')) {
                        $this->log = new WC_Logger();
                    } else {
                        $this->log = WC_ePayco::woocommerce_instance()->logger();
                    }
                }
            }

            function order_received_message( $text, $order ) {
                if(!empty($_GET['msg'])){
                    return $text .' '.$_GET['msg'];
                }
                return $text;
            }

            public function is_valid_for_use()
            {
                return in_array(get_woocommerce_currency(), array('COP', 'USD'));
            }

            public function admin_options()
            {
                ?>
                <style>
                    tbody{
                    }
                    .epayco-table tr:not(:first-child) {
                        border-top: 1px solid #ededed;
                    }
                    .epayco-table tr th{
                        padding-left: 15px;
                        text-align: -webkit-right;
                    }
                    .epayco-table input[type="text"]{
                        padding: 8px 13px!important;
                        border-radius: 3px;
                        width: 100%!important;
                    }
                    .epayco-table .description{
                        color: #afaeae;
                    }
                    .epayco-table select{
                        padding: 8px 13px!important;
                        border-radius: 3px;
                        width: 100%!important;
                        height: 37px!important;
                    }
                    .epayco-required::before{
                        content: '* ';
                        font-size: 16px;
                        color: #F00;
                        font-weight: bold;
                    }

                </style>
                <div class="container-fluid">
                    <div class="panel panel-default" style="">
                        <img  src="<?php echo plugin_dir_url(__FILE__).'lib/logo.png' ?>">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-pencil"></i>Configuración <?php _e('ePayco', 'epayco_woocommerce'); ?></h3>
                        </div>

                        <div style ="color: #31708f; background-color: #d9edf7; border-color: #bce8f1;padding: 10px;border-radius: 5px;">
                            <b>Este modulo le permite aceptar pagos seguros por la plataforma de pagos ePayco</b>
                            <br>Si el cliente decide pagar por ePayco, el estado del pedido cambiara a ePayco Esperando Pago
                            <br>Cuando el pago sea Aceptado o Rechazado ePayco envia una configuracion a la tienda para cambiar el estado del pedido.
                        </div>

                        <div class="panel-body" style="padding: 15px 0;background: #fff;margin-top: 15px;border-radius: 5px;border: 1px solid #dcdcdc;border-top: 1px solid #dcdcdc;">
                            <table class="form-table epayco-table">
                                <?php
                                if ($this->is_valid_for_use()) :
                                    $this->generate_settings_html();
                                else :
                                    if ( is_admin() && ! defined( 'DOING_AJAX')) {
                                        echo '<div class="error"><p><strong>' . __( 'ePayco: Requiere que la moneda sea USD O COP', 'epayco-woocommerce' ) . '</strong>: ' . sprintf(__('%s', 'woocommerce-mercadopago' ), '<a href="' . admin_url() . 'admin.php?page=wc-settings&tab=general#s2id_woocommerce_currency">' . __( 'Click aquí para configurar!', 'epayco_woocommerce') . '</a>' ) . '</p></div>';
                                    }
                                endif;
                                ?>
                            </table>
                        </div>
                    </div>
                </div>
                <?php
            }

            public function init_form_fields()
            {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('Habilitar/Deshabilitar', 'epayco_woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Habilitar ePayco Checkout', 'epayco_woocommerce'),
                        'default' => 'yes'
                    ),
                    'epayco_title' => array(
                        'title' => __('<span class="epayco-required">Título</span>', 'epayco_woocommerce'),
                        'type' => 'text',
                        'description' => __('Corresponde al titulo que el usuario ve durante el checkout.', 'epayco_woocommerce'),
                        'default' => __('Checkout ePayco (Tarjetas de crédito,debito,efectivo)', 'epayco_woocommerce'),
                        //'desc_tip' => true,
                    ),
                    'description' => array(
                        'title' => __('<span class="epayco-required">Descripción</span>', 'epayco_woocommerce'),
                        'type' => 'textarea',
                        'description' => __('Corresponde a la descripción que verá el usuaro durante el checkout', 'epayco_woocommerce'),
                        'default' => __('Checkout ePayco (Tarjetas de crédito,debito,efectivo)', 'epayco_woocommerce'),
                        //'desc_tip' => true,
                    ),
                    'epayco_customerid' => array(
                        'title' => __('<span class="epayco-required">P_CUST_ID_CLIENTE</span>', 'epayco_woocommerce'),
                        'type' => 'text',
                        'description' => __('ID de cliente que lo identifica en ePayco. Lo puede encontrar en su panel de clientes en la opción configuración.', 'epayco_woocommerce'),
                        'default' => '',
                        //'desc_tip' => true,
                        'placeholder' => '',
                    ),
                    'epayco_secretkey' => array(
                        'title' => __('<span class="epayco-required">P_KEY</span>', 'epayco_woocommerce'),
                        'type' => 'text',
                        'description' => __('LLave para firmar la información enviada y recibida de ePayco. Lo puede encontrar en su panel de clientes en la opción configuración.', 'epayco_woocommerce'),
                        'default' => '',
                        'placeholder' => ''
                    ),
                    'epayco_publickey' => array(
                        'title' => __('<span class="epayco-required">PUBLIC_KEY</span>', 'epayco_woocommerce'),
                        'type' => 'text',
                        'description' => __('LLave para autenticar y consumir los servicios de ePayco, Proporcionado en su panel de clientes en la opción configuración.', 'epayco_woocommerce'),
                        'default' => '',
                        'placeholder' => ''
                    ),
                    'epayco_testmode' => array(
                        'title' => __('Sitio en pruebas', 'epayco_woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Habilitar el modo de pruebas', 'epayco_woocommerce'),
                        'description' => __('Habilite para realizar pruebas', 'epayco_woocommerce'),
                        'default' => 'no',
                    ),
                    'epayco_type_checkout' => array(
                        'title' => __('Tipo Checkout', 'epayco_woocommerce'),
                        'type' => 'select',
                        'css' =>'line-height: inherit',
                        'label' => __('Seleccione un tipo de Checkout:', 'epayco_woocommerce'),
                        'description' => __('(Onpage Checkout, el usuario al pagar permanece en el sitio) ó (Standart Checkout, el usario al pagar es redireccionado a la pasarela de ePayco)', 'epayco_woocommerce'),
                        'options' => array('false'=>"Onpage Checkout","true"=>"Standart Checkout"),
                    ),
                    'epayco_endorder_state' => array(
                        'title' => __('Estado Final del Pedido', 'epayco_woocommerce'),
                        'type' => 'select',
                        'css' =>'line-height: inherit',
                        'description' => __('Seleccione el estado del pedido que se aplicará a la hora de aceptar y confirmar el pago de la orden', 'epayco_woocommerce'),
                        'options' => array(
                            'epayco-processing'=>"ePayco Procesando Pago",
                            "epayco-completed"=>"ePayco Pago Completado",
                            'processing'=>"Procesando",
                            "completed"=>"Completado"
                        ),
                    ),
                    'epayco_url_response' => array(
                        'title' => __('Página de Respuesta', 'epayco_woocommerce'),
                        'type' => 'select',
                        'css' =>'line-height: inherit',
                        'description' => __('Url de la tienda donde se redirecciona al usuario luego de pagar el pedido', 'epayco_woocommerce'),
                        'options'       => $this->get_pages(__('Seleccionar pagina', 'epayco-woocommerce')),
                    ),
                    'epayco_url_confirmation' => array(
                        'title' => __('Página de Confirmación', 'epayco_woocommerce'),
                        'type' => 'select',
                        'css' =>'line-height: inherit',
                        'description' => __('Url de la tienda donde ePayco confirma el pago', 'epayco_woocommerce'),
                        'options'       => $this->get_pages(__('Seleccionar pagina', 'epayco-woocommerce')),
                    ),
                    'epayco_reduce_stock_pending' => array(
                        'title' => __('Reducir el stock en transacciones pendientes', 'epayco_woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Habilitar', 'epayco_woocommerce'),
                        'description' => __('Habilite para reducir el stock en transacciones pendientes', 'epayco_woocommerce'),
                        'default' => 'yes',
                    ),
                    'epayco_lang' => array(
                        'title' => __('Idioma del Checkout', 'epayco_woocommerce'),
                        'type' => 'select',
                        'css' =>'line-height: inherit',
                        'description' => __('Seleccione el idioma del checkout', 'epayco_woocommerce'),
                        'options' => array('es'=>"Español","en"=>"Inglés"),
                    ),
                    'response_data' => array(
                        'title' => __('Habilitar envió de atributos a través de la URL de respuesta', 'epayco_woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Habilitar el modo redireccion con data', 'epayco_woocommerce'),
                        'description' => __('Al habilitar esta opción puede exponer información sensible de sus clientes, el uso de esta opción es bajo su responsabilidad, conozca esta información en el siguiente  <a href="https://docs.epayco.co/payments/checkout#scroll-response-p" target="_blank">link.</a>', 'epayco_woocommerce'),
                        'default' => 'no',
                    ),
                    'monto_maximo' => array(
                        'title' => __('monto maximo', 'epayco_woocommerce'),
                        'type' => 'text',
                        'description' => __('ingresa el monto maximo permitido ha pagar por el método de pago', 'epayco_woocommerce'),
                        'default' => '3000000',
                        //'desc_tip' => true,
                        'placeholder' => '3000000',
                    ),
                );
            }


            /**
             * @param $order_id
             * @return array
             */
            public function process_payment($order_id)
            {
                $order = new WC_Order($order_id);
                $order->reduce_order_stock();
                if (version_compare( WOOCOMMERCE_VERSION, '2.1', '>=')) {
                    return array(
                        'result'    => 'success',
                        'redirect'  => add_query_arg('order-pay', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))
                    );
                } else {
                    return array(
                        'result'    => 'success',
                        'redirect'  => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))
                    );
                }
            }


            function get_pages($title = false, $indent = true) {

                $wp_pages = get_pages('sort_column=menu_order');
                $page_list = array();
                if ($title) $page_list[] = $title;
                foreach ($wp_pages as $page) {
                    $prefix = '';
                    // show indented child pages?
                    if ($indent) {
                        $has_parent = $page->post_parent;
                        while($has_parent) {
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
             * @param $order_id
             */

            public function receipt_page($order_id)
            {
                global $woocommerce;
                $order = new WC_Order($order_id);
                $descripcionParts = array();
                foreach ($order->get_items() as $product) {
                    $clearData = str_replace('_', ' ', $this->string_sanitize($product['name']));
                    $descripcionParts[] = $clearData;
                }
                $descripcion = implode(' - ', $descripcionParts);
                $currency = strtolower(get_woocommerce_currency());
                $testMode = $this->epayco_testmode == "yes" ? "true" : "false";
                $basedCountry = WC()->countries->get_base_country();
                $external=$this->epayco_type_checkout;
                $redirect_url =get_site_url() . "/";
                $confirm_url=get_site_url() . "/";
                $redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
                $redirect_url = add_query_arg( 'order_id', $order_id, $redirect_url );
                
                if ($this->get_option('epayco_url_confirmation' ) == 0) {
                        $confirm_url = add_query_arg( 'wc-api', get_class( $this ), $confirm_url );
                        $confirm_url = add_query_arg( 'order_id', $order_id, $confirm_url );
                        $confirm_url = $redirect_url.'&confirmation=1';
                    } else {
                        
                        $confirm_url = get_permalink($this->get_option('epayco_url_confirmation'));
                }
                
               
                
                $name_billing=$order->get_billing_first_name().' '.$order->get_billing_last_name();
                $address_billing=$order->get_billing_address_1();
                $phone_billing=@$order->billing_phone;
                $email_billing=@$order->billing_email;
                $order = new WC_Order($order_id);
                $tax=$order->get_total_tax();
                $tax=round($tax,2);
                if((int)$tax>0){
                    $base_tax=$order->get_total()-$tax;
                }else{
                    $base_tax=$order->get_total();
                    $tax=0;
                }

                //Busca si ya se restauro el stock
                if (!EpaycoOrder::ifExist($order_id)) {
                    //si no se restauro el stock restaurarlo inmediatamente
                    EpaycoOrder::create($order_id,1);
                }


                if ($this->epayco_lang !== "es") {
                    $msgEpaycoCheckout = '<span class="animated-points">Loading payment methods</span>
                               <br><small class="epayco-subtitle"> If they do not load automatically, click on the "Pay with ePayco" button</small>';
                    $epaycoButtonImage = plugin_dir_url(__FILE__).'lib/Boton-color-Ingles.png';
                }else{
                    $msgEpaycoCheckout = '<span class="animated-points">Cargando métodos de pago</span>
                    <br><small class="epayco-subtitle"> Si no se cargan automáticamente, de clic en el botón "Pagar con ePayco</small>';
                    $epaycoButtonImage =  plugin_dir_url(__FILE__).'lib/Boton-color-espanol.png';
                }

                echo sprintf('
                        <div class="loader-container">
                            <div class="loading"></div>
                        </div>
                        <p style="text-align: center;" class="epayco-title">
                           '.$msgEpaycoCheckout.'
                        </p>                        
                        <center>

                        <form id="appGateway">
                            <script
                                src="https://checkout.epayco.co/checkout.js"
                                class="epayco-button"
                                data-epayco-key="%s"
                                data-epayco-test="%s"
                                data-epayco-name="%s"
                                data-epayco-description="%s"
                                data-epayco-invoice="%s"      
                                data-epayco-currency="%s"                   
                                data-epayco-amount="%s"
                                data-epayco-tax="%s"
                                data-epayco-tax-base="%s"
                                data-epayco-country="%s"
                                data-epayco-external="%s"                       
                                data-epayco-response="%s"
                                data-epayco-confirmation="%s"
                                data-epayco-email-billing="%s"
                                data-epayco-name-billing="%s"
                                data-epayco-address-billing="%s"
                                data-epayco-lang="%s"
                                data-epayco-mobilephone-billing="%s"
                                data-epayco-button="'.$epaycoButtonImage.'"
                                data-epayco-autoclick="true"
                                >
                            </script>
                            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
                            <script>
                            $(document).keydown(function (event) {
                                if (event.keyCode == 123) {
                                    return false;
                                } else if (event.ctrlKey && event.shiftKey && event.keyCode == 73) {        
                                    return false;
                                }
                            });
                            </script>
                        </form>
                        </center>
                ',trim($this->epayco_publickey),
                    $testMode,
                    $descripcion,
                    $descripcion,
                    $order->get_id(),
                    $currency,
                    $order->get_total(),
                    $tax,
                    $base_tax,
                    $basedCountry,
                    $external,
                    $redirect_url,
                    $confirm_url,
                    $email_billing,
                    $name_billing,
                    $address_billing,
                    $this->epayco_lang,
                    $phone_billing);

            }


            public function datareturnepayco_ajax()
            {
                die();
            }


            public function authSignature($x_ref_payco, $x_transaction_id, $x_amount, $x_currency_code){
                $signature = hash('sha256',
                    trim($this->epayco_customerid).'^'
                    .trim($this->epayco_secretkey).'^'
                    .$x_ref_payco.'^'
                    .$x_transaction_id.'^'
                    .$x_amount.'^'
                    .$x_currency_code
                );

                return $signature;
            }

            function check_ePayco_response(){
                @ob_clean();
                if ( ! empty( $_REQUEST ) ) {
                    header( 'HTTP/1.1 200 OK' );
                    do_action( "ePayco_init", $_REQUEST );
                } else {
                    wp_die( __("ePayco Request Failure", 'epayco-woocommerce') );
                }
            }


            /**
             * @param $validationData
             */
            function ePayco_successful_request($validationData)
            {
                global $woocommerce;

                $order_id_info = sanitize_text_field($_GET['order_id']);
                $order_id_explode = explode('=',$order_id_info);
                $order_id_rpl  = str_replace('?ref_payco','',$order_id_explode);
                $order_id = $order_id_rpl[0];
                $order = new WC_Order($order_id);
                $ref_payco = sanitize_text_field($_GET['ref_payco']);
                $isConfirmation = sanitize_text_field($_GET['confirmation']) == 1;
                if(empty($ref_payco)){
                    $ref_payco =$order_id_rpl[1];
                }
                
                if ($isConfirmation){
                    $x_signature = sanitize_text_field($_REQUEST['x_signature']);
                    $x_cod_transaction_state = sanitize_text_field($_REQUEST['x_cod_transaction_state']);
                    $x_ref_payco = sanitize_text_field($_REQUEST['x_ref_payco']);
                    $x_transaction_id = sanitize_text_field($_REQUEST['x_transaction_id']);
                    $x_amount = sanitize_text_field($_REQUEST['x_amount']);
                    $x_currency_code = sanitize_text_field($_REQUEST['x_currency_code']);
                    $x_test_request = trim(sanitize_text_field($_REQUEST['x_test_request']));
                    $x_approval_code = trim(sanitize_text_field($_REQUEST['x_approval_code']));
                    $x_franchise = trim(sanitize_text_field($_REQUEST['x_franchise']));
                }
                else {

                    if (!$ref_payco) 
                    {
                        $explode=explode('=',$order_id);
                        $ref_payco=$explode[1];
                    }
                    
                    $url = 'https://secure.epayco.co/validation/v1/reference/'.$ref_payco;
                    $response = wp_remote_get(  $url );
                    $body = wp_remote_retrieve_body( $response );
                    $jsonData = @json_decode($body, true);
                    $validationData = $jsonData['data'];
                    $x_signature = trim($validationData['x_signature']);
                    $x_cod_transaction_state = (int)trim($validationData['x_cod_transaction_state']);
                    $x_ref_payco = trim($validationData['x_ref_payco']);
                    $x_transaction_id = trim($validationData['x_transaction_id']);
                    $x_amount = trim($validationData['x_amount']);
                    $x_currency_code = trim($validationData['x_currency_code']);
                    $x_test_request = trim($validationData['x_test_request']);
                    $x_approval_code = trim($validationData['x_approval_code']);
                    $x_franchise = trim($validationData['x_franchise']);
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
                $isTestPluginMode = $this->epayco_testmode;
                if(floatval($order->get_total()) == floatval($x_amount)){
                    if("yes" == $isTestPluginMode){
                        $validation = true;
                    }
                    if("no" == $isTestPluginMode ){
                        if($x_approval_code != "000000" && $x_cod_transaction_state == 1){
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
                if($authSignature == $x_signature && $validation){
                    switch ($x_cod_transaction_state) {
                        case 1: {
                            if($current_state == "epayco_failed" ||
                            $current_state == "epayco_cancelled" ||
                            $current_state == "failed" ||
                            $current_state == "epayco-cancelled" ||
                            $current_state == "epayco-failed"
                        ){}else{
                             //Busca si ya se descontó el stock
                        if (!EpaycoOrder::ifStockDiscount($order_id)){
                            
                            //se descuenta el stock
                            EpaycoOrder::updateStockDiscount($order_id,1);
                                
                        }
                       if($isTestMode=="true"){
                                $message = 'Pago exitoso Prueba';
                                switch ($this->epayco_endorder_state ){
                                    case 'epayco-processing':{
                                        $orderStatus ='epayco_processing';
                                    }break;
                                    case 'epayco-completed':{
                                        $orderStatus ='epayco_completed';
                                    }break;
                                    case 'processing':{
                                        $orderStatus ='processing_test';
                                    }break;
                                    case 'completed':{
                                        $orderStatus ='completed_test';
                                    }break;
                                }
                            }else{
                                $message = 'Pago exitoso';
                                $orderStatus = $this->epayco_endorder_state;
                            }
                            $order->payment_complete($x_ref_payco);
                            $order->update_status($orderStatus);
                            $order->add_order_note($message);
                        }
                        echo "1";
                        } break;
                        case 2: {
                            if($isTestMode=="true"){
                                if(
                                    $current_state == "epayco_processing" ||
                                    $current_state == "epayco_completed" ||
                                    $current_state == "processing_test" ||
                                    $current_state == "completed_test"
                                ){}else{
                                    $message = 'Pago rechazado Prueba: ' .$x_ref_payco;
                                    $messageClass = 'woocommerce-error';
                                    $order->update_status('epayco_cancelled');
                                    $order->add_order_note($message);
                                    if($current_state =="epayco-cancelled"||
                                    $current_state == "epayco_cancelled" ){
                                       }else{
                                         $this->restore_order_stock($order->id);
                                    }
                                }
                            }else{
                                if(
                                    $current_state == "epayco-processing" ||
                                    $current_state == "epayco-completed" ||
                                    $current_state == "processing-test" ||
                                    $current_state == "completed-test"||
                                    $current_state == "processing" ||
                                    $current_state == "completed"
                                ){}else{
                                    $message = 'Pago rechazado: ' .$x_ref_payco;
                                    $messageClass = 'woocommerce-error';
                                    $order->update_status('epayco-cancelled');
                                    $order->add_order_note($message);
                                    if($current_state !="epayco-cancelled"){
                                        $this->restore_order_stock($order->id);
                                    }
                                }
                            }
                        echo "2";
                        } break;
                        case 3: {
                            
                            //Busca si ya se restauro el stock y si se configuro reducir el stock en transacciones pendientes
                            if (!EpaycoOrder::ifStockDiscount($order_id) && $this->get_option('epayco_reduce_stock_pending') != 'yes') {
                                //actualizar el stock
                                EpaycoOrder::updateStockDiscount($order_id,1);
                            }

                            if($isTestMode=="true"){
                                $message = 'Pago pendiente de aprobación Prueba';
                                $orderStatus = "epayco_on_hold";
                            }else{
                                $message = 'Pago pendiente de aprobación';
                                $orderStatus = "epayco-on-hold";
                            }
                            if($x_franchise != "PSE"){
                                $order->update_status($orderStatus);
                                $order->add_order_note($message);
                            }
                        } break;
                        case 4: {
                            if($isTestMode=="true"){
                                if(
                                    $current_state == "epayco_processing" ||
                                    $current_state == "epayco_completed" ||
                                    $current_state == "processing_test" ||
                                    $current_state == "completed_test"
                                ){}else{
                                    $message = 'Pago rechazado Prueba: ' .$x_ref_payco;
                                    $messageClass = 'woocommerce-error';
                                    $order->update_status('epayco_failed');
                                    $order->add_order_note($message);
                                    if($current_state =="epayco-failed"||
                                    $current_state == "epayco_failed" ){
                                       }else{
                                         $this->restore_order_stock($order->id);
                                    }
                                }
                            }else{
                                if(
                                    $current_state == "epayco-processing" ||
                                    $current_state == "epayco-completed" ||
                                    $current_state == "processing-test" ||
                                    $current_state == "completed-test"||
                                    $current_state == "processing" ||
                                    $current_state == "completed"
                                ){}else{
                                    $message = 'Pago rechazado: ' .$x_ref_payco;
                                    $messageClass = 'woocommerce-error';
                                    $order->update_status('epayco-failed');
                                    $order->add_order_note($message);
                                    if($current_state !="epayco-failed"){
                                        $this->restore_order_stock($order->id);
                                    }
                                }
                            }
                            echo "4";
                        } break;
                        case 6: {
                            $message = 'Pago Reversada' .$x_ref_payco;
                            $messageClass = 'woocommerce-error';
                            $order->update_status('refunded');
                            $order->add_order_note('Pago Reversado');
                            $this->restore_order_stock($order->id);
                        } break;
                        case 10:{
                            if($isTestMode=="true"){
                                if(
                                    $current_state == "epayco_processing" ||
                                    $current_state == "epayco_completed" ||
                                    $current_state == "processing_test" ||
                                    $current_state == "completed_test"
                                ){}else{
                                    $message = 'Pago rechazado Prueba: ' .$x_ref_payco;
                                    $messageClass = 'woocommerce-error';
                                    $order->update_status('epayco_cancelled');
                                    $order->add_order_note($message);
                                    if($current_state =="epayco-cancelled"||
                                    $current_state == "epayco_cancelled" ){
                                       }else{
                                         $this->restore_order_stock($order->id);
                                    }
                                }
                            }else{
                                if(
                                    $current_state == "epayco-processing" ||
                                    $current_state == "epayco-completed" ||
                                    $current_state == "processing-test" ||
                                    $current_state == "completed-test"||
                                    $current_state == "processing" ||
                                    $current_state == "completed"
                                ){}else{
                                    $message = 'Pago rechazado: ' .$x_ref_payco;
                                    $messageClass = 'woocommerce-error';
                                    $order->update_status('epayco-cancelled');
                                    $order->add_order_note($message);
                                    if($current_state !="epayco-cancelled"){
                                        $this->restore_order_stock($order->id);
                                    }
                                }
                            }
                            echo "10";
                        } break;
                        case 11:{
                            if($isTestMode=="true"){
                                if(
                                    $current_state == "epayco_processing" ||
                                    $current_state == "epayco_completed" ||
                                    $current_state == "processing_test" ||
                                    $current_state == "completed_test"
                                ){}else{
                                    $message = 'Pago rechazado Prueba: ' .$x_ref_payco;
                                    $messageClass = 'woocommerce-error';
                                    $order->update_status('epayco_cancelled');
                                    $order->add_order_note($message);
                                    if($current_state =="epayco-cancelled"||
                                    $current_state == "epayco_cancelled" ){
                                       }else{
                                         $this->restore_order_stock($order->id);
                                    }
                                }
                            }else{
                                if(
                                    $current_state == "epayco-processing" ||
                                    $current_state == "epayco_processing" ||
                                    $current_state == "epayco-completed" ||
                                    $current_state == "epayco_completed" ||
                                    $current_state == "processing-test" ||
                                    $current_state == "completed-test"||
                                    $current_state == "processing" ||
                                    $current_state == "completed"
                                ){}else{
                                    $message = 'Pago rechazado: ' .$x_ref_payco;
                                    $messageClass = 'woocommerce-error';
                                    $order->update_status('epayco-cancelled');
                                    $order->add_order_note($message);
                                    if($current_state !="epayco-cancelled"){
                                        $this->restore_order_stock($order->id);
                                    }
                                }
                            }
                            echo "11";
                        } break;
                        default: {
                            if(
                                $current_state == "epayco-processing" ||
                                $current_state == "epayco-completed" ||
                                $current_state == "processing" ||
                                $current_state == "completed"){
                            } else{
                                $message = 'Pago '.$_REQUEST['x_transaction_state'] . $x_ref_payco;
                                $messageClass = 'woocommerce-error';
                                $order->update_status('epayco-failed');
                                $order->add_order_note('Pago fallido o abandonado');
                                $this->restore_order_stock($order->id);
                            }
                        } break;
                    }

                    //validar si la transaccion esta pendiente y pasa a rechazada y ya habia descontado el stock
                    if($current_state == 'on-hold' && ((int)$x_cod_transaction_state == 2 || (int)$x_cod_transaction_state == 4) && EpaycoOrder::ifStockDiscount($order_id)){
                        //si no se restauro el stock restaurarlo inmediatamente
                        $this->restore_order_stock($order_id);
                    };

                } else {
                    if($isTestMode=="true"){
                        if($x_cod_transaction_state==1){
                            $message = 'Pago exitoso Prueba';
                            switch ($this->epayco_endorder_state ){
                                case 'epayco-processing':{
                                    $orderStatus ='epayco_processing';
                                }break;
                                case 'epayco-completed':{
                                    $orderStatus ='epayco_completed';
                                }break;
                                    case 'processing':{
                                    $orderStatus ='processing_test';
                                }break;
                                    case 'completed':{
                                    $orderStatus ='completed_test';
                                }break;
                            }
                        } 
                        if($isTestPluginMode == "no" && $x_cod_transaction_state == 1)
                        {
                            $this->restore_order_stock($order->id);
                        }
                    }else{  
                        $message = 'Firma no valida';
                        $orderStatus = 'epayco-failed';
                        if($x_cod_transaction_state!=1){
                            $this->restore_order_stock($order->id);
                        }
                    }
                        $order->update_status($orderStatus);
                        $order->add_order_note($message);
                        $messageClass = 'error';
                        echo $message;
                }
                
                 if (isset($_REQUEST['confirmation'])) {
                        $redirect_url = get_permalink($this->get_option('epayco_url_confirmation'));
                        if ($this->get_option('epayco_url_confirmation' ) == 0) {
                            echo $current_state;
                            die();
                        }
                    }else{
                        
                        if ($this->get_option('epayco_url_response' ) == 0) {
                            $redirect_url = $order->get_checkout_order_received_url();
                        } else {
                            $woocommerce->cart->empty_cart();
                            $redirect_url = get_permalink($this->get_option('epayco_url_response'));
                        }
                    }

               

                $arguments=array();

                foreach ($validationData as $key => $value) {
                    $arguments[$key]=$value;
                }

                unset($arguments["wc-api"]);
                $arguments['msg']=urlencode($message);
                $arguments['type']=$messageClass;
                $response_data = $this->response_data == "yes" ? true : false;

                if ($response_data) {
                    $redirect_url = add_query_arg($arguments , $redirect_url );
                }

                wp_redirect($redirect_url);
                die();
            }


            /**
             * @param $order_id
             */
            public function restore_order_stock($order_id,$operation = 'increase')
            {
                //$order = new WC_Order($order_id);
                $order = wc_get_order($order_id);
                if (!get_option('woocommerce_manage_stock') == 'yes' && !sizeof($order->get_items()) > 0) {
                    return;
                }
                
                foreach ($order->get_items() as $item) {
                    // Get an instance of corresponding the WC_Product object
                    $product = $item->get_product();
                    $qty = $item->get_quantity(); // Get the item quantity
                    wc_update_product_stock($product, $qty, $operation);
                }

            }


            public function string_sanitize($string, $force_lowercase = true, $anal = false) {

                $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]","}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;","â€”", "â€“", ",", "<", ".", ">", "/", "?");
                $clean = trim(str_replace($strip, "", strip_tags($string)));
                $clean = preg_replace('/\s+/', "_", $clean);
                $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
                return $clean;
            }


            public function getTaxesOrder($order){
                $taxes=($order->get_taxes());
                $tax=0;
                foreach($taxes as $tax){
                    $itemtax=$tax['item_meta']['tax_amount'][0];
                }
                return $itemtax;
            }

            public function enqueue_scripts()
            {
                wp_enqueue_script('gateway-epayco', plugin_dir_url(__FILE__).'lib/epayco.js', array(), $this->version, true );
                wp_enqueue_style('frontend-epayco',  plugin_dir_url(__FILE__).'lib/epayco.css', array(), $this->version, null);
            }
        }


        function is_product_in_cart( $prodids ){
            $product_in_cart = false;
            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                $product = $cart_item['data'];
                if ( in_array( $product->id, $prodids ) ) {
                    $product_in_cart = true;
                }

            }
            return $product_in_cart;
        }


        /**
         * @param $methods
         * @return array
         */
        function woocommerce_epayco_add_gateway($methods)
        {
            $methods[] = 'WC_ePayco';
            return $methods;
        }
        add_filter('woocommerce_payment_gateways', 'woocommerce_epayco_add_gateway');

        function epayco_woocommerce_addon_settings_link( $links ) {
            array_push( $links, '<a href="admin.php?page=wc-settings&tab=checkout&section=epayco">' . __( 'Configuración' ) . '</a>' );
            return $links;
        }

        add_filter( "plugin_action_links_".plugin_basename( __FILE__ ),'epayco_woocommerce_addon_settings_link' );
    }


    //Actualización de versión
    global $epayco_db_version;
    $epayco_db_version = '1.0';
    //Verificar si la version de la base de datos esta actualizada

    function epayco_update_db_check()
    {
        global $epayco_db_version;
        $installed_ver = get_option('epayco_db_version');
        if ($installed_ver == null || $installed_ver != $epayco_db_version) {
            EpaycoOrder::setup();
            update_option('epayco_db_version', $epayco_db_version);
        }
    }


    add_action('plugins_loaded', 'epayco_update_db_check');

    function register_epayco_order_status() {
        register_post_status( 'wc-epayco-failed', array(
            'label'                     => 'ePayco Pago Fallido',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'ePayco Pago Fallido <span class="count">(%s)</span>', 'ePayco Pago Fallido <span class="count">(%s)</span>' )
        ));

        register_post_status( 'wc-epayco_failed', array(
            'label'                     => 'ePayco Pago Fallido Prueba',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'ePayco Pago Fallido Prueba <span class="count">(%s)</span>', 'ePayco Pago Fallido Prueba <span class="count">(%s)</span>' )
        ));

        register_post_status( 'wc-epayco-cancelled', array(
            'label'                     => 'ePayco Pago Cancelado',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'ePayco Pago Cancelado <span class="count">(%s)</span>', 'ePayco Pago Cancelado <span class="count">(%s)</span>' )
        ));

        register_post_status( 'wc-epayco_cancelled', array(
            'label'                     => 'ePayco Pago Cancelado Prueba',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'ePayco Pago Cancelado Prueba <span class="count">(%s)</span>', 'ePayco Pago Cancelado Prueba <span class="count">(%s)</span>' )
        ));

        register_post_status( 'wc-epayco-on-hold', array(
            'label'                     => 'ePayco Pago Pendiente',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'ePayco Pago Pendiente <span class="count">(%s)</span>', 'ePayco Pago Pendiente <span class="count">(%s)</span>' )
        ));

        register_post_status( 'wc-epayco_on_hold', array(
            'label'                     => 'ePayco Pago Pendiente Prueba',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'ePayco Pago Pendiente Prueba <span class="count">(%s)</span>', 'ePayco Pago Pendiente Prueba <span class="count">(%s)</span>' )
        ));

        register_post_status( 'wc-epayco-processing', array(
            'label'                     => 'ePayco Procesando Pago',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'ePayco Procesando Pago <span class="count">(%s)</span>', 'ePayco Procesando Pago <span class="count">(%s)</span>' )
        ));

        register_post_status( 'wc-epayco_processing', array(
            'label'                     => 'ePayco Procesando Pago Prueba',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'ePayco Procesando Pago Prueba<span class="count">(%s)</span>', 'ePayco Procesando Pago Prueba<span class="count">(%s)</span>' )
        ));

        register_post_status( 'wc-processing', array(
            'label'                     => 'Procesando',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'Procesando<span class="count">(%s)</span>', 'Procesando<span class="count">(%s)</span>' )
        ));

        register_post_status( 'wc-processing_test', array(
            'label'                     => 'Procesando Prueba',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'Procesando Prueba<span class="count">(%s)</span>', 'Procesando Prueba<span class="count">(%s)</span>' )
        ));

        register_post_status( 'wc-epayco-completed', array(
            'label'                     => 'ePayco Pago Completado',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'ePayco Pago Completado <span class="count">(%s)</span>', 'ePayco Pago Completado <span class="count">(%s)</span>' )
        ));

        register_post_status( 'wc-epayco_completed', array(
            'label'                     => 'ePayco Pago Completado Prueba',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'ePayco Pago Completado Prueba <span class="count">(%s)</span>', 'ePayco Pago Completado Prueba <span class="count">(%s)</span>' )
        ));

        register_post_status( 'wc-completed', array(
            'label'                     => 'Completado',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'Completado<span class="count">(%s)</span>', 'Completado<span class="count">(%s)</span>' )
        ));

        register_post_status( 'wc-completed_test', array(
            'label'                     => 'Completado Prueba',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'Completado Prueba<span class="count">(%s)</span>', 'Completado Prueba<span class="count">(%s)</span>' )
        ));
    }

    add_action( 'plugins_loaded', 'register_epayco_order_status' );

    function add_epayco_to_order_statuses( $order_statuses ) {
        $new_order_statuses = array();
        $epayco_order = get_option('epayco_order_status');
        $testMode = $epayco_order == "yes" ? "true" : "false";
        foreach ( $order_statuses as $key => $status ) {
            $new_order_statuses[ $key ] = $status;
            if ( 'wc-cancelled' === $key ) {
                if($testMode=="true"){
                    $new_order_statuses['wc-epayco_cancelled'] = 'ePayco Pago Cancelado Prueba';
                }else{
                    $new_order_statuses['wc-epayco-cancelled'] = 'ePayco Pago Cancelado';
                }
            }

            if ( 'wc-failed' === $key ) {
                if($testMode=="true"){
                    $new_order_statuses['wc-epayco_failed'] = 'ePayco Pago Fallido Prueba';
                }else{
                    $new_order_statuses['wc-epayco-failed'] = 'ePayco Pago Fallido';
                }
            }

            if ( 'wc-on-hold' === $key ) {
                if($testMode=="true"){
                    $new_order_statuses['wc-epayco_on_hold'] = 'ePayco Pago Pendiente Prueba';
                }else{
                    $new_order_statuses['wc-epayco-on-hold'] = 'ePayco Pago Pendiente';
                }
            }

            if ( 'wc-processing' === $key ) {
                if($testMode=="true"){
                    $new_order_statuses['wc-epayco_processing'] = 'ePayco Pago Procesando Prueba';
                }else{
                    $new_order_statuses['wc-epayco-processing'] = 'ePayco Pago Procesando';
                }
            }else {
                if($testMode=="true"){
                    $new_order_statuses['wc-processing_test'] = 'Procesando Prueba';
                }else{
                    $new_order_statuses['wc-processing'] = 'Procesando';
                }
            }

            if ( 'wc-completed' === $key ) {
                if($testMode=="true"){
                    $new_order_statuses['wc-epayco_completed'] = 'ePayco Pago Completado Prueba';
                }else{
                    $new_order_statuses['wc-epayco-completed'] = 'ePayco Pago Completado';
                }
            }else{
                if($testMode=="true"){
                    $new_order_statuses['wc-completed_test'] = 'Completado Prueba';
                }else{
                    $new_order_statuses['wc-completed'] = 'Completado';
                }
            }
        }
        return $new_order_statuses;
    }

    add_filter( 'wc_order_statuses', 'add_epayco_to_order_statuses' );
    add_action('admin_head', 'styling_admin_order_list' );
    function styling_admin_order_list() {
        global $pagenow, $post;
        if( $pagenow != 'edit.php') return; // Exit
        if( get_post_type($post->ID) != 'shop_order' ) return; // Exit
        // HERE we set your custom status
        $epayco_order = get_option('epayco_order_status');
        $testMode = $epayco_order == "yes" ? "true" : "false";
        if($testMode=="true"){
            $order_status_failed = 'epayco_failed';
            $order_status_on_hold = 'epayco_on_hold';
            $order_status_processing = 'epayco_processing';
            $order_status_processing_ = 'processing_test';
            $order_status_completed = 'epayco_completed';
            $order_status_cancelled = 'epayco_cancelled';
            $order_status_completed_ = 'completed_test';

        }else{
            $order_status_failed = 'epayco-failed';
            $order_status_on_hold = 'epayco-on-hold';
            $order_status_processing = 'epayco-processing';
            $order_status_processing_ = 'processing';
            $order_status_completed = 'epayco-completed';
            $order_status_cancelled = 'epayco-cancelled';
            $order_status_completed_ = 'completed';
        }
        ?>

<style>
                .order-status.status-<?php echo sanitize_title( $order_status_failed); ?> {
                    background: #eba3a3;
                    color: #761919;
                }
                .order-status.status-<?php echo sanitize_title( $order_status_on_hold); ?> {
                    background: #f8dda7;
                    color: #94660c;
                }
                .order-status.status-<?php echo sanitize_title( $order_status_processing ); ?> {
                    background: #c8d7e1;
                    color: #2e4453;
                }
                .order-status.status-<?php echo sanitize_title( $order_status_processing_ ); ?> {
                    background: #c8d7e1;
                    color: #2e4453;
                }
                .order-status.status-<?php echo sanitize_title( $order_status_completed ); ?> {
                    background: #d7f8a7;
                    color: #0c942b;
                }
                .order-status.status-<?php echo sanitize_title( $order_status_completed_ ); ?> {
                    background: #d7f8a7;
                    color: #0c942b;
                }
                .order-status.status-<?php echo sanitize_title( $order_status_cancelled); ?> {
                    background: #eba3a3;
                    color: #761919;
                }
            </style>

        <?php
    }

}