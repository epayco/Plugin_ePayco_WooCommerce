<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Gateway_Epayco extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id                   = 'epayco';
		$this->version = '8.1.2';
        $this->icon = apply_filters( 'woocommerce_' . $this->id . '_icon', EPAYCO_PLUGIN_URL . 'assets/images/paymentLogo.svg' );
        $this->method_title         = __( 'ePayco Checkout Gateway', 'woo-epayco-gateway' );
        $this->method_description   = __( 'Acepta tarjetas de credito, depositos y transferencias.', 'woo-epayco-gateway' );
        //$this->order_button_text = __('Pay', 'epayco_woocommerce');
		$this->has_fields           = false;
        $this->supports         = array(
            'products',
            'refunds',
        );
        // Load the settings
        $this->init_form_fields();
        $this->init_settings();
        $this->msg['message']   = "";
        $this->msg['class']     = "";
        // Define user set variables
        $this->title            = $this->get_option( 'title' );
        $this->epayco_customerid = $this->get_option('epayco_customerid');
        $this->epayco_secretkey = $this->get_option('epayco_secretkey');
        $this->epayco_publickey = $this->get_option('epayco_publickey');
        $this->epayco_privatekey = $this->get_option('epayco_privatekey');
        $this->monto_maximo = $this->get_option('monto_maximo');
        //$this->max_monto = $this->get_option('monto_maximo');
        $this->description      = $this->get_option( 'description' );
        $this->epayco_testmode = $this->get_option('epayco_testmode');
        if ($this->get_option('epayco_reduce_stock_pending') !== null ) {
            $this->epayco_reduce_stock_pending = $this->get_option('epayco_reduce_stock_pending');
        }else{
            $this->epayco_reduce_stock_pending = "yes";
        }
        $this->epayco_type_checkout=$this->get_option('epayco_type_checkout');
        $this->epayco_endorder_state=$this->get_option('epayco_endorder_state');
        $this->epayco_cancelled_endorder_state=$this->get_option('epayco_cancelled_endorder_state');
        $this->epayco_url_response=$this->get_option('epayco_url_response');
        $this->epayco_url_confirmation=$this->get_option('epayco_url_confirmation');
        $this->epayco_lang=$this->get_option('epayco_lang');
        $this->response_data = $this->get_option('response_data');

		// Actions
		add_action( 'valid-' . $this->id . '-standard-ipn-request', array( $this, 'successful_request' ) );
        add_action('ePayco_init_validation', array( $this, 'ePayco_successful_validation'));
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Payment listener/API hook
		add_action( 'woocommerce_api_wc_gateway_' . $this->id, array( $this, 'check_ipn_response' ) );
        add_action( 'woocommerce_api_' . strtolower( get_class( $this )."Validation" ), array( $this, 'validate_ePayco_request' ) );

        add_action( 'woocommerce_checkout_create_order'. $this->id,array( $this, 'add_expiration') );
		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = false;
		}

        if ($this->epayco_testmode == "yes") {
            if (class_exists('WC_Logger')) {
                $this->log = new WC_Logger();
            } else {
                $this->log = WC_ePayco::woocommerce_instance()->logger();
            }
        }
	}


	function is_valid_for_use() {
		if ( ! in_array( get_woocommerce_currency(), array('COP','USD'), true ) ) {
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
	public function admin_options() {
        $validation_url=get_site_url() . "/";
        $validation_url = add_query_arg( 'wc-api', get_class( $this )."Validation", $validation_url );
        $logo_url=get_site_url() . "/";
        $logo_url = add_query_arg( 'wc-api', get_class( $this )."ChangeLogo", $logo_url );
		?>


        <div class="container-fluid">
            <div class="panel panel-default" style="">
                <img  src="<?php echo EPAYCO_PLUGIN_URL.'/assets/images/logo.svg' ?>">
                <div id="path_upload"  hidden>
                    <?php esc_html_e( $logo_url, 'text_domain' ); ?>
                </div>
                <div id="path_images"  hidden>
                    <?php echo EPAYCO_PLUGIN_URL.'assets/images' ?>
                </div>
                <div id="path_validate"  hidden>
                    <?php esc_html_e( $validation_url, 'text_domain' ); ?>
                </div>
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-pencil"></i><?php esc_html_e( 'Configuración Epayco', 'woo-epayco-gateway' ); ?></h3>
                </div>ePayco
            </div>
            <div style ="color: #31708f; background-color: #d9edf7; border-color: #bce8f1;padding: 10px;border-radius: 5px;">
                <?php esc_html_e( 'Este módulo le permite aceptar pagos seguros por la plataforma de pagos ePayco.Si el cliente decide pagar por ePayco, el estado del pedido cambiará a ', 'woo-epayco-gateway' ); ?><b>
                    <?php esc_html_e( ' Esperando Pago', 'woo-epayco-gateway' ); ?></b>.
                <br><?php esc_html_e( 'Cuando el pago sea Aceptado o Rechazado ePayco envía una confirmación a la tienda para cambiar el estado del pedido.', 'woo-epayco-gateway' ); ?>
            </div>

        <?php if ( $this->is_valid_for_use() ) : ?>
            <table class="form-table epayco-table">
                <?php
                // Generate the HTML For the settings form.
                $this->generate_settings_html();
                ?>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_epayco_enabled"><?php esc_html_e( 'Validar llaves', 'woo-epayco-gateway' ); ?></label>
                        <span hidden id="public_key">0</span>
                        <span hidden id="private_key">0</span>
                        <td class="forminp">
                            <form method="post" action="#">
                                <label for="woocommerce_epayco_enabled">
                                </label>
                                <input type="button" class="button-primary woocommerce-save-button validar" value="Validar">
                                <p class="description">
                                    <?php esc_html_e( 'Validación de llaves PUBLIC_KEY y PRIVATE_KEY', 'woo-epayco-gateway' ); ?>
                                </p>
                            </form>
                            <br>
                            <!-- The Modal -->
                            <div id="myModal" class="modal">
                                <!-- Modal content -->
                                <div class="modal-content">
                                    <span class="close">&times;</span>
                                    <center>
                                        <img src="'.$logo.'">
                                    </center>
                                    <p><strong><?php esc_html_e( 'Llaves de comercio inválidas', 'woo-epayco-gateway' );?></strong> </p>
                                    <p><?php esc_html_e( 'Las llaves Public Key, Private Key insertadas<br>del comercio son inválidas.<br>Consúltelas en el apartado de integraciones <br>Llaves API en su Dashboard ePayco.', 'woo-epayco-gateway' );?></p>
                                </div>
                            </div>

                        </td>
                    </th>
                </tr>
            </table><!--/.form-table-->
        <?php
        else :
            $currencies          = array('USD','COP');
            $formated_currencies = '';

            foreach ( $currencies as $currency ) {
                $formated_currencies .= $currency . ', ';
            }
            ?>

        </div>





        
	<div class="inline error">
        <p><strong><?php esc_html_e( 'Gateway Disabled', 'woo-epayco-gateway' );
	?>
		</strong>: 
			<?php
				esc_html_e( 'Servired/Epayco only support ', 'woo-epayco-gateway' );
				echo esc_html( $formated_currencies );
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
	function init_form_fields() {
		$this->form_fields = array(
			'enabled'          => array(
				'title'   => __( 'Habilitar/Deshabilitar', 'woo-epayco-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Habilitar ePayco', 'woo-epayco-gateway' ),
				'default' => 'yes',
			),
			'title'            => array(
				'title'       => __( '<span class="epayco-required">Título</span>', 'woo-epayco-gateway' ),
				'type'        => 'text',
				'description' => __( 'Corresponde al título que el usuario ve durante el Checkout.', 'woo-epayco-gateway' ),
				'default'     => __( 'Checkout ePayco (Tarjetas de crédito,debito,efectivo)', 'woo-epayco-gateway' ),
				'desc_tip'    => true,
			),
			'description'      => array(
				'title'       => __( '<span class="epayco-required">Descripción</span>', 'woo-epayco-gateway' ),
				'type'        => 'textarea',
				'description' => __( 'Corresponde a la descripción que verá el usuario durante el Checkout', 'woo-epayco-gateway' ),
				'default'     => __( 'Checkout ePayco (Tarjetas de crédito,débito,efectivo)', 'woo-epayco-gateway' ),
			),
            'epayco_customerid' => array(
                'title'       => __( '<span class="epayco-required">P_CUST_ID_CLIENTE</span>', 'woo-epayco-gateway' ),
                'type'        => 'text',
                'description' => __( 'ID de cliente que lo identifica en ePayco. Lo puede encontrar en su panel de clientes en la opción configuración', 'woo-epayco-gateway' ),
                'default' => '',
                //'desc_tip' => true,
                'placeholder' => '',
            ),
            'epayco_secretkey' => array(
                'title'       => __( '<span class="epayco-required">P_KEY</span>', 'woo-epayco-gateway' ),
                'type'        => 'text',
                'description' => __( 'LLave para firmar la información enviada y recibida de ePayco. Lo puede encontrar en su panel de clientes en la opción configuración', 'woo-epayco-gateway' ),
                'default' => '',
                //'desc_tip' => true,
                'placeholder' => '',
            ),
            'epayco_publickey' => array(
                'title'       => __( '<span class="epayco-required">PUBLIC_KEY</span>', 'woo-epayco-gateway' ),
                'type'        => 'text',
                'description' => __( 'LLave para autenticar y consumir los servicios de ePayco, Proporcionado en su panel de clientes en la opción configuración', 'woo-epayco-gateway' ),
                'default' => '',
                //'desc_tip' => true,
                'placeholder' => '',
            ),
            'epayco_privatekey' => array(
                'title'       => __( '<span class="epayco-required">PRIVATE_KEY</span>', 'woo-epayco-gateway' ),
                'type'        => 'text',
                'description' => __( 'LLave para autenticar y consumir los servicios de ePayco, Proporcionado en su panel de clientes en la opción configuración', 'woo-epayco-gateway' ),
                'default' => '',
                //'desc_tip' => true,
                'placeholder' => '',
            ),
			'epayco_testmode' => array(
				'title'       => __( 'Sitio en pruebas', 'woo-epayco-gateway' ),
				'type'        => 'checkbox',
                'label' => __('Habilitar el modo de pruebas', 'woo-epayco-gateway'),
				'description' => __( 'Habilite para realizar pruebas', 'woo-epayco-gateway' ),
                'default' => 'no',
			),
			'epayco_type_checkout'         => array(
				'title'       => __( 'Tipo Checkout', 'woo-epayco-gateway' ),
				'type'        => 'select',
                'css' =>'line-height: inherit',
                'label' => __('Seleccione un tipo de Checkout:', 'woo-epayco-gateway'),
				'description' => __( '(Onpage Checkout, el usuario al pagar permanece en el sitio) ó (Standard Checkout, el usario al pagar es redireccionado a la pasarela de ePayco)', 'woo-epayco-gateway' ),
                'options' => array('false'=>"Onpage Checkout","true"=>"Standard Checkout"),
			),
			'epayco_endorder_state'     => array(
				'title'       => __( 'Estado Final del Pedido', 'woo-epayco-gateway' ),
                'type' => 'select',
                'css' =>'line-height: inherit',
				'description' => __( 'Seleccione el estado del pedido que se aplicará a la hora de aceptar y confirmar el pago de la orden', 'woo-epayco-gateway' ),
                'options' => array(
                    'epayco-processing'=> __( 'ePayco Procesando Pago', 'woo-epayco-gateway' ),
                    "epayco-completed"=> __( 'ePayco Pago Completado', 'woo-epayco-gateway' ),
                    'processing'=> __( 'Procesando', 'woo-epayco-gateway' ),
                    "completed"=> __( 'Completado', 'woo-epayco-gateway' )
                ),
			),
			'epayco_cancelled_endorder_state'         => array(
				'title'       => __( 'Estado Cancelado del Pedido', 'woo-epayco-gateway' ),
                'type' => 'select',
                'css' =>'line-height: inherit',
				'description' => __( 'Seleccione el estado del pedido que se aplicará cuando la transacciónes Cancelada o Rechazada', 'woo-epayco-gateway' ),
                'options' => array(
                    'epayco-cancelled'=> __( 'ePayco Pago Cancelado', 'woo-epayco-gateway' ),
                    "epayco-failed"=> __( 'ePayco Pago Fallido', 'woo-epayco-gateway' ),
                    'cancelled'=> __( 'Cancelado', 'woo-epayco-gateway' ),
                    "failed"=> __( 'Fallido', 'woo-epayco-gateway' )
                ),
			),
            'epayco_url_response'          => array(
                'title'       => __( 'Página de Respuesta', 'woo-epayco-gateway' ),
                'type'        => 'select',
                'css' =>'line-height: inherit',
                'description' => __( 'Url de la tienda donde se redirecciona al usuario luego de pagar el pedido', 'woo-epayco-gateway' ),
                'options'       => $this->get_pages(__('Seleccionar pagina', 'woo-epayco-gateway')),
            ),
			'epayco_url_confirmation'          => array(
				'title'       => __( 'Página de Confirmación', 'woo-epayco-gateway' ),
				'type'        => 'select',
                'css' =>'line-height: inherit',
				'description' => __( 'Url de la tienda donde ePayco confirma el pago', 'woo-epayco-gateway' ),
                'options'       => $this->get_pages(__('Seleccionar pagina', 'woo-epayco-gateway')),
			),
			'epayco_reduce_stock_pending'    => array(
				'title'       => __( 'Reducir el stock en transacciones pendientes', 'woo-epayco-gateway' ),
				'type'        => 'checkbox',
                'css' =>'line-height: inherit',
				'default'     => 'yes',
				'description' => sprintf( __( 'Habilite para reducir el stock en transacciones pendientes', 'woo-epayco-gateway' ) ),
			),
            'epayco_lang'          => array(
                'title'       => __( 'Idioma del Checkout', 'woo-epayco-gateway' ),
                'type'        => 'select',
                'css' =>'line-height: inherit',
                'description' => __( 'Seleccione el idioma del checkout', 'woo-epayco-gateway' ),
                'default'     => 'es',
                'options'     => array(),
            ),
            'response_data'     => array(
				'title'       => __( 'Habilitar envió de atributos a través de la URL de respuesta', 'woo-epayco-gateway' ),
                'type' => 'checkbox',
                'label' => __('Habilitar el modo redirección con data', 'woo-epayco-gateway'),
				'description' => __( 'Al habilitar esta opción puede exponer información sensible de sus clientes, el uso de esta opción es bajo su responsabilidad, conozca esta información en el siguiente  <a href="https://docs.epayco.co/payments/checkout#scroll-response-p" target="_blank">link.</a>', 'woo-epayco-gateway' ),
                'default'     => 'no',
			),
			/*'monto_maximo' => array(
				'title'       => __( 'Monto máximo', 'woo-epayco-gateway' ),
				'type'        => 'text',
				'description' => __( 'Ingresa el monto máximo permitido a pagar por el método de pago', 'woo-epayco-gateway' ),
                'default' => '3000000',
                //'desc_tip' => true,
                'placeholder' => '3000000',
			),*/
		);
		$epayco_langs   = array(
            '1'	  => 'Español',
            '2'	  => 'English - Inglés');

		foreach ( $epayco_langs as $epayco_lang => $valor ) {
			$this->form_fields['epayco_lang']['options'][ $epayco_lang ] = $valor;
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
	 * Generate the epayco form
	 *
	 * @param mixed $order_id
	 * @return string
	 */
	function generate_epayco_form( $order_id ) {
		global $woocommerce;
        $order = new WC_Order($order_id);
        $descripcionParts = array();
        $iva=0;
        $ico=0;
        $base_tax=$order->get_subtotal()-$order->get_total_discount();
        foreach($order->get_items('tax') as $item_id => $item ) {
            if( strtolower( $item->get_label() ) == 'iva' ){
                $iva += round($item->get_tax_total(),2);
            }
            if( strtolower( $item->get_label() ) == 'ico'){
                $ico += round($item->get_tax_total(),2);
            }
        }

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
        $redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
        $redirect_url = add_query_arg( 'order_id', $order_id, $redirect_url );
        $myIp=$this->getCustomerIp();
        $lang = $this->epayco_lang == 1 ? "es" : "en";
        if ($this->get_option('epayco_url_confirmation' ) == 0) {
            $confirm_url = $redirect_url.'&confirmation=1';
        } else {
            $confirm_url = get_permalink($this->get_option('epayco_url_confirmation'));
        }

        $name_billing=$order->get_billing_first_name().' '.$order->get_billing_last_name();
        $address_billing=$order->get_billing_address_1();
        $phone_billing=@$order->billing_phone;
        $email_billing=@$order->billing_email;

        //Busca si ya se restauro el stock
        if (!EpaycoOrder::ifExist($order_id)) {
            //si no se restauro el stock restaurarlo inmediatamente
            EpaycoOrder::create($order_id,1);
            //$this->restore_order_stock($order->get_id(),"decrease");
        }
        $orderStatus = "pending";
        $current_state = $order->get_status();
        if($current_state != $orderStatus){
            $order->update_status($orderStatus);
            //$this->restore_order_stock($order->get_id(),"decrease");
        }
        echo sprintf('
                    <script
                       src="https://epayco-checkout-testing.s3.amazonaws.com/checkout.preprod.js">
                    </script>
                    <script> var handler = ePayco.checkout.configure({
                        key: "%s",
                        test: "%s"
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
                        extras_epayco:{extra5:"p19"},
                        method_confirmation: "POST"
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
                            return  fetch("https://cms.epayco.io/checkout/payment/session", {
                                method: "POST",
                                body: JSON.stringify(info),
                                headers
                            })
                                .then(res =>  res.json())
                                .catch(err => err);
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
                                error.message;
                            });
                    }
                    var openChekout = function () {
                        //handler.open(data);
                        bntPagar.style.pointerEvents = "none";
                        openNewChekout()
                    }
                    bntPagar.addEventListener("click", openChekout);
            	    openChekout()
                </script>
                </form>
                </center>
        ',  trim($this->epayco_publickey),
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
            trim($this->epayco_publickey),
            trim($this->epayco_privatekey)
        );
        wp_enqueue_script('epayco',  'https://epayco-checkout-testing.s3.amazonaws.com/checkout.preprod.js', array(), $this->version, null);
		wc_enqueue_js('
		jQuery("#btn_epayco_new").click(function(){
            console.log("epayco")
		});
		'
		);
		return '<form  method="post" id="appGateway">
		        </form>';
	}
	/**
	 * Process the payment and return the result
	 *
	 * @param int $order_id
	 * @return array
	 */
	function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * Output for the order received page.
	 * @param $order_id
	 * @return void
	 */
	function receipt_page( $order_id ) {
        echo ' <div class="loader-container">
                    <div class="loading"></div>
                </div>
                <p style="text-align: center;" class="epayco-title">
                    <span class="animated-points">' . esc_html__( 'Cargando métodos de pago', 'woo-epayco-gateway' ) . '</span>
                    <br><small class="epayco-subtitle"> ' . esc_html__( '', 'woo-epayco-gateway' ) . '</small>
                </p>';

        if ($this->epayco_lang === "2") {
            $epaycoButtonImage = 'https://multimedia.epayco.co/epayco-landing/btns/Boton-epayco-color-Ingles.png';
        }else{
            $epaycoButtonImage = 'https://multimedia.epayco.co/epayco-landing/btns/Boton-epayco-color1.png';
        }
		echo '<p>       
                 <center>
                    <a id="btn_epayco" href="#">
                        <img src="'.$epaycoButtonImage.'">
                    </a>
                 </center> 
               </p>';
		echo $this->generate_epayco_form( $order_id );
	}

	/**
	 * Check for Epayco HTTP Notification
	 *
	 * @return void
	 */
	function check_ipn_response() {
		@ob_clean();
		$post = stripslashes_deep( $_POST );
		if ( true ) {
			header( 'HTTP/1.1 200 OK' );
			do_action( 'valid-' . $this->id . '-standard-ipn-request', $post );
		} else {
			wp_die( 'Do not access this page directly (ePayco)' );
		}
	}

    function validate_ePayco_request(){
        @ob_clean();
        if ( ! empty( $_REQUEST ) ) {
            header( 'HTTP/1.1 200 OK' );
            do_action( "ePayco_init_validation", $_REQUEST );
        } else {
            wp_die( 'Do not access this page directly (ePayco)' );
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
        global $woocommerce;
        //$clear_cart = !($this->clear_cart == "yes");
        $order_id_info = sanitize_text_field($_GET['order_id']);
        $order_id_explode = explode('=',$order_id_info);
        $order_id_rpl  = str_replace('?ref_payco','',$order_id_explode);
        $order_id = $order_id_rpl[0];
        $order = new WC_Order($order_id);
        $isConfirmation = sanitize_text_field($_GET['confirmation']) == 1;

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
            $x_fecha_transaccion = trim(sanitize_text_field($_REQUEST['x_fecha_transaccion']));
        }else{
            $ref_payco = sanitize_text_field($_REQUEST['ref_payco']);
            if(empty($ref_payco)){
                $ref_payco =$order_id_rpl[1];
            }
            if (!$ref_payco)
            {
                $explode=explode('=',$order_id);
                $ref_payco=$explode[1];
            }

            if(!$ref_payco){
                if($this->epayco_testmode == "yes"){
                    $order->update_status('epayco_cancelled');
                    $order->add_order_note('Pago rechazado');
                    $this->restore_order_stock($order->get_id());
                }else{
                    $order->update_status('epayco-cancelled');
                    $order->add_order_note('Pago rechazado');
                    $this->restore_order_stock($order->get_id());
                }

                /*foreach ($order->get_items() as $item) {
                    // Get an instance of corresponding the WC_Product object
                    $product_id = $item->get_product()->id;
                    $qty = $item->get_quantity(); // Get the item quantity
                    WC()->cart->add_to_cart( $product_id ,(int)$qty);
                }*/
                wp_safe_redirect( wc_get_checkout_url() );
                exit();

            }

            $url = 'https://secure.epayco.io/validation/v1/reference/'.$ref_payco;
            $response = wp_remote_get(  $url );
            $body = wp_remote_retrieve_body( $response );
            $jsonData = @json_decode($body, true);
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
        $x_approval_code_value = intval($x_approval_code);
        if(floatval($order->get_total()) == floatval($x_amount)){
            if("yes" == $isTestPluginMode){
                $validation = true;
            }
            if("no" == $isTestPluginMode ){
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
        if($authSignature == $x_signature && $validation){
            switch ($x_cod_transaction_state) {
                case 1: {
                    if($isTestMode=="true"){
                        update_post_meta( $order->get_id(), 'modo', esc_attr('pruebas'));
                        update_post_meta( $order->get_id(), 'fecha', esc_attr($x_fecha_transaccion));
                        update_post_meta( $order->get_id(), 'franquicia', esc_attr($x_franchise));
                        update_post_meta( $order->get_id(), 'autorizacion', esc_attr($x_approval_code));
                        $message = "Modo:pruebas, \nref_payco: ".$x_ref_payco." \nFecha y hora transacción: ".$x_fecha_transaccion." \nFranquicia/Medio de pago: ".$x_franchise. " \nCódigo de autorización: ".$x_approval_code;
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
                        $message = "Modo:Producción, \nref_payco: ".$x_ref_payco." \nFecha y hora transacción: ".$x_fecha_transaccion." \nFranquicia/Medio de pago: ".$x_franchise. " \nCódigo de autorización: ".$x_approval_code;
                        update_post_meta( $order->get_id(), 'modo', esc_attr('Producción'));
                        update_post_meta( $order->get_id(), 'fecha', esc_attr($x_fecha_transaccion));
                        update_post_meta( $order->get_id(), 'franquicia', esc_attr($x_franchise));
                        update_post_meta( $order->get_id(), 'autorizacion', esc_attr($x_approval_code));
                        $orderStatus = $this->epayco_endorder_state;
                    }

                    if($current_state == "epayco_failed" ||
                        $current_state == "epayco_cancelled" ||
                        $current_state == "failed" ||
                        $current_state == "epayco-cancelled" ||
                        $current_state == "epayco-failed"
                    ){
                        if (!EpaycoOrder::ifStockDiscount($order_id)){
                            //se descuenta el stock
                            EpaycoOrder::updateStockDiscount($order_id,1);
                            if($current_state != $orderStatus){
                                if($isTestMode=="true"){
                                    $this->restore_order_stock($order->get_id(),"decrease");
                                }else{
                                    if($orderStatus == "epayco-processing" || $orderStatus == "epayco-completed"){
                                        $this->restore_order_stock($order->get_id(),"decrease");
                                    }
                                }

                                $order->payment_complete($x_ref_payco);
                                $order->update_status($orderStatus);
                                //$order->add_order_note($message);
                            }
                        }

                    }else{
                        //Busca si ya se descontó el stock
                        if (!EpaycoOrder::ifStockDiscount($order_id)){
                            //se descuenta el stock
                            EpaycoOrder::updateStockDiscount($order_id,1);
                        }

                        if($current_state == "epayco_processing" ||
                            $current_state == "epayco_completed" ||
                            $current_state == "processing_test" ||
                            $current_state == "completed_test" ||
                            $current_state == "epayco-processing" ||
                            $current_state == "epayco-completed" ||
                            $current_state == "processing-test" ||
                            $current_state == "completed-test"||
                            $current_state == "processing" ||
                            $current_state == "completed"
                        ){}
                        else{
                            
                            if($current_state =="pending")
                            {
                                $this->restore_order_stock($order->get_id());
                            }
                            $order->payment_complete($x_ref_payco);
                            $order->update_status($orderStatus);
                            //$order->add_order_note($message);
                        }
                    }
                    echo "1";
                } break;
                case 2:
                case 4:
                case 10:
                case 11:    
                    {
                    if($isTestMode=="true"){
                        if(
                            $current_state == "epayco_processing" ||
                            $current_state == "epayco_completed" ||
                            $current_state == "processing_test" ||
                            $current_state == "completed_test"
                        ){}else{
                            switch ($this->epayco_cancelled_endorder_state ){
                                case 'epayco-cancelled':{
                                    $orderStatus ='epayco_cancelled';
                                }break;
                                case 'epayco-failed':{
                                    $orderStatus ='epayco_failed';
                                }break;
                                case 'cancelled':{
                                    $orderStatus ='cancelled';
                                }break;
                                case 'failed':{
                                    $orderStatus ='failed';
                                }break;
                            }
                            $message = "Modo:pruebas, \nref_payco: ".$x_ref_payco." \nFecha y hora transacción: ".$x_fecha_transaccion." \nFranquicia/Medio de pago: ".$x_franchise. " \nCódigo de autorización: ".$x_approval_code;
                            update_post_meta( $order->get_id(), 'modo', esc_attr('pruebas'));
                            update_post_meta( $order->get_id(), 'fecha', esc_attr($x_fecha_transaccion));
                            update_post_meta( $order->get_id(), 'franquicia', esc_attr($x_franchise));
                            update_post_meta( $order->get_id(), 'autorizacion', esc_attr($x_approval_code));
                            $messageClass = 'woocommerce-error';
                            $order->update_status($orderStatus);
                            //$order->add_order_note($message);
                            if($current_state =="epayco-cancelled"||
                                $current_state == $orderStatus ){
                            }else{
                                if($current_state =="on-hold" || $current_state =="pending"){
                                    $order->update_status($orderStatus);
                                    //$order->add_order_note($message);
                                }
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
                            $message = "Modo:Producción, \nref_payco: ".$x_ref_payco." \nFecha y hora transacción: ".$x_fecha_transaccion." \nFranquicia/Medio de pago: ".$x_franchise. " \nCódigo de autorización: ".$x_approval_code;
                            update_post_meta( $order->get_id(), 'modo', esc_attr('Producción'));
                            update_post_meta( $order->get_id(), 'fecha', esc_attr($x_fecha_transaccion));
                            update_post_meta( $order->get_id(), 'franquicia', esc_attr($x_franchise));
                            update_post_meta( $order->get_id(), 'autorizacion', esc_attr($x_approval_code));
                            $messageClass = 'woocommerce-error';
                            $order->update_status($this->epayco_cancelled_endorder_state);
                            //$order->add_order_note($message);
                            if($current_state =="pending"){
                                $order->update_status($this->epayco_cancelled_endorder_state);
                                $this->restore_order_stock($order->get_id(),"increase");
                                //$order->add_order_note($message);
                            }
                            if($current_state =="on-hold"){
                                $order->update_status($this->epayco_cancelled_endorder_state);
                            }
                        }
                    }
                    echo "2";
                    if(!$isConfirmation){
                        $woocommerce->cart->empty_cart();
                        foreach ($order->get_items() as $item) {
                            // Get an instance of corresponding the WC_Product object
                            $product_id = $item->get_product()->id;
                            $qty = $item->get_quantity(); // Get the item quantity
                            WC()->cart->add_to_cart( $product_id ,(int)$qty);
                        }
                        wp_safe_redirect( wc_get_checkout_url() );
                        exit();
                    }
                } break;
                case 3:
                case 7:    
                    {

                    //Busca si ya se restauro el stock y si se configuro reducir el stock en transacciones pendientes
                    if (!EpaycoOrder::ifStockDiscount($order_id) && $this->get_option('epayco_reduce_stock_pending') != 'yes') {
                        //actualizar el stock
                        EpaycoOrder::updateStockDiscount($order_id,1);
                    }
                    if($isTestMode=="true"){
                        update_post_meta( $order->get_id(), 'modo', esc_attr('pruebas'));
                        update_post_meta( $order->get_id(), 'fecha', esc_attr($x_fecha_transaccion));
                        update_post_meta( $order->get_id(), 'franquicia', esc_attr($x_franchise));
                        update_post_meta( $order->get_id(), 'autorizacion', esc_attr($x_approval_code));
                        
                    }else{
                        update_post_meta( $order->get_id(), 'modo', esc_attr('Producción'));
                        update_post_meta( $order->get_id(), 'fecha', esc_attr($x_fecha_transaccion));
                        update_post_meta( $order->get_id(), 'franquicia', esc_attr($x_franchise));
                        update_post_meta( $order->get_id(), 'autorizacion', esc_attr($x_approval_code));
                    }
                        $message = 'Pago pendiente de aprobación';
                        $orderStatus = "on-hold";
                        if($current_state != $orderStatus){
                            $order->update_status($orderStatus);
                            /*if($current_state == "epayco_failed" ||
                                $current_state == "epayco_cancelled" ||
                                $current_state == "failed" ||
                                $current_state == "epayco-cancelled" ||
                                $current_state == "epayco-failed"
                            ){
                                $this->restore_order_stock($order->get_id(),"decrease");
                            }*/
                            //$order->add_order_note($message);
                        }
                    echo "3";
                } break;
                case 6: {
                    $message = 'Pago Reversada' .$x_ref_payco;
                    $messageClass = 'woocommerce-error';
                    $order->update_status('refunded');
                    $order->add_order_note('Pago Reversado');
                    $this->restore_order_stock($order->get_id());
                    echo "6";
                } break;
                default: {
                    if(
                        $current_state == "epayco-processing" ||
                        $current_state == "epayco-completed" ||
                        $current_state == "processing" ||
                        $current_state == "completed"){
                    } else{
                        $message = 'Pago '.sanitize_text_field($_REQUEST['x_transaction_state']) . $x_ref_payco;
                        $messageClass = 'woocommerce-error';
                        $order->update_status('epayco-failed');
                        $order->add_order_note('Pago fallido o abandonado');
                        $this->restore_order_stock($order->get_id());
                    }
                    echo "default";
                } break;
            }

            //validar si la transaccion esta pendiente y pasa a rechazada y ya habia descontado el stock
            if(($current_state == 'on-hold' || $current_state == 'pending') && ((int)$x_cod_transaction_state == 2 || (int)$x_cod_transaction_state == 4) && EpaycoOrder::ifStockDiscount($order_id)){
                //si no se restauro el stock restaurarlo inmediatamente
                $this->restore_order_stock($order_id);
            };

        } else {
            if($isTestMode=="true"){
                if($x_cod_transaction_state==1){
                    $message = 'Pago exitoso Prueba';
                        update_post_meta( $order->get_id(), 'modo', esc_attr('prueba'));
                        update_post_meta( $order->get_id(), 'fecha', esc_attr($x_fecha_transaccion));
                        update_post_meta( $order->get_id(), 'franquicia', esc_attr($x_franchise));
                        update_post_meta( $order->get_id(), 'autorizacion', esc_attr($x_approval_code));
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
                    if($current_state == "epayco_failed" ||
                        $current_state == "epayco_cancelled" ||
                        $current_state == "failed" ||
                        $current_state == "epayco-cancelled" ||
                        $current_state == "epayco-failed"
                    ){}else{
                        if($isTestPluginMode == "no" && $x_cod_transaction_state == 1)
                        {
                            $this->restore_order_stock($order->get_id());
                        }
                    }
                }

            }else{
                if(
                    $current_state == "epayco-processing" ||
                    $current_state == "epayco-completed" ||
                    $current_state == "processing" ||
                    $current_state == "completed"){
                }else{
                    $message = 'Firma no valida';
                    update_post_meta( $order->get_id(), 'modo', esc_attr('Producción'));
                    update_post_meta( $order->get_id(), 'fecha', esc_attr($x_fecha_transaccion));
                    update_post_meta( $order->get_id(), 'franquicia', esc_attr($x_franchise));
                    update_post_meta( $order->get_id(), 'autorizacion', esc_attr($x_approval_code));
                    $orderStatus = 'epayco-failed';
                    if($x_cod_transaction_state!=1 && !empty($x_cod_transaction_state)){
                        if($current_state == "epayco_failed" ||
                            $current_state == "epayco_cancelled" ||
                            $current_state == "failed" ||
                            $current_state == "epayco-cancelled" ||
                            $current_state == "epayco-failed"
                        ){}else{
                            $this->restore_order_stock($order->get_id());
                            $order->update_status($orderStatus);
                            //$order->add_order_note($message);
                            $messageClass = 'error';
                        }
                    }
                    echo $x_cod_transaction_state." firma no valida: ".$validation;
                }
            }
        }

        if (isset($_REQUEST['confirmation'])) {
            echo $x_cod_transaction_state;
            exit();
        }else{
            if ($this->get_option('epayco_url_response' ) == 0) {
                $redirect_url = $order->get_checkout_order_received_url();
            } else {
                $woocommerce->cart->empty_cart();
                $redirect_url = get_permalink($this->get_option('epayco_url_response'));
                $redirect_url = add_query_arg(['ref_payco'=>$ref_payco] , $redirect_url);
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

    function add_expiration( $order, $data ) {
        $items_count  = WC()->cart->get_cart_contents_count();

        $order->update_meta_data('expiration_date', date( 'Y-m-d H:i:s', strtotime( '+'. ( $items_count * 60 ) .' minutes' ) ) );
    }

    /**
     * @param $validationData
     */
    function ePayco_successful_validation($validationData)
    {
        $username = sanitize_text_field($validationData['epayco_publickey']);
        $password = sanitize_text_field($validationData['epayco_privatey']);
        $response = wp_remote_post( 'https://apify.epayco.io/login', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
            ),
        ) );
        $data = json_decode( wp_remote_retrieve_body( $response ) );
        if($data->token){
            echo "success";
            exit();
        }
    }

     function string_sanitize($string, $force_lowercase = true, $anal = false) {

        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]","}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;","â€”", "â€“", ",", "<", ".", ">", "/", "?");
        $clean = trim(str_replace($strip, "", strip_tags($string)));
        $clean = preg_replace('/\s+/', "_", $clean);
        $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
        return $clean;
    }

    /**
     * @param $order_id
     */
    public function restore_order_stock($order_id,$operation = 'increase')
    {
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
    public function getCustomerIp(){
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
}
