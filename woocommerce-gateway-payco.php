<?php
/**
 * WooCommerce Epayco Gateway
 *
 * @package WooCommerce Epayco Gateway
 *
 * Plugin Name: WooCommerce Epayco Gateway
 * Description: Plugin ePayco Gateway for WooCommerce.
 * Version: 8.3.0
 * Author: ePayco
 * Author URI: http://epayco.co
 * Tested up to: 6.8.3
 * WC requires at least: 8.3.0
 * WC tested up to: 10.2.1
 * Text Domain: woo-epayco-gateway
 * Domain Path: /i18n/languages/
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

define( 'EPAYCO_WOOCOMMERCE_VERSION', '5.3.0' );
define( 'EPAYCO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
if ( ! defined( 'EPAYCO_PLUGIN_PATH' ) ) {
	define( 'EPAYCO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'EPAYCO_PLUGIN_DATA_URL' ) ) {
	define( 'EPAYCO_PLUGIN_DATA_URL', EPAYCO_PLUGIN_URL . 'includes/data/' );
}
if ( ! defined( 'EPAYCO_PLUGIN_CLASS_PATH' ) ) {
	define( 'EPAYCO_PLUGIN_CLASS_PATH', EPAYCO_PLUGIN_PATH . 'classes/' );
}

add_action( 'plugins_loaded', 'woocommerce_gateway_epayco_init', 11 );

add_action( 'before_woocommerce_init',
	function() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * epayco hook
 *
 * @param string $hook page hook.
 */
function epayco_styles_css( $hook ) {

    if ( 'woocommerce_page_wc-settings' == $hook ) {
        wp_register_style( 'aboutEpayco', EPAYCO_PLUGIN_URL . 'assets/css/epayco-css.css', array(), '1.2.0' );
        wp_enqueue_style( 'aboutEpayco' );
        wp_register_script('aboutEpaycoJquery',  EPAYCO_PLUGIN_URL . 'assets/js/frontend/admin.js', array('jquery'), '7.0.0', null);
        wp_enqueue_script('aboutEpaycoJquery');
    }
}
add_action( 'admin_enqueue_scripts', 'epayco_styles_css' );

/**
 * Epayco init.
 */
function woocommerce_gateway_epayco_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}
	/**
	 * Localisation
	 */
	load_plugin_textdomain( 'woo-epayco-gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );


	/**
	 * Epayco add method.
	 *
	 * @param array $methods all WooCommerce methods.
	 */
	function woocommerce_add_gateway_epayco_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Epayco';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_gateway_epayco_gateway' );


	function plugin_abspath_epayco() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	function plugin_url_epayco() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	require_once EPAYCO_PLUGIN_CLASS_PATH . 'class-wc-gateway-epayco.php';

}

function woocommerce_gateway_epayco_block_support() {
	if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		require_once 'includes/blocks/wc-gateway-epayco-support.php';
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				$payment_method_registry->register( new WC_Gateway_Epayco_Support );
			}
		);
	}
}
add_action( 'woocommerce_blocks_loaded', 'woocommerce_gateway_epayco_block_support' );

function epayco_woocommerce_addon_settings_link( $links ) {
    array_push( $links, '<a href="admin.php?page=wc-settings&tab=checkout&section=epayco">' . __( 'Configuración' ) . '</a>' );
    return $links;
}

