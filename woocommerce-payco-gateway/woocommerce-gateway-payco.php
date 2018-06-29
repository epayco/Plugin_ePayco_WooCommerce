<?php
/**
 * @since             1.0.0
 * @package           ePayco_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       ePayco WooCommerce
 * Description:       Plugin ePayco WooCommerce.
 * Version:           1.0.0
 * Author:            ePayco
 * Author URI:        http://epayco.co
 *Lice
 * Text Domain:       epayco-woocommerce
 * Domain Path:       /languages
 */

if (!defined('WPINC')) {
    die;
}

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
                $this->icon = plugins_url('assets/images/epayco.png', __FILE__);
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
                $this->epayco_description = $this->get_option('epayco_description');
                $this->epayco_testmode = $this->get_option('epayco_testmode');
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
                <h3><?php _e('ePayco', 'epayco_woocommerce'); ?></h3>
                <table class="form-table">
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
                        'title' => __('Título', 'epayco_woocommerce'),
                        'type' => 'text',
                        'description' => __('Corresponde al titulo que el usuario ve durante el checkout.', 'epayco_woocommerce'),
                        'default' => __('ePayco', 'epayco_woocommerce'),
                        'desc_tip' => true,
                    ),

                    'epayco_description' => array(
                        'title' => __('Descripción', 'epayco_woocommerce'),
                        'type' => 'textarea',
                        'description' => __('Corresponde a la descripción que verá el usuaro durante el checkout', 'epayco_woocommerce'),
                        'default' => __('El onPage Checkout de ePayco, simplifica y asegura el procesamiento de pagos en línea', 'epayco_woocommerce'),
                        'desc_tip' => true,
                    ),

                    'epayco_customerid' => array(
                        'title' => __('P_CUST_ID_CLIENTE', 'epayco_woocommerce'),
                        'type' => 'text',
                        'description' => __('ID de cliente que lo representa en la plataforma. es Proporcionado en su panel de clientes en la opción configuración..', 'epayco_woocommerce'),
                        'default' => '',
                        'desc_tip' => true,
                        'placeholder' => ''
                    ),

                    'epayco_secretkey' => array(
                        'title' => __('P_KEY', 'epayco_woocommerce'),
                        'type' => 'text',
                        'description' => __('Corresponde a la llave transacción de su cuenta, Proporcionado en su panel de clientes en la opción configuración.', 'epayco_woocommerce'),
                        'default' => '',
                        'desc_tip' => true,
                        'placeholder' => ''
                    ),

                    'epayco_publickey' => array(
                        'title' => __('PUBLIC_KEY', 'epayco_woocommerce'),
                        'type' => 'text',
                        'description' => __('Corresponde a la llave de autenticación en el API Rest, Proporcionado en su panel de clientes en la opción configuración.', 'epayco_woocommerce'),
                        'default' => '',
                        'desc_tip' => true,
                        'placeholder' => ''
                    ),

                    'epayco_testmode' => array(
                        'title' => __('Sitio en pruebas', 'epayco_woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Habilitar el modo de pruebas', 'epayco_woocommerce'),
                        'description' => __('Habilite para realizar pruebas', 'epayco_woocommerce'),
                        'desc_tip' => true,
                        'default' => 'no',
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
                return array
                (
                    'result' => 'success',
                    'redirect' => add_query_arg('order-pay', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
                );
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
                    $descripcionParts[] = $product['name'];
                }

                $descripcion = implode(' - ', $descripcionParts);
                $currency = get_woocommerce_currency();
                $testMode = $this->epayco_testmode == "yes" ? "true" : "false";
                $responseURL = add_query_arg('order-pay', $_GET['order-pay'], add_query_arg('key', $_GET['key'], get_permalink(get_option('woocommerce_pay_page_id'))));
                $responseURL .= "&";
                $basedCountry = WC()->countries->get_base_country();

                $redirect_url = get_site_url() . "/";
                $redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
                $redirect_url = add_query_arg( 'order_id', $order_id, $redirect_url );
                $redirect_url = add_query_arg( '', $this->endpoint, $redirect_url );
                $order = new WC_Order($order_id);
                 
                if (isset($_GET['?ref_payco'])) {
                   
                    $message = __('Esperando respuesta por parte del servidor.','payco-woocommerce');
                    $js = $this->block($message);
                    $url = 'https://api.secure.payco.co/validation/v1/reference/'.$_GET['?ref_payco'];
                    $responseData = $this->agafa_dades($url,false,$this->goter());
                    $jsonData = @json_decode($responseData, true);
                    $validationData = $jsonData['data'];
                    $signature = hash('sha256',
                        $this->epayco_customerid.'^'
                        .$this->epayco_secretkey.'^'
                        .$validationData['x_ref_payco'].'^'
                        .$validationData['x_transaction_id'].'^'
                        .$validationData['x_amount'].'^'
                        .$validationData['x_currency_code']
                    );

                    $message = '';
                    $messageClass = '';
                    if($signature == $validationData['x_signature']){
                        switch ((int)$validationData['x_cod_response']) {
                            case 1:{
                                $message = 'Pago exitoso';
                                $messageClass = 'woocommerce-message';
                                $order->payment_complete($validationData['x_ref_payco']);
                                $order->add_order_note('Pago exitoso');
                            }break;
                            case 2: {
                                $message = 'Pago rechazado';
                                $messageClass = 'woocommerce-error';
                                $order->update_status('failed');
                                $order->add_order_note('Pago fallido');
                                $this->restore_order_stock($order->id);
                            }break;
                            case 3:{
                                $message = 'Pago pendiente de aprobación';
                                $messageClass = 'woocommerce-info';
                                $order->update_status('on-hold');
                                $order->add_order_note('Pago pendiente');
                            }break;
                            case 4:{
                                $message = 'Pago fallido';
                                $messageClass = 'woocommerce-error';
                                $order->update_status('failed');
                                $order->add_order_note('Pago fallido');
                                $this->restore_order_stock($order->id);
                            }break;
                        }
                    }else {
                        $message = 'Firma no valida';
                        $messageClass = 'error';
                        $order->update_status('failed');
                        $order->add_order_note('Failed');
                    }
                    $redirect_url = add_query_arg( array('msg'=> urlencode($message), 'type'=> $messageClass), $order->get_checkout_order_received_url() );
                    wp_redirect( $redirect_url );
                }else{
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
                            color: #f0943e;
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
                        <p style="text-align: center;" class="epayco-title">
                            <span class="animated-points">Cargando metodos de pago</span>
                           <br><small class="epayco-subtitle"> Si no se cargan automáticamente, de clic en el botón "Pagar con ePayco"</small>
                        </p>                        
                        <form id="epayco_form" style="text-align: center;">
                            <script src="https://checkout.epayco.co/checkout.js"
                            class="epayco-button"
                            data-epayco-key="%s"
                            data-epayco-amount="%s"
                            data-epayco-name="%s"
                            data-epayco-description="%s"
                            data-epayco-currency="%s"
                            data-epayco-invoice="%s"
                            data-epayco-country="%s"
                            data-epayco-test="%s"
                            data-epayco-response="%s" 
                            data-epayco-confirmation="%s"
                            data-epayco-button="https://369969691f476073508a-60bf0867add971908d4f26a64519c2aa.ssl.cf5.rackcdn.com/btns/btn4.png"
                            data-epayco-autoClick="true"
                            >

                        </script>
                    </form>
                ', $this->epayco_publickey, $order->get_total(), $descripcion, $descripcion, $currency, $order->id, $basedCountry, $testMode, $redirect_url,$redirect_url);
                    echo '<a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . ' Cancelar orden ' . '</a>';
                    $messageload = __('Espere por favor..Cargando checkout.','payco-woocommerce');
                    $js = "if(jQuery('button.epayco-button-render').length)    
                {
                jQuery('button.epayco-button-render').css('margin','auto');
                jQuery('button.epayco-button-render').css('display','block');
                ";
                }
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

                    if(isset($_REQUEST['x_signature'])){
                        $order_id=$_REQUEST['order_id'];
                        $ref_payco=['x_ref_payco'];
                    }else{
                         //Viene por el onpage
                        $explode=explode('?',$_GET['order_id']);

                        if(count($explode)>=2){
                            $order_id=$explode[0];
                            $strref_payco=explode("=",$explode[1]);
                            $ref_payco=$strref_payco[1];
                            //Consultamos los datos
                            $message = __('Esperando respuesta por parte del servidor.','payco-woocommerce');
                            $js = $this->block($message);
                            $url = 'https://api.secure.payco.co/validation/v1/reference/'.$ref_payco;
                            $responseData = $this->agafa_dades($url,false,$this->goter());
                            $jsonData = @json_decode($responseData, true);
                            $validationData = $jsonData['data'];
                        }

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

                    if($signature == $validationData['x_signature']){
                        
                     
                        switch ((int)$validationData['x_cod_response']) {
                            case 1:{
                                $message = 'Pago exitoso';
                                $messageClass = 'woocommerce-message';
                                $order->payment_complete($validationData['x_ref_payco']);
                                $order->update_status('completed');
                                $order->add_order_note('Pago exitoso');
                                
                            }break;
                            case 2: {
                                $message = 'Pago rechazado';
                                $messageClass = 'woocommerce-error';
                                $order->update_status('failed');
                                $order->add_order_note('Pago fallido');
                                $this->restore_order_stock($order->id);
                            }break;
                            case 3:{
                                $message = 'Pago pendiente de aprobación';
                                $messageClass = 'woocommerce-info';
                                $order->update_status('on-hold');
                                $order->add_order_note('Pago pendiente');
                            }break;
                            case 4:{
                                $message = 'Pago fallido';
                                $messageClass = 'woocommerce-error';
                                $order->update_status('failed');
                                $order->add_order_note('Pago fallido');
                                $this->restore_order_stock($order->id);
                            }break;
                        }
                    }else {
                        $message = 'Firma no valida';
                        $messageClass = 'error';
                        $order->update_status('failed');
                        $order->add_order_note('Failed');
                    }

                    $redirect_url = add_query_arg( array('msg'=> urlencode($message), 'type'=> $messageClass), $order->get_checkout_order_received_url() );
                    wp_redirect( $redirect_url );
                    exit();
            }

            /**
             * @param $order_id
             */
            public function restore_order_stock($order_id)
            {
                $order = new WC_Order($order_id);
                if (!get_option('woocommerce_manage_stock') == 'yes' && !sizeof($order->get_items()) > 0) {
                    return;
                }
                foreach ($order->get_items() as $item) {
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
                }
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
}