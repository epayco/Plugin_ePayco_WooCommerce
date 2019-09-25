<?php
/**
 * @since             1.0.0
 * @package           ePayco_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       ePayco WooCommerce
 * Description:       Plugin ePayco WooCommerce.
 * Version:           3.7.0
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

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    add_action('plugins_loaded', 'init_epayco_woocommerce', 0);

    function init_epayco_woocommerce()
    {

        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        class WC_ePayco extends WC_Payment_Gateway
        {
            public function __construct()
            {
                $this->id = 'epayco';
                $this->icon = 'https://369969691f476073508a-60bf0867add971908d4f26a64519c2aa.ssl.cf5.rackcdn.com/logos/logo_epayco_200px.png';
                $this->method_title = __('ePayco Checkout', 'epayco_woocommerce');
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


                add_filter('woocommerce_thankyou_order_received_text', array(&$this, 'order_received_message'), 10, 2 );
                add_action('ePayco_init', array( $this, 'ePayco_successful_request'));
                add_action('woocommerce_receipt_' . $this->id, array(&$this, 'receipt_page'));
                add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_ePayco_response' ) );
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                add_action('wp_ajax_nopriv_returndata',array($this,'datareturnepayco_ajax'));
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
                        <img  src="https://369969691f476073508a-60bf0867add971908d4f26a64519c2aa.ssl.cf5.rackcdn.com/logos/logo_epayco_200px.png">
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
                        'label' => __('Seleccione un tipo de Checkout:', 'epayco_woocommerce'),
                        'description' => __('(Onpage Checkout, el usuario al pagar permanece en el sitio) ó (Standart Checkout, el usario al pagar es redireccionado a la pasarela de ePayco)', 'epayco_woocommerce'),
                        'options' => array('false'=>"Onpage Checkout","true"=>"Standart Checkout"),
                    ),

                    'epayco_endorder_state' => array(
                        'title' => __('Estado Final del Pedido', 'epayco_woocommerce'),
                        'type' => 'select',
                        'description' => __('Seleccione el estado del pedido que se aplicará a la hora de aceptar y confirmar el pago de la orden', 'epayco_woocommerce'),
                        'options' => array('epayco-processing'=>"ePayco Procesando Pago","epayco-completed"=>"ePayco Pago Completado"),
                    ),

                    'epayco_url_response' => array(
                        'title' => __('Página de Respuesta', 'epayco_woocommerce'),
                        'type' => 'select',
                        'description' => __('Url de la tienda donde se redirecciona al usuario luego de pagar el pedido', 'epayco_woocommerce'),
                        'options'       => $this->get_pages(__('Seleccionar pagina', 'payco-woocommerce')),
                    ),

                    'epayco_url_confirmation' => array(
                        'title' => __('Página de Confirmación', 'epayco_woocommerce'),
                        'type' => 'select',
                        'description' => __('Url de la tienda donde ePayco confirma el pago', 'epayco_woocommerce'),
                        'options'       => $this->get_pages(__('Seleccionar pagina', 'payco-woocommerce')),
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
                        'description' => __('Seleccione el idioma del checkout', 'epayco_woocommerce'),
                        'options' => array('es'=>"Español","en"=>"Inglés"),
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
                    $descripcionParts[] = $this->string_sanitize($product['name']);
                }

                $descripcion = implode(' - ', $descripcionParts);
                $currency = get_woocommerce_currency();
                $testMode = $this->epayco_testmode == "yes" ? "true" : "false";
                $basedCountry = WC()->countries->get_base_country();
                $external=$this->epayco_type_checkout;
                

                $redirect_url =get_site_url() . "/";
                $confirm_url=get_site_url() . "/";
              
                $redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
                $redirect_url = add_query_arg( 'order_id', $order_id, $redirect_url );

                $confirm_url = add_query_arg( 'wc-api', get_class( $this ), $confirm_url );
                $confirm_url = add_query_arg( 'order_id', $order_id, $confirm_url );
                $confirm_url = $redirect_url.'&confirmation=1';
                //$confirm_url = add_query_arg( 'confirmation', 1 );

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
                    $base_tax=0;
                    $tax=0;
                }

                //Busca si ya se restauro el stock
                if (!EpaycoOrder::ifExist($order_id)) {
                    //si no se restauro el stock restaurarlo inmediatamente
                    $this->restore_order_stock($order_id);
                    EpaycoOrder::create($order_id,1);
                }
                $msgEpaycoCheckout = '<span class="animated-points">Cargando metodos de pago</span>
                           <br><small class="epayco-subtitle"> Si no se cargan automáticamente, de clic en el botón "Pagar con ePayco</small>';
               $epaycoButtonImage = 'https://369969691f476073508a-60bf0867add971908d4f26a64519c2aa.ssl.cf5.rackcdn.com/btns/epayco/boton_de_cobro_epayco6.png';
                if ($this->epayco_lang !== "es") {
                    $msgEpaycoCheckout = '<span class="animated-points">Loading payment methods</span>
                               <br><small class="epayco-subtitle"> If they do not load automatically, click on the "Pay with ePayco" button</small>';
                    $epaycoButtonImage = 'https://369969691f476073508a-60bf0867add971908d4f26a64519c2aa.ssl.cf5.rackcdn.com/btns/btn7.png';
                }
                echo('
                    <style>
                        .epayco-title{
                            max-width: 900px;
                            display: block;
                            margin:auto;
                            color: #444;
                            font-weight: 700;
                            margin-bottom: 25px;
                        }

                        .loader-container{
                            position: relative;
                            padding: 20px;
                            color: #ff5700;
                        }

                        .epayco-subtitle{
                            font-size: 14px;
                        }
                        .epayco-button-render{
                            transition: all 500ms cubic-bezier(0.000, 0.445, 0.150, 1.025);
                            transform: scale(1.1);
                            box-shadow: 0 0 4px rgba(0,0,0,0);
                        }
                        .epayco-button-render:hover {
                            /*box-shadow: 0 0 4px rgba(0,0,0,.5);*/
                            transform: scale(1.2);
                        }

                        .animated-points::after{
                            content: "";
                            animation-duration: 2s;
                            animation-fill-mode: forwards;
                            animation-iteration-count: infinite;
                            animation-name: animatedPoints;
                            animation-timing-function: linear;
                            position: absolute;
                        }

                        .animated-background {
                            animation-duration: 2s;
                            animation-fill-mode: forwards;
                            animation-iteration-count: infinite;
                            animation-name: placeHolderShimmer;
                            animation-timing-function: linear;
                            color: #f6f7f8;
                            background: linear-gradient(to right, #7b7b7b 8%, #999 18%, #7b7b7b 33%);
                            background-size: 800px 104px;
                            position: relative;
                            background-clip: text;
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        }
                        
                        .loading::before{
                            -webkit-background-clip: padding-box;
                            background-clip: padding-box;
                            box-sizing: border-box;
                            border-width: 2px;
                            border-color: currentColor currentColor currentColor transparent;
                            position: absolute;
                            margin: auto;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            content: " ";
                            display: inline-block;
                            background: center center no-repeat;
                            background-size: cover;
                            border-radius: 50%;
                            border-style: solid;
                            width: 30px;
                            height: 30px;
                            opacity: 1;
                            -webkit-animation: loaderAnimation 1s infinite linear,fadeIn 0.5s ease-in-out;
                            -moz-animation: loaderAnimation 1s infinite linear, fadeIn 0.5s ease-in-out;
                            animation: loaderAnimation 1s infinite linear, fadeIn 0.5s ease-in-out;
                        }

                        @keyframes animatedPoints{
                            33%{
                                content: "."
                            }
                            66%{
                                content: ".."
                            }
                            100%{
                                content: "..."
                            }

                        }
                        @keyframes placeHolderShimmer{
                            0%{
                                background-position: -800px 0
                            }
                            100%{
                                background-position: 800px 0
                            }
                        }
                        @keyframes loaderAnimation{
                            0%{
                                -webkit-transform:rotate(0);
                                transform:rotate(0);
                                animation-timing-function:cubic-bezier(.55,.055,.675,.19)
                            }
                            50%{
                                -webkit-transform:rotate(180deg);
                                transform:rotate(180deg);
                                animation-timing-function:cubic-bezier(.215,.61,.355,1)
                            }
                            100%{
                                -webkit-transform:rotate(360deg);
                                transform:rotate(360deg)
                            }
                        }
                    </style>

                    ');
                echo sprintf('
                        <div class="loader-container">
                            <div class="loading"></div>
                        </div>
                        <p style="text-align: center;" class="epayco-title">'.$msgEpaycoCheckout
                            .'
                        </p>                        
                        <form id="epayco_form" style="text-align: center;">
                            <script src="https://checkout.epayco.co/checkout.js"
                            class="epayco-button"
                            data-epayco-key="%s"
                            data-epayco-amount="%s"
                            data-epayco-tax="%s"
                            data-epayco-tax-base="%s"    
                            data-epayco-name="%s"
                            data-epayco-description="%s"
                            data-epayco-currency="%s"
                            data-epayco-invoice="%s"
                            data-epayco-country="%s"
                            data-epayco-test="%s"
                            data-epayco-external="%s"
                            data-epayco-response="%s" 
                            data-epayco-confirmation="%s"
                            data-epayco-email-billing="%s"
                            data-epayco-name-billing="%s"
                            data-epayco-address-billing="%s"
                            data-epayco-lang="%s"
                            data-epayco-mobilephone-billing="%s"
                            data-epayco-button="'.$epaycoButtonImage.'"
                            data-epayco-autoClick="true"
                            >
                        </script>
                    </form>
                ',$this->epayco_publickey, $order->get_total(),$tax,$base_tax, $descripcion, $descripcion, $currency, $order->get_id(), $basedCountry, $testMode, $external, $redirect_url,$confirm_url,
                    $email_billing,$name_billing,$address_billing,$this->epayco_lang,$phone_billing);
                   
                    $messageload = __('Espere por favor..Cargando checkout.','payco-woocommerce');
                    $js = "if(jQuery('button.epayco-button-render').length)    
                {
                jQuery('button.epayco-button-render').css('margin','auto');
                jQuery('button.epayco-button-render').css('display','block');
                }
                ";

                if (version_compare(WOOCOMMERCE_VERSION, '2.1', '>=')){
                    wc_enqueue_js($js);
                }else{
                    $woocommerce->add_inline_js($js);
                }
            }

            public function datareturnepayco_ajax()
            {
                die();
            }
            public function block($message)
            {
                return 'jQuery("body").block({
                        message: "' . esc_js($message) . '",
                        baseZ: 99999,
                        overlayCSS:
                        {
                            background: "#000",
                            opacity: "0.6",
                        },
                        css: {
                            padding:        "20px",
                            zindex:         "9999999",
                            textAlign:      "center",
                            color:          "#555",
                            border:         "1px solid #aaa",
                            backgroundColor:"#fff",
                            cursor:         "wait",
                            lineHeight:     "24px",
                        }
                    });';
            }

            public function agafa_dades($url) {
                if (function_exists('curl_init')) {
                    $ch = curl_init();
                    $timeout = 5;
                    $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
                    curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
                    curl_setopt($ch,CURLOPT_MAXREDIRS,10);
                    $data = curl_exec($ch);
                    curl_close($ch);
                    return $data;
                }else{
                    $data =  @file_get_contents($url);
                    return $data;
                }
            }
            public function goter(){
                $context = stream_context_create(array(
                    'http' => array(
                        'method' => 'POST',
                        'header' => 'Content-Type: application/x-www-form-urlencoded',
                        'protocol_version' => 1.1,
                        'timeout' => 10,
                        'ignore_errors' => true
                    )
                ));
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
                    $order_id="";
                    $ref_payco="";
                    $signature="";

                    if(isset($_REQUEST['x_signature'])){
                        $explode=explode('?',$_GET['order_id']);
                        $order_id=$explode[0];
                        //$order_id=$_REQUEST['order_id'];
                        $ref_payco=$_REQUEST['x_ref_payco'];
                    }else{
                         //Viene por el onpage
                        $explode=explode('?',$_GET['order_id']);
                        $order_id=$explode[0];
                       
                            $strref_payco=explode("=",$explode[1]);
                            $ref_payco=$_REQUEST['ref_payco'];

                            //Consultamos los datos
                            $message = __('Esperando respuesta por parte del servidor.','payco-woocommerce');
                            $js = $this->block($message);
                            $url = 'https://secure.epayco.co/validation/v1/reference/'.$ref_payco;
                            $responseData = $this->agafa_dades($url,false,$this->goter());
                            $jsonData = @json_decode($responseData, true);
                            $validationData = $jsonData['data'];
                           $ref_payco = $validationData['x_ref_payco'];
                        
                    }
                    //Validamos la firma
                    if ($order_id!="" && $ref_payco!="") {
                        $order = new WC_Order($order_id);
                        $signature = hash('sha256',
                            $this->epayco_customerid.'^'
                            .$this->epayco_secretkey.'^'
                            .$validationData['x_ref_payco'].'^'
                            .$validationData['x_transaction_id'].'^'
                            .$validationData['x_amount'].'^'
                            .$validationData['x_currency_code']
                        );
                    }
                    
                    $message = '';
                    $messageClass = '';
                    $current_state = $order->get_status();

                    if($signature == $validationData['x_signature']){
                        
                        switch ((int)$validationData['x_cod_response']) {
                            case 1:{
                                //Busca si ya se descontó el stock
                                if (!EpaycoOrder::ifStockDiscount($order_id)) {
                                    
                                    //se descuenta el stock
                                    if (EpaycoOrder::updateStockDiscount($order_id,1)) {
                                        $this->restore_order_stock($order_id,'decrease');
                                    }
                                }
                                $message = 'Pago exitoso';
                                $messageClass = 'woocommerce-message';
                                $order->payment_complete($validationData['x_ref_payco']);
                                $order->update_status($this->epayco_endorder_state);
                                $order->add_order_note('Pago exitoso');
                                
                            }break;
                            case 2: {
                                $message = 'Pago rechazado';
                                $messageClass = 'woocommerce-error';
                                $order->update_status('epayco-failed');
                                $order->add_order_note('Pago fallido');
                                //$this->restore_order_stock($order->id);
                            }break;
                            case 3:{
                                //Busca si ya se restauro el stock y si se configuro reducir el stock en transacciones pendientes  
                                if (!EpaycoOrder::ifStockDiscount($order_id) && $this->get_option('epayco_reduce_stock_pending') == 'yes') {

                                    //reducir el stock
                                    if (EpaycoOrder::updateStockDiscount($order_id,1)) {
                                        $this->restore_order_stock($order_id,'decrease');
                                    }
                                }

                                $message = 'Pago pendiente de aprobación';
                                $messageClass = 'woocommerce-info';
                                $order->update_status('epayco-on-hold');
                                $order->add_order_note('Pago pendiente');
                            }break;
                            case 4:{
                                $message = 'Pago fallido';
                                $messageClass = 'woocommerce-error';
                                $order->update_status('epayco-failed');
                                $order->add_order_note('Pago fallido');
                                //$this->restore_order_stock($order->id);
                            }break;
                            default:{
                                $message = 'Pago '.$_REQUEST['x_transaction_state'];
                                $messageClass = 'woocommerce-error';
                                $order->update_status('epayco-failed');
                                $order->add_order_note($message);
                               // $this->restore_order_stock($order->id);
                            }break;

                        }
                    //validar si la transaccion esta pendiente y pasa a rechazada y ya habia descontado el stock
                    if($current_state == 'on-hold' && ((int)$validationData['x_cod_response'] == 2 || (int)$validationData['x_cod_response'] == 4) && EpaycoOrder::ifStockDiscount($order_id)){

                        //si no se restauro el stock restaurarlo inmediatamente
                         $this->restore_order_stock($order_id);
                    };
                    }else {
                        $message = 'Firma no valida';
                        $messageClass = 'error';
                        $order->update_status('failed');
                        $order->add_order_note('Failed');
                        //$this->restore_order_stock($order_id);
                    }

                    
                    if (isset($_REQUEST['confirmation'])) {
                        $redirect_url = get_permalink($this->get_option('epayco_url_confirmation'));

                        if ($this->get_option('epayco_url_confirmation' ) == 0) {
                            echo "ok";
                            die();
                        }
                    }else{

                        if ($this->get_option('epayco_url_response' ) == 0) {
                            $redirect_url = $order->get_checkout_order_received_url();
                        }else{
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
                    $redirect_url = add_query_arg($arguments , $redirect_url );

                    wp_redirect($redirect_url);
                    die();
            }

            /**
             * @param $order_id
             */
            public function restore_order_stock($order_id,$operation = 'increase')
            {
                //$order = new WC_Order($order_id);
                $order =  $order = wc_get_order($order_id);
                if (!get_option('woocommerce_manage_stock') == 'yes' && !sizeof($order->get_items()) > 0) {
                    return;
                }

                foreach ($order->get_items() as $item) {
                    // Get an instance of corresponding the WC_Product object
                    $product = $item->get_product();
                    $qty = $item->get_quantity(); // Get the item quantity
                    wc_update_product_stock($product, $qty, $operation);
                }
               /* foreach ($order->get_items() as $item) {
                    if ($item['product_id'] > 0) {
                        $_product = $order->get_product_from_item($item);
                        if ($_product && $_product->exists() && $_product->managing_stock()) {
                            $old_stock = $_product->stock;
                            $qty = apply_filters('woocommerce_order_item_quantity', $item['qty'], $this, $item);
                            $new_quantity = $_product->increase_stock($qty);
                            do_action('woocommerce_auto_stock_restored', $_product, $item);
                            $order->add_order_note(sprintf(__('Item #%s stock incremented from %s to %s.', 'woocommerce'), $item['product_id'], $old_stock, $new_quantity));
                            $order->send_stock_notifications($_product, $new_quantity, $item['qty']);
                        }




                    }
                }*/
            }

            public function string_sanitize($string, $force_lowercase = true, $anal = false) {

                $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
                               "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
                               "â€”", "â€“", ",", "<", ".", ">", "/", "?");
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
                    //var_dump($itemtax);
                }
                return $itemtax;
            }

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
        register_post_status( 'wc-epayco-canceled', array(
            'label'                     => 'ePayco Pago Cancelado',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'ePayco Pago Cancelado <span class="count">(%s)</span>', 'ePayco Pago Cancelado <span class="count">(%s)</span>' )
        ));
        register_post_status( 'wc-epayco-on-hold', array(
            'label'                     => 'ePayco Pago Pendiente',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'ePayco Pago Pendiente <span class="count">(%s)</span>', 'ePayco Pago Pendiente <span class="count">(%s)</span>' )
        ));
        register_post_status( 'wc-epayco-processing', array(
            'label'                     => 'ePayco Procesando Pago',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'ePayco Procesando Pago <span class="count">(%s)</span>', 'ePayco Procesando Pago <span class="count">(%s)</span>' )
        ));
        register_post_status( 'wc-epayco-completed', array(
            'label'                     => 'ePayco Pago Completado',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'ePayco Pago Completado <span class="count">(%s)</span>', 'ePayco Pago Completado <span class="count">(%s)</span>' )
        ));
    }
    add_action( 'plugins_loaded', 'register_epayco_order_status' );

    function add_epayco_to_order_statuses( $order_statuses ) {

        $new_order_statuses = array();

        foreach ( $order_statuses as $key => $status ) {

            $new_order_statuses[ $key ] = $status;
            if ( 'wc-cancelled' === $key ) {
                $new_order_statuses['wc-epayco-cancelled'] = 'ePayco Pago Cancelado';
            }
            if ( 'wc-failed' === $key ) {
                $new_order_statuses['wc-epayco-failed'] = 'ePayco Pago Fallido';
            }
            if ( 'wc-on-hold' === $key ) {
                $new_order_statuses['wc-epayco-on-hold'] = 'ePayco Pago Pendiente';
            }
            if ( 'wc-processing' === $key ) {
                $new_order_statuses['wc-epayco-processing'] = 'ePayco Procesando Pago';
            }
            if ( 'wc-completed' === $key ) {
                $new_order_statuses['wc-epayco-completed'] = 'ePayco Pago Completado';
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
        $order_status_failed = 'epayco-failed';
        $order_status_on_hold = 'epayco-on-hold';
        $order_status_processing = 'epayco-processing';
        $order_status_completed = 'epayco-completed';
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
            .order-status.status-<?php echo sanitize_title( $order_status_completed ); ?> {
                background: #d7f8a7;
                color: #0c942b;
            }
        </style>
        <?php
    }
}