add_filter( "plugin_action_links_".plugin_basename( __FILE__ ),'epayco_woocommerce_addon_settings_link' );
function epayco_update_db_check()
{
    require_once(dirname(__FILE__) . '/includes/blocks/EpaycoOrder.php');
    EpaycoOrder::setup();
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

function styling_admin_order_list() {
    global $pagenow, $post;
    //if( $pagenow != 'edit.php') return; // Exit
    //if( get_post_type($post->ID) != 'shop_order' ) return; // Exit
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
        .order-status.status-<?php esc_html_e( $order_status_failed, 'woo-epayco-gateway' );  ?> {
            background: #eba3a3;
            color: #761919;
        }
        .order-status.status-<?php esc_html_e( $order_status_on_hold, 'woo-epayco-gateway' ); ?> {
            background: #f8dda7;
            color: #94660c;
        }
        .order-status.status-<?php esc_html_e( $order_status_processing, 'woo-epayco-gateway' ); ?> {
            background: #c8d7e1;
            color: #2e4453;
        }
        .order-status.status-<?php esc_html_e( $order_status_processing_, 'woo-epayco-gateway' ); ?> {
            background: #c8d7e1;
            color: #2e4453;
        }
        .order-status.status-<?php esc_html_e( $order_status_completed, 'woo-epayco-gateway' ); ?> {
            background: #d7f8a7;
            color: #0c942b;
        }
        .order-status.status-<?php esc_html_e( $order_status_completed_, 'woo-epayco-gateway' ); ?> {
            background: #d7f8a7;
            color: #0c942b;
        }
        .order-status.status-<?php esc_html_e( $order_status_cancelled, 'woo-epayco-gateway' ); ?> {
            background: #eba3a3;
            color: #761919;
        }
    </style>

    <?php
}
add_action('admin_head', 'styling_admin_order_list' );

/////////////////////////////////////////////////////////////////////
// Display as order meta
function my_field_order_meta_handler( $item_id, $values, $cart_item_key ) {
    if( isset( $values['modo'] ) ) {
        wc_add_order_item_meta( $item_id, "modo", $values['modo'] );
    }
}
add_action( 'woocommerce_new_order_item', 'my_field_order_meta_handler', 1, 3 );

// Update the user meta with field value
add_action('woocommerce_checkout_update_user_meta', 'my_custom_checkout_field_update_user_meta');
function my_custom_checkout_field_update_user_meta( $user_id ) {
    if ($user_id && $_POST['modo']) update_user_meta( $user_id, 'modo', esc_attr($_POST['modo']) );
}

// Update the order meta with field value
add_action('woocommerce_checkout_update_order_meta', 'my_custom_checkout_field_update_order_meta');
function my_custom_checkout_field_update_order_meta( $order_id ) {
    if ($_POST['modo']) update_post_meta( $order_id, 'My Field', esc_attr($_POST['modo']));
}
/**
 * Display field value on the order edit page
 */
//woocommerce_admin_order_data_after_payment_info
//woocommerce_admin_order_data_after_order_details
//woocommerce_admin_order_data_after_billing_address
//woocommerce_admin_order_data_after_shipping_address
add_action( 'woocommerce_admin_order_data_after_payment_info', 'my_custom_checkout_field_display_admin_order_meta', 10, 1 );
function my_custom_checkout_field_display_admin_order_meta( $order ){
     $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
     $ref_payco = $order->get_meta('refPayco')??get_post_meta( $order_id, 'refPayco', true );
     $modo = $order->get_meta('modo')??get_post_meta( $order_id, 'modo', true );
     $fecha = $order->get_meta('fecha')??get_post_meta( $order_id, 'fecha', true );
     $franquicia = $order->get_meta('franquicia')??get_post_meta( $order_id, 'franquicia', true );
     $autorizacion = $order->get_meta('autorizacion')??get_post_meta( $order_id, 'autorizacion', true );
     if( null !== $ref_payco && null !== $fecha && null !== $franquicia && null !== $autorizacion
     ){
    echo '<br>
    <h3>Detalle de la transacción</h3>
    <div>
        <div class="order_data_column_container">
            <div class="order_data_column">
                <div class="address">    
                    <p><strong>'.__('Pago con ePayco').':</strong> ' . $ref_payco . '</p>
                    <p><strong>'.__('Modo').':</strong> ' . $modo . '</p>
                </div>
            </div>
            <div class="order_data_column">
                <div class="address">    
                    <p><strong>'.__('Fecha y hora transacción').':</strong> ' . $fecha . '</p>
                    <p><strong>'.__('Franquicia/Medio de pago').':</strong> ' . $franquicia . '</p>
                </div>
            </div>
            <div class="order_data_column">
                <div class="address">    
                    <p><strong>'.__('Código de autorización').':</strong> ' . $autorizacion . '</p>
                </div>
            </div>
        </div>
    </div>
    ';
     }

}


///////////////////////////////////////////////////////////////////////
add_action('woocommerce_checkout_create_order_line_item', 'add_custom_hiden_order_item_meta_data', 20, 4 );
function add_custom_hiden_order_item_meta_data( $item, $cart_item_key, $values, $order ) {

    // Set user meta custom field as order item meta
    if( $meta_value = get_user_meta( $order->get_user_id(), 'billing_enumber', true ) )
        $item->update_meta_data( 'pa_billing-e-number', $meta_value );
}


function epayco_cron_job_deactivation() {
    wp_clear_scheduled_hook('woocommerc_epayco_cron_hook');
    as_unschedule_action( 'woocommerce_epayco_cleanup_draft_orders' );
    $timestamp = wp_next_scheduled('woocommerce_epayco_cleanup_draft_orders');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'woocommerce_epayco_cleanup_draft_orders');
    }
}
register_deactivation_hook(__FILE__, 'epayco_cron_job_deactivation');

function payco_shop_order($postOrOrderObject) {
    $order = ($postOrOrderObject instanceof WP_Post) ? wc_get_order($postOrOrderObject->ID) : $postOrOrderObject;
    try {
        $logger = new WC_Logger();
        $paymentsIds   = explode(',', $order->get_meta(WC_Gateway_Epayco::PAYMENTS_IDS, true));
        $lastPaymentId = trim(end($paymentsIds));
        $orderStatus = $order->get_status();
        $logger->add('ePayco_shop_order', $lastPaymentId);
        if (!$lastPaymentId) {
            return false;
        }
        if($orderStatus == 'pending' || $orderStatus == 'on-hold'){
            $epayco = new WC_Gateway_Epayco();
            $token = $epayco->epyacoBerarToken();
            if($token && !isset($token['error'])){
                $path = "payment/transaction";
                $data = [ "referencePayco" => $paymentsIds[0]];
                $epayco_status = $epayco->getEpaycoStatusOrder($path,$data, $token);
                if ($epayco_status['success']) {
                    if (isset($epayco_status['data']) && is_array($epayco_status['data'])) {
                        $epayco->epaycoUploadOrderStatus($epayco_status);
                    }
                }
            }
        }
    } catch (Exception $e) {
        throw new Exception('Couldn\'t find order'.$e->getMessage());
    }
}



add_action('add_meta_boxes_shop_order', 'payco_shop_order');
add_action('add_meta_boxes_woocommerce_page_wc-orders', 'payco_shop_order');


add_action('woocommerc_epayco_order_hook', 'woocommerce_epayco_cleanup_draft_orders');

register_deactivation_hook(__FILE__, 'epayco_cron_inactive');
function epayco_cron_inactive() {
    wp_clear_scheduled_hook('bf_epayco_event');
}
// function that registers new custom schedule
function bf_add_epayco_schedule( $schedules )
{
    $schedules[ 'every_five_minutes' ] = array(
        'interval' => 300,
        'display'  => 'Every 5 minutes',
    );

    return $schedules;
}

// function that schedules epayco event

function bf_schedule_epayco_event()
{
    // the actual hook to register new epayco schedule

    add_filter( 'cron_schedules', 'bf_add_epayco_schedule' );

    // schedule epayco event

    if( !wp_next_scheduled( 'bf_epayco_event' ) )
    {
        wp_schedule_event( time(), 'every_five_minutes', 'bf_epayco_event' );
    }
}
add_action( 'init', 'bf_schedule_epayco_event' );

// fire custom event

function bf_do_epayco_on_schedule()
{
    if (class_exists('WC_Gateway_Epayco')) {
        $ePayco = new WC_Gateway_Epayco();
        $ePayco->woocommerc_epayco_cron_job_funcion();
    }
    
}
add_action( 'bf_epayco_event', 'bf_do_epayco_on_schedule' );
