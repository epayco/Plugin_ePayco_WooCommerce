<?php
/*
Plugin Name: WooCommerce - Payco Gateway
Description: Payco Payment Gateway for WooCommerce. Payco, paga y cobra online
Version: 1.0
License: GNU General Public License v3.0
*/

add_action('plugins_loaded', 'woocommerce_payco_init', 0);
define('IMGDIR', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/img/');

function woocommerce_payco_init() {
	if(!class_exists('WC_Payment_Gateway')) return;

    if( isset($_GET['msg']) && !empty($_GET['msg']) ){
        add_action('the_content', 'showpaycoMessage');
    }
    function showpaycoMessage($content){
            return '<div class="'.htmlentities($_GET['type']).'">'.htmlentities(urldecode($_GET['msg'])).'</div>'.$content;
    }
    /**
	 * Payco Gateway Class
     * @access public
     * @param 
     * @return 
     */
	class WC_payco extends WC_Payment_Gateway{
		
		public function __construct(){
			global $woocommerce;
			// $this->load_plugin_textdomain();
    
            /* settings */

			$this->id = 'payco';
			$this->icon = IMGDIR . 'payco.png';
			$this->method_title = __('ePayco','payco-woocommerce');
			$this->method_description	= __("ePayco, Recibe Pagos  Con Tarjetas De Credito, Debito / Efectivo.",'payco-woocommerce');
			$this->has_fields = false;
			$this->init_form_fields();
			$this->init_settings();
			$this->language = get_bloginfo('language');
			$this->en_pruebas = $this->settings['en_pruebas'];
			$this->title = $this->settings['title'];
			$this->description = $this->settings['description'];
		    $this->taxes = $this->settings['taxes'];
			$this->p_cust_id_cliente = $this->settings['p_cust_id_cliente'];
			$this->p_key =$this->settings['p_key'];
			$this->redirect_page_id = $this->settings['redirect_page_id'];
			$this->currency = ($this->is_valid_currency())?get_woocommerce_currency():'COP';
			$this->form_method = $this->settings['POST'];
			if($this->en_pruebas=='TRUE'){
				$this->liveurl = 'https://secure.payco.co/checkout.php';
			}else{
				$this->liveurl = 'https://secure.payco.co/checkout.php';
			}
			

			/* mesagges */

			$this->msg_approved	= "El pago fue aprobado. ";
			$this->msg_declined	= "El pago fue rechazado.";
			$this->msg_pending	= "El pago esta pendiente. ";

			if ($this->en_pruebas == "yes")
				 $this->debug = "yes";

			add_filter( 'woocommerce_currencies', 'add_payco_all_currency' );
			add_filter( 'woocommerce_currency_symbol', 'add_payco_all_symbol', 10, 2);

			$this->msg['message'] 	= "";
			$this->msg['class'] 	= "";

			$this->log = new WC_Logger();
					
			add_action('payco_init', array( $this, 'payco_successful_request'));
			add_action( 'woocommerce_receipt_payco', array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_payco_response' ) );
			
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				/* 2.0.0 */
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
			} else {
				/* 1.6.6 */
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}
			
		}

	  //   public function load_plugin_textdomain()
	  //   {
			// load_plugin_textdomain( 'payco-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . "/languages" );
	  //   }
    	/**
		 * Check if Gateway can be display 
	     * @access public
	     * @return void
	     */
	    function is_available() {
			global $woocommerce;

			if ( $this->enabled=="yes" ) :
				
				if ( !$this->is_valid_currency()) return false;
				
				if ( $woocommerce->version < '1.5.8' ) return false;
				
				if (!$this->p_cust_id_cliente || !$this->p_key ) return false;

				return true;
			endif;

			return false;
		}
    	/**
	     * @access public
	     * @return void
	     */

		function init_form_fields() {

			$this->form_fields = array(

				'enabled' => array(
					'title' 		=> __('Activar/Desactivar', 'payco-woocommerce'),
					'type' 			=> 'checkbox',
					'label' 		=> __('Activar ePayco', 'payco-woocommerce'),
					'default' 		=> 'no',
					'description' 	=> __('Active o desactive el plugin ', 'payco-woocommerce')
				),
      			'title' => array(
					'title' 		=> __('Titulo:', 'payco-woocommerce'),
					'type'			=> 'text',
					'default' 		=> __('ePayco - Pagar con Tarjeta de Credito, Debito / Efectivo', 'payco-woocommerce'),
					'description' 	=> __('Ingrese el titulo que el usuario ve durante el pago', 'payco-woocommerce'),
					
				),
      			'p_description' => array(
					'title' 		=> __('Descripción:', 'payco-woocommerce'),
					'type' 			=> 'textarea',
					'default' 		=> __('Recibe Pagos  Con Tarjetas De Credito, Debito / Efectivo','payco-woocommerce'),
					'description' 	=> __('Ingrese la descripción que el usuario ve durante el pago', 'payco-woocommerce'),
					
				),
				'p_cust_id_cliente' => array(
					'title' 		=> __('P_CUST_ID_CLIENTE', 'payco-woocommerce'),
					'type' 			=> 'text',
					'description' 	=> __('Código de identificación del comercio, lo puedes ver en el panel de clientes ingresando a: https://secure.payco.co/clientes', 'payco-woocommerce'),
					
				),
				'p_key' => array(
					'title' 		=> __('P_KEY', 'payco-woocommerce'),
					'type' 			=> 'text',
					'description' 	=>  __('LLave transaccional del comercio, la puedes ver en el panel de clientes ingresando a: https://secure.payco.co/clientes', 'payco-woocommerce'),
					
		        ),
		      	'en_pruebas' => array(
					'title' 		=> __('Modo Prueba', 'payco-woocommerce'),
					'type' 			=> 'select',
					'options' 		=> array('TRUE' => 'Si', 'FALSE' => 'No'),
					'label' 		=> __('Modo prueba', 'payco-woocommerce'),
					'default' 		=> 'FALSE',
					'description' 	=> __('Enviar las transacciones en modo de pruebas Si/No', 'payco-woocommerce'),
					
		        ),
		         'taxes' => array(
					 'title' 		=> __('Tasa de impuestos', 'payco-woocommerce'),
					 'type' 		=> 'text',
					 'label' 		=> __('Tasa de impuestos', 'payco-woocommerce'),
					 'default' 		=> '0',
					 'description' 	=> __('Ingrese el % de impuestos de la compra: ejemplo iva 16% o 0 si la compra tiene los impuestos incluidos', 'payco-woocommerce'),
					
		         ),
		        'currency' => array(
					'title' 		=> __('Moneda', 'payco-woocommerce'),
					'type' 			=> 'select',
					'options' 		=> array('COP' => 'COP', 'USD' => 'USD'),
					'label' 		=> __('Moneda', 'payco-woocommerce'),
					'default' 		=> 'COP',
					'description' 	=> __('Moneda en la cual se cobra y se envia la transacción', 'payco-woocommerce'),
					
		        ),
		        'form_method' => array(
					'title' 		=> __('Metodo del Formulario', 'payco-woocommerce'),
					'type' 			=> 'select',
					'default' 		=> 'POST',
					'options' 		=> array('POST' => 'POST'),
					'description' 	=> __('Permite seleccionar el metodo del formulario', 'payco-woocommerce'),
					
		        ),
		      	'redirect_page_id' => array(
					'title' 		=> __('Página Respuesta', 'payco-woocommerce'),
					'type' 			=> 'select',
					'options' 		=> $this->get_pages(__('Seleccionar pagina', 'payco-woocommerce')),
					'description' 	=> __('Seleccione la página de respuesta luego de finalizar el paga en ePayco', 'payco-woocommerce'),
					
		        ),
			);      
		} 

        /**
	     * @access public
	     * @return string
         **/
		public function admin_options(){
			echo '<h3>'.__('ePayco', 'payco-woocommerce').'</h3>';
			echo '<p>'.__('Permite que sus clientes puedan pagar con Tarjetas De Credito, Debito / Efectivo ', 'payco-woocommerce').'</p>';
			echo '<table class="form-table">';
			// Generate the HTML For the settings form.
			$this->generate_settings_html();
			echo '</table>';
		}
        /**
		 * Generate the Payco Payment Fields
	     *
	     * @access public
	     * @return string
	     */
		function payment_fields(){
			if($this->description) echo wpautop(wptexturize($this->description));
		}
		/**
		 * Generate the Payco Form for checkout
	     *
	     * @access public
	     * @param mixed $order
	     * @return string
		**/
		function receipt_page($order){
			echo '<p>'.__('Gracias por tu orden, para continuar debes hacer click en el botón para pagar con payco.', 'payco-woocommerce').'</p>';
			echo $this->generate_payco_form($order);
		}
		/**
		 * Generate Payco POST arguments
	     *
	     * @access public
	     * @param mixed $order_id
	     * @return string
		**/
		function get_payco_args($order_id){
			global $woocommerce;
			$order = new WC_Order( $order_id );
			$txnid = $order->order_key;
			$total=round($order->order_total,2);
			
			$redirect_url = ($this->redirect_page_id=="" || $this->redirect_page_id==0)?get_site_url() . "/":get_permalink($this->redirect_page_id);
			$redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
			$redirect_url = add_query_arg( 'order_id', $order_id, $redirect_url );
			$redirect_url = add_query_arg( '', $this->endpoint, $redirect_url );

			$productinfo = $this->getDescriptionOrder($order);
     		
     		$p_signature=md5($this->p_cust_id_cliente.'^'.$this->p_key.'^'.$txnid.'^'.$total.'^'.$this->currency);
			$tax=$this->getTaxesOrder($order);
			$tax=round($tax,2);
			if((int)$tax>0){
				$base_tax=$total-$tax;	
			}else{
				$base_tax=0;
			}

			$payco_args = array(
				'p_cust_id_cliente' => $this->p_cust_id_cliente,
				'p_cliente' 		=> "",
				'p_key' 		    => $this->p_key,
				'p_signature'		=> $p_signature,
				'p_id_invoice'   	=> $txnid,
				'p_description'		=> $productinfo,
				'p_amount' 			=> $total,
				'p_tax' 			=> $tax,
			    'p_amount_base'		=> $base_tax,
				'p_currency_code' 	=> $this->currency,
				'p_test_request'    => $this->en_pruebas,								
		        'p_billing_name'    => $order->billing_first_name,
		        'p_billing_lastname'=> $order->billing_last_name,
		        'p_billing_address' => $order->billing_address_1,
		        'p_billing_city'    => $order->billing_city,
		        'p_billing_state'   => $order->billing_state,
		        'p_billing_zip'     => $order->billing_postcode,
		        'p_billing_country' => $order->billing_country,
		        'p_billing_phone'   => $order->billing_phone,
		        'p_billing_email'   => $order->billing_email,
				'p_url_response'    => $redirect_url,
				'p_url_confirmation'=> $redirect_url,
				'p_customer_ip'		=> $_SERVER['REMOTE_ADDR'],		
				'p_extra1'			=> "plugin_woocommerce",
				'p_extra2'			=> "",
				'p_extra3'			=> "",
			);
			return $payco_args;
		}

		/**
		 * Generate the Payco button link
	     *
	     * @access public
	     * @param mixed $order_id
	     * @return string
	    */
	    function generate_payco_form( $order_id ) {
			global $woocommerce;

			$order = new WC_Order( $order_id );

			$payco_adr = $this->liveurl;

			$payco_args = $this->get_payco_args( $order_id );
			$payco_args_array = array();

			foreach ($payco_args as $key => $value) {
				$payco_args_array[] = '<input type="hidden" name="'.esc_attr( $key ).'" value="'.esc_attr( $value ).'" />';
			}
			$code='jQuery("body").block({
						message: "' . esc_js( __( 'Gracias por tu orden, ahora nos dirigimos a ePayco para realizar el pago.', 'payco-woocommerce' ) ) . '",
						baseZ: 99999,
						overlayCSS:
						{
							background: "#000",
							opacity: 0.6
						},
						css: {
					        padding:        "20px",
					        zindex:         "9999999",
					        textAlign:      "center",
					        color:          "#555",
					        border:         "1px solid #aaa",
					        backgroundColor:"#fff",
					        cursor:         "wait",
					        lineHeight:		"24px",
					    }
					});
				jQuery("#submit_payco_payment_form").click();';

			if (version_compare( WOOCOMMERCE_VERSION, '2.1', '>=')) {
				 wc_enqueue_js($code);
			} else {
				$woocommerce->add_inline_js($code);
			}

			return '<form action="'.esc_url( $payco_adr ).'" method="POST" id="payco_payment_form" target="_top">
					' . implode( '', $payco_args_array) . '
					<input type="submit" class="button alt" id="submit_payco_payment_form" value="' . __( 'Pago via ePayco', 'payco-woocommerce' ) . '" /> <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__( 'Cancelar orden &amp; restaurar carro', 'woocommerce' ).'</a>
				</form>';
		}

		/**
	     * Process the payment and return the result
	     *
	     * @access public
	     * @param int $order_id
	     * @return array
	     */
		function process_payment( $order_id ) {
			$order = new WC_Order( $order_id );
			if ( $this->form_method == 'GET' ) {
				$payco_args = $this->get_payco_args( $order_id );
				$payco_args = http_build_query( $payco_args, '', '&' );
				$payco_adr = $this->liveurl . '?';

				return array(
					'result' 	=> 'success',
					'redirect'	=> $payco_adr . $payco_args
				);
			} else {
				if (version_compare( WOOCOMMERCE_VERSION, '2.1', '>=')) {
					return array(
						'result' 	=> 'success',
						'redirect'	=> add_query_arg('order-pay', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))
					);
				} else {
					return array(
						'result' 	=> 'success',
						'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))
					);
				}

			}

		}
		/**
		 * Check for valid Payco server callback
		 *
		 * @access public
		 * @return void
		**/
		function check_payco_response(){
			@ob_clean();
	    	if ( ! empty( $_REQUEST ) ) {
	    		header( 'HTTP/1.1 200 OK' );
	        	do_action( "payco_init", $_REQUEST );
			} else {
				wp_die( __("ePayco Request Failure", 'payco-woocommerce') );
	   		}
		}

		/**
		 * Process Payco Response and update the order information
		 *
		 * @access public
		 * @param array $posted
		 * @return void
		 */
		function payco_successful_request( $posted ) {
			global $woocommerce;
			// Custom holds post ID
			 
			
			//$posted['x_respuesta']='Pendiente';

		    if ( !empty( $posted['x_ref_payco'] )) {

			    $order = $this->get_payco_order( $posted );
			 	//Comprabar la respuesta para continuar 
			    $checkTransaction=$this->checkTransactionPayco($order,$posted);
			    $estado_orden=$order->get_status();
		    
			   	     	   
		        if (!empty($posted['x_respuesta'])) {
			        // We are here so lets check status and do actions
        			if ( ! empty( $posted['x_ref_payco'] ) )
	                	update_post_meta( $order->id, __('Referencia ePayco', 'payco-woocommerce'), $posted['x_ref_payco'] );
	                
	            	if ( ! empty( $posted['x_approval_code'] ) )
	                	update_post_meta( $order->id, __('Autorizacion', 'payco-woocommerce'), $posted['x_approval_code'] );

	                	//Actualizando el metodo de pago pasarlo a payco
	                if ( ! empty( $posted['x_transaction_id'] ) )
	                	update_post_meta( $order->id, __('Recibo', 'payco-woocommerce'), $posted['x_transaction_id'] );

	                if ( ! empty( $posted['x_franchise'] ) )
	                	update_post_meta( $order->id, __('Franquicia', 'payco-woocommerce'), $posted['x_franchise'] );
	                
	                	//Actualizando el metodo de pago pasarlo a payco
	                if ( ! empty( $posted['x_respuesta'] ) )
	                	update_post_meta( $order->id, __('Respuesta', 'payco-woocommerce'), $posted['x_respuesta'] );

	                if ( ! empty( $posted['x_response_reason_text'] ) )
	                	update_post_meta( $order->id, __('Motivo', 'payco-woocommerce'), $posted['x_response_reason_text'] );

	                if(!$checkTransaction['success']){
					    	$errores=true;
					    	update_post_meta('payco',__('Transacción Log','payco-woocommerce'),$checkTransaction['error']);
					    	$this->msg['message'] = $checkTransaction['error'];
							$this->msg['class'] = 'woocommerce-error';
							
							$woocommerce->cart->empty_cart();
							
					 }else{
					    	$errores=false;
					 }
					if(!$errores){ 
			        switch ( $posted['x_respuesta'] ) {
			            case 'Aceptada' :
			            case 'Pendiente' :
			                /*Cambiar la orden a completa  y aprobada*/
			                if ($posted['x_respuesta'] == 'Aceptada' ) {
			                    $order->update_status( 'completed', __( $posted['x_response_reason_text'], 'payco-woocommerce' ));
			                	$order->add_order_note( __( 'Transacción Aceptada', 'payco-woocommerce') );
								
								$this->msg['message'] =  $posted['x_response_reason_text'];
								$this->msg['class'] = 'woocommerce-message';
								
			                	$order->payment_complete();
			                	//Si la transaccion ya esta aceptada no reducir stock;
			                	
			                	if($estado_orden=='pending'){
			                		$order->reduce_order_stock();
			                	}
			                	$woocommerce->cart->empty_cart();

			                } else {
			                	//Pendiente
			                	$order->update_status( 'on-hold', sprintf( __( 'Pago pendiente: %s', 'payco-woocommerce'), $posted['x_response_reason_text'] ) );
								$this->msg['message'] = $posted['x_response_reason_text'];
								$this->msg['class'] = 'woocommerce-info';
								$woocommerce->cart->empty_cart();
			                }
			                
			            	if ( 'yes' == $this->debug )
			                	$this->log->add( 'payco', __('Pago completado.', 'payco-woocommerce') );

			            break;
			            /*Cuanddo esta rechazada*/
			            case 'Rechazada' :

			                // Order failed
			               		$order->update_status( 'cancelled', __( $posted['x_response_reason_text'], 'payco-woocommerce' ));

								$this->msg['message'] = $posted['x_response_reason_text'];
								$this->msg['class'] = 'woocommerce-error';
								$woocommerce->cart->empty_cart();
								break;			
							
			            break;
			            case 'Fallida' :
			            	  	$order->update_status( 'failed', __( $posted['x_response_reason_text'], 'payco-woocommerce' ));
								$this->msg['message'] = $posted['x_response_reason_text'];
								$this->msg['class'] = 'woocommerce-error';
								$woocommerce->cart->empty_cart();
						break;	
			            default :
			               	 	$order->update_status( 'failed', __( $posted['x_response_reason_text'], 'payco-woocommerce' ));
								$this->msg['message'] = $posted['x_response_reason_text'];
								$this->msg['class'] = 'woocommerce-error';
								$woocommerce->cart->empty_cart();
			            break;
			        	}
		        	
		        	} 
				}
				$redirect_url = ($this->redirect_page_id=="" || $this->redirect_page_id==0)?get_site_url() . "/":get_permalink($this->redirect_page_id);
                //For wooCoomerce 2.0
                $arguments=array();
                foreach($_POST as $key=>$value){
                	$arguments[$key]=$value;
                }
                $redirect_url = add_query_arg($arguments , $redirect_url );

                wp_redirect( $redirect_url );
                
		    }
		    //confirmation process
		}
		/**
		 * @access public
		 * @param mixed $order
		 * @param mixed $posted
		 * @return bool
		 */
		public function checkTransactionPayco($order,$posted){

	    $url_confirm_payment='https://secure.payco.co/pasarela/estadotransaccion?id_transaccion='.$posted['x_ref_payco'];		

	    $data=json_decode($this->getPaycoContentUrl($url_confirm_payment));
	    $error="";
	   
	    if(is_object($data)){
	    	//Comprobar el precio  	
	   		
		 	// Validate Amount
		 	//x_amount//Variable original del cliente
		 	//Simpre se devuelve en pesos;

		 	
		 	 if ((int) $order->get_total() != (int) $data->data->x_amount ) {
		     	$error='Payment error: Monto invalido (Monto ' . $posted['x_amount_ok'] . ')';
		     	$response=array('success'=>false,'error'=>$error);
		     }
		     if((int)$data->data->x_amount_ok!=(int) $posted['x_amount_ok']){
		     	$error='Payment error: Monto invalido (Monto ' . $posted['x_amount_ok'] . ')';
		     	$response=array('success'=>false,'error'=>$error);
		     } 	 	
		     if($data->data->x_cust_id_cliente!=$posted['x_cust_id_cliente']){
		     	$error='Payment error: x_cust_id_cliente invalido (x_cust_id_cliente ' . $data->data->x_cust_id_cliente . ')';
		     	$response=array('success'=>false,'error'=>$error);
		     }
		     //Verificar que la orden este pendiente
		     if($order->get_status()=='completed'){
		     	$error='Payment error: Orden Procesada (La orden ya fue procesada, estado orden ' . $order->get_status() . ')';
		     	$response=array('success'=>false,'error'=>$error);
		     }
		     //Verificar que los estados sean iguales
		     if($posted['x_respuesta']!=$data->data->x_respuesta){
		     	$error='Payment error: Estado de transaccion no coincide (El estado de la transacción enviada no concide con el estado de la transacción real' . $data->data->x_respuesta . ')';
		     	$response=array('success'=>false,'error'=>$error);
		     }
		     if($error==""){
		     	$response=array('success'=>true,'error'=>'');	
		     }
		     
	   		
	   		//return true;
		    }else{
		    	$response=array('success'=>false,'error'=>'No se pudo validar la transacción');
		    }		    
		    return $response;
		}

		public function getPaycoContentUrl($url){
			
			$ch = curl_init($url);				
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");	
			$response = curl_exec($ch);
			curl_close($ch);
			if(!$response) {
			    return false;
			}else{
				return $response;
			}
		}


		/**
		 * @access public
		 * @param mixed $posted
		 * @return void
		 */
		function get_payco_order( $posted ) {
			$custom =  $posted['x_id_factura'];

	    	// Backwards comp for IPN requests
	    	
		    $order_id = (int) $_GET['order_id'] ;
		    $order_key = $posted['x_id_factura'];
	    	
			$order = new WC_Order( $order_id );

			if ( ! isset( $order->id ) ) {
				// We have an invalid $order_id, probably because invoice_prefix has changed
				$order_id = woocommerce_get_order_id_by_order_key( $order_key );
				$order = new WC_Order( $order_id );
			}

			// Validate key
			if ( $order->order_key !== $order_key ) {
	        	if ( $this->debug=='yes' )
	        		$this->log->add( 'payco', __('Error: Order Key does not match invoice.', 'payco-woocommerce') );
	        	exit;
	        }

	        return $order;
		}
		/**
		 * @access public
		 * @param mixed $order
		 * @return void
		 */
		public function getDescriptionOrder($order){

			$items = $order->get_items();
			$order_description="";
			foreach($items as $item){
				$name_item=$item['name'];
				$subtotal_item=($item['item_meta']['_line_subtotal'][0]);
				//var_dump($item);
				$order_description=$name_item.' $'.round($subtotal_item,2).' '.get_woocommerce_currency();
				$order_description.=',';
			}			
			$order_description=substr($order_description,0,strlen($order_description)-1);
			return $order_description;
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

		/**
		 * Check the tax rates active in woocommerce.
		 *
		 * @access public
		 * @return bool
		 */
		/*function get_tax_rates() {
			$p_tax= new WC_Tax();
			$countries = new WC_Countries();
			$countrybase=$countries->get_base_country();
			$tax_rates=$taxes->get_shop_base_rate('Standard');
			print_r($tax_rates);
			return $tax_rates;
		}*/

		/**
		 * Check if current currency is valid for Payco
		 *
		 * @access public
		 * @return bool
		 */
		function is_valid_currency() {
			//var_dump(get_woocommerce_currency());
			if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_payco_supported_currencies', array( 'COP', 'USD' ) ) ) ) return false;

			return true;
		}
		
		/**
		 * Get pages for return page setting
		 *
		 * @access public
		 * @return bool
		 */
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
		}
		/**
		 * Add all currencys supported by Payco Latem so it can be display 
		 * in the woocommerce settings
		 *
		 * @access public
		 * @return bool
		 */
		function add_payco_all_currency( $currencies ) {
			$currencies['COP'] = __( 'Colombian Peso', 'payco-woocommerce');
			$currencies['USD'] = __( 'Dolar', 'payco-woocommerce');
			return $currencies;
		}
		/**
		 * Add simbols for all currencys in Payco so it can be display 
		 * in the woocommerce settings
		 *
		 * @access public
		 * @return bool
		 */
		function add_payco_all_symbol( $currency_symbol, $currency ) {
			switch( $currency ) {
			case 'COP': $currency_symbol = '$'; break;
			case 'USD': $currency_symbol = '$'; break;
			}
			return $currency_symbol;
		}
		/**
		* Add the Gateway to WooCommerce
		**/
		function woocommerce_add_payco_gateway($methods) {
			$methods[] = 'WC_payco';
			return $methods;
		}

		add_filter('woocommerce_payment_gateways', 'woocommerce_add_payco_gateway' );
	}