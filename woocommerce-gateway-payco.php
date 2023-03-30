<?php
/**
 * @since             1.0.0
 * @package           ePayco_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       ePayco Gateway WooCommerce
 * Description:       Plugin ePayco Gateway for WooCommerce.
 * Version:           6.7.2
 * Author:            ePayco
 * Author URI:        http://epayco.co
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       epayco-woocommerce
 * Domain Path:       /languages
 */


if (!defined('WPINC')) {
    die;
}
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

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
            public $max_monto;
            public static $p_key;
            public static $p_key_p;
            public function __construct()
            {
                $this->id = 'epayco';
                $this->version = '6.7.0';
                $url_icon = plugin_dir_url(__FILE__)."lib";
                $dir_ = __DIR__."/lib";
                if(is_dir($dir_)) {
                    $gestor = opendir($dir_);
                    if($gestor){
                        while (($image = readdir($gestor)) !== false){
                            if($image != '.' && $image != '..'){
                                if($image == "epayco.png"){
                                    $this->icon = $url_icon."/".$image;;
                                }
                            }
                        }
                    }
                }
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
                $this->epayco_cancelled_endorder_state=$this->get_option('epayco_cancelled_endorder_state');
                $this->epayco_url_response=$this->get_option('epayco_url_response');
                $this->epayco_url_confirmation=$this->get_option('epayco_url_confirmation');
                $this->epayco_lang=$this->get_option('epayco_lang')?$this->get_option('epayco_lang'):'es';
                $this->response_data = $this->get_option('response_data');
                $this->force_redirect = $this->get_option('force_redirect');
                $this->clear_cart = $this->get_option('clear_cart');
                $this->epayco_split_payment = $this->get_option('epayco_split_payment');
                $this->epayco_split_payment_type = $this->get_option('epayco_split_payment_type');
                $this->custom_order_numbers_enabled = $this->get_option( 'alg_wc_custom_order_numbers_enabled');
                $this->alg_wc_custom_order_numbers_prefix = $this->get_option( 'alg_wc_custom_order_numbers_prefix');
                add_filter('woocommerce_thankyou_order_received_text', array(&$this, 'order_received_message'), 10, 2 );
                add_action('ePayco_init', array( $this, 'ePayco_successful_request'));
                add_action('ePayco_init_validation', array( $this, 'ePayco_successful_validation'));
                add_action('ePayco_change_logo', array( $this, 'ePayco_new_logo'));
                add_action('woocommerce_receipt_' . $this->id, array(&$this, 'receipt_page'));
                add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_ePayco_response' ) );
                add_action( 'woocommerce_api_' . strtolower( get_class( $this )."Validation" ), array( $this, 'validate_ePayco_request' ) );
                add_action( 'woocommerce_api_' . strtolower( get_class( $this )."ChangeLogo" ), array( $this, 'change_logo' ) );
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                add_action('wp_ajax_nopriv_returndata',array($this,'datareturnepayco_ajax'));
                add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
                $custom_order_numbers_enabled = $this->custom_order_numbers_enabled == "yes" ? "true" : "false";
                if ( 'true' == $custom_order_numbers_enabled ) {
                    add_action( 'woocommerce_new_order', array( $this, 'add_new_order_number' ), 11 );
                    add_filter( 'woocommerce_order_number', array( $this, 'display_order_number' ), PHP_INT_MAX, 2 );
                    add_action( 'admin_notices', array( $this, 'alg_custom_order_numbers_update_admin_notice' ) );
                    add_action( 'admin_notices', array( $this, 'alg_custom_order_numbers_update_success_notice' ) );
                    // Add a recurring As action.
                    add_action( 'admin_init', array( $this, 'alg_custom_order_numbers_add_recurring_action' ) );
                    add_action( 'admin_init', array( $this, 'alg_custom_order_numbers_stop_recurring_action' ) );
                    add_action( 'alg_custom_order_numbers_update_old_custom_order_numbers', array( $this, 'alg_custom_order_numbers_update_old_custom_order_numbers_callback' ) );
                    // Include JS script for the notice.
                    add_action( 'admin_enqueue_scripts', array( $this, 'alg_custom_order_numbers_setting_script' ) );
                    add_action( 'wp_ajax_alg_custom_order_numbers_admin_notice_dismiss', array( $this, 'alg_custom_order_numbers_admin_notice_dismiss' ) );
                    add_action( 'woocommerce_settings_save_alg_wc_custom_order_numbers', array( $this, 'woocommerce_settings_save_alg_wc_custom_order_numbers_callback' ), PHP_INT_MAX );
                    add_action( 'woocommerce_shop_order_search_fields', array( $this, 'search_by_custom_number' ) );
                    //add_action( 'admin_menu', array( $this, 'add_renumerate_orders_tool' ), PHP_INT_MAX );
                    if ( 'yes' === apply_filters( 'alg_wc_custom_order_numbers', 'no', 'manual_counter_value' ) ) {
                        add_action( 'add_meta_boxes', array( $this, 'add_order_number_meta_box' ) );
                        add_action( 'save_post_shop_order', array( $this, 'save_order_number_meta_box' ), PHP_INT_MAX, 2 );
                    }

                    // check if subscriptions is enabled.
                    if ( in_array( 'woocommerce-subscriptions/woocommerce-subscriptions.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
                        add_action( 'woocommerce_checkout_subscription_created', array( $this, 'update_custom_order_meta' ), PHP_INT_MAX, 1 );
                        add_filter( 'wcs_renewal_order_created', array( $this, 'remove_order_meta_renewal' ), PHP_INT_MAX, 2 );
                        // To unset the CON meta key at the time of renewal of subscription, so that renewal orders don't have duplicate order numbers.
                        add_filter( 'wcs_renewal_order_meta', array( $this, 'remove_con_metakey_in_wcs_order_meta' ), 10, 3 );
                    }
                    add_filter( 'pre_update_option_alg_wc_custom_order_numbers_prefix', array( $this, 'pre_alg_wc_custom_order_numbers_prefix' ), 10, 2 );
                    add_action( 'admin_init', array( $this, 'alg_custom_order_number_old_orders_without_meta_key' ) );
                    add_action( 'admin_init', array( $this, 'alg_custom_order_numbers_add_recurring_action_to_add_meta_key' ) );
                    add_action( 'alg_custom_order_numbers_update_meta_key_in_old_con', array( $this, 'alg_custom_order_numbers_update_meta_key_in_old_con_callback' ) );
                    add_action( 'wp_ajax_alg_custom_order_numbers_admin_meta_key_notice_dismiss', array( $this, 'alg_custom_order_numbers_admin_meta_key_notice_dismiss' ) );

                }

                if ($this->epayco_testmode == "yes") {
                    if (class_exists('WC_Logger')) {
                        $this->log = new WC_Logger();
                    } else {
                        $this->log = WC_ePayco::woocommerce_instance()->logger();
                    }
                }




            }


            function order_received_message( $text, $order ) {
                return $text;
            }

            public function is_valid_for_use()
            {
                return in_array(get_woocommerce_currency(), array('COP', 'USD'));
            }

            public function admin_options()
            {
                $validation_url=get_site_url() . "/";
                $validation_url = add_query_arg( 'wc-api', get_class( $this )."Validation", $validation_url );
                $logo_url=get_site_url() . "/";
                $logo_url = add_query_arg( 'wc-api', get_class( $this )."ChangeLogo", $logo_url );
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

                    .modal {
                        display: none;
                        position: fixed;
                        z-index: 1;
                        padding-top: 100px;
                        left: 0;
                        top: 0;
                        width: 100%;
                        height: 100%;
                        overflow: auto;
                        background-color: rgb(0,0,0);
                        background-color: rgba(0,0,0,0.4);
                        justify-content: center;
                        align-items: center;
                    }

                    /* Modal Content */
                    .modal-content {
                        background-color: #ffff;
                        padding: 20px;
                        border: 1px solid #888;
                        position: absolute;
                        border-radius: 8px;
                        left: 50%;
                        top: 35%;
                        transform: translate(-50%, -50%);
                    }

                    .modal-content p {
                        position: static;
                        font-family: 'Open Sans';
                        font-style: normal;
                        font-weight: 400;
                        font-size: 16px;
                        line-height: 22px;
                        text-align: center;
                        color: #5C5B5C;
                        margin: 8px 0px;
                    }
                    /* The Close Button */
                    .close {
                        color: #aaaaaa;
                        float: right;
                        font-size: 28px;
                        font-weight: bold;
                    }

                    .close:hover,
                    .close:focus {
                        color: #000;
                        text-decoration: none;
                        cursor: pointer;
                    }
                    @media screen and (max-width: 425px) {
                        .modal-content {
                            width: 50% ;
                        }
                    }
                    @media screen and (max-width: 425px) {
                        .dropdown dt a{
                            width: 250px !important;
                        }
                    }
                </style>
                <div class="container-fluid">
                    <div class="panel panel-default" style="">
                        <img  src="<?php echo plugin_dir_url(__FILE__).'lib/logo.png' ?>">
                        <div id="path_upload"  hidden>
                            <?php esc_html_e( $logo_url, 'text_domain' ); ?>
                        </div>
                        <div id="path_images"  hidden>
                            <?php echo plugin_dir_url(__FILE__).'lib/images' ?>
                        </div>
                        <div id="path_validate"  hidden>
                            <?php esc_html_e( $validation_url, 'text_domain' ); ?>
                        </div>
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-pencil"></i>Configuración <?php _e('ePayco', 'epayco-woocommerce'); ?></h3>
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
                                    $logo=plugin_dir_url(__FILE__).'lib/images/logo_warning.png';
                                    $arrowLogo = plugin_dir_url(__FILE__)."lib/images/arrow.png";
                                    echo'
                                    <script>
                                        jQuery( document ).ready( function( $ ) {
                                            $(".validar").on("click", function() {
                                                var modal = document.getElementById("myModal");
                                                var url_validate = $("#path_validate")[0].innerHTML.trim();
                                                const epayco_publickey = $("input:text[name=woocommerce_epayco_epayco_publickey]").val().replace(/\s/g,"");
                                                const epayco_privatey = $("input:text[name=woocommerce_epayco_epayco_privatekey]").val().replace(/\s/g,"");
                                                if (epayco_publickey !== "" && 
                                                    epayco_privatey !== "") {
                                                        var formData = new FormData();
                                                        formData.append("epayco_publickey",epayco_publickey.replace(/\s/g,""));
                                                        formData.append("epayco_privatey",epayco_privatey.replace(/\s/g,""));
                                                        $.ajax({
                                                            url: url_validate,
                                                            type: "post",
                                                            data: formData,
                                                            contentType: false,
                                                            processData: false,
                                                            success: function(response) {
                                                                if (response == "success") {
                                                                    alert("validacion exitosa!");
                                                                } else {
                                                                    modal.style.display = "block";
                                                                }
                                                            }
                                                        });
                                                }else{
                                                    modal.style.display = "block";
                                                }
                                            });
                                        });
                                    </script>               
                                        <tr valign="top">
                                            <th scope="row" class="titledesc">
                                                <label for="woocommerce_epayco_enabled">'. __( 'ePayco: validar llaves', 'epayco-woocommerce' ) .'</label>
                                                <span hidden id="public_key">0</span>
                                                <span hidden id="private_key">0</span>
                                            </th>
                                            <td class="forminp">
                                                <form method="post" action="#">
                                                    <label for="woocommerce_epayco_enabled">
                                                    </label>
                                                    <input type="button" class="button-primary woocommerce-save-button validar" value="Validar">
                                                    <p class="description">
                                                    validacion de llaves PUBLIC_KEY y PRIVATE_KEY
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
                                                    <p><strong>Llaves de comercio inválidas</strong> </p>
                                                    <p>Las llaves Public Key, Private Key insertadas<br>
                                                     del comercio son inválidas.<br> 
                                                     Consúltelas en el apartado de integraciones <br> 
                                                     Llaves API en su Dashboard ePayco.</p>
                                                  </div>
                                                </div>

                                                <script>
                                                    var modal = document.getElementById("myModal");
                                                    var span = document.getElementsByClassName("close")[0];
                                                    span.onclick = function() {
                                                        modal.style.display = "none";
                                                    }
                                                </script>
                                            </td>
                                        </tr> 

                                        <tr valign="top">
                                          <th scope="row" class="titledesc">
                                             <label for="woocommerce_epayco_enabled">'. __( 'ePayco: cambiar logo', 'epayco-woocommerce' ) .'</label>
                                          </th>
                                            <td class="forminp">
                                            <script>
                                                jQuery( document ).ready( function( $ ) {
                                                    $(".upload").on("click", function() {
                                                        var url = $("#path_upload")[0].innerHTML.trim();
                                                        send(url)
                                                        return false;
                                                    });
                                                    async function  send(url){
                                                        const imgName = document.getElementById("info").children[0].name;
                                                        const img = $("#path_images")[0].innerHTML.trim()+"/"+imgName+".png";
                                                        await fetch(img, {
                                                            method: "GET"
                                                        })
                                                        .then(function(res)  {
                                                            let data = res.blob()
                                                            return data;
                                                        })
                                                        .then(blob => {
                                                         const files =  new File([blob], "epayco.png", blob);
                                                         var imageNames = imgName;
                                                         var formData = new FormData();
                                                            formData.append("file",files);
                                                             $.ajax({
                                                                 url: url,
                                                                 type: "post",
                                                                 data: formData,
                                                                 contentType: false,
                                                                 processData: false,
                                                                 success: function(response) {
                                                                     if (response != 0) {
                                                                         $(".card-img-top").attr("src", response);
                                                                          alert("el logo se subio de forma exitosa!");
                                                                     } else {
                                                                         alert("Formato de imagen incorrecto.");
                                                                     }
                                                                 }
                                                             });
                                                            return file;
                                                        });
                                                    }
                                                });
                                            </script>
                                            <fieldset>
                                                <legend class="screen-reader-text">
                                                </legend>
                                                <style>
                                                .desc { color:#6b6b6b;}
                                                .desc a {color:#0092dd;}
                                                .dropdown dd, .dropdown dt, .dropdown ul { margin:0px; padding:0px; }
                                                .dropdown dd { position:relative; }
                                                .dropdown a, .dropdown a:visited { color:#2c3338; text-decoration:none; outline:none;}
                                                .dropdown a:hover { color:#007cba;}
                                                .dropdown dt a:hover { color:#007cba; border: 1px solid #07cba;}
                                                .dropdown dt a {background:#ffffff url("'.$arrowLogo.'") no-repeat scroll right center; display:block; padding-right:20px;
                                                                border:1px solid #2c3338;width: 400px;}
                                                .dropdown dt a span {cursor:pointer; display:block; padding:5px;}
                                                .dropdown dd ul { background:#ffffff none repeat scroll 0 0; border:1px solid #d4ca9a; color:#C5C0B0; display:none;
                                                                  left:0px; padding:5px 0px; position:absolute; top:2px; width:auto; min-width:170px; list-style:none;}
                                                .dropdown span.value { display:none;}
                                                .dropdown dd ul li a { padding:5px; display:block;}
                                                .dropdown dd ul li a:hover { background-color:#d0c9af;}
                                                
                                                .dropdown img.flag { border:none; vertical-align:middle; margin-left:10px; }
                                                .flagvisibility { display:none;}
                                                </style>
                                                    <dl id="sample" class="select  dropdown">
                                                        <dt><a href="#"><span>Logos</span></a>
                                                            <div><span id="info"></span></div>
                                                        </dt>
                                                        <dd>
                                                            <ul>
                                                                <li><a href="#"><img class="flag" src="https://multimedia.epayco.co/epayco-landing/btns/epayco-logo-fondo-oscuro-lite.png" alt="" name="epayco1" /></a></li>
                                                                <li><a href="#"><img class="flag" src="https://multimedia.epayco.co/epayco-landing/btns/epayco-logo-fondo-claro-lite.png" alt="" name="epayco2" /></a></li>
                                                                <li><a href="#"><img class="flag" src="https://multimedia.epayco.co/epayco-landing/btns/epayco-logos-medios-de-pago-pequeno-horizontal-fondo-oscuro-powered-by-epayco.png" alt="" name="epayco3" /></a></li>
                                                                <li><a href="#"><img class="flag" src="https://multimedia.epayco.co/epayco-landing/btns/powered_01.png" alt="" name="epayco4" /></a></li>
                                                                <li><a href="#"><img class="flag" src="https://multimedia.epayco.co/epayco-landing/btns/epayco-logos-medios-de-pago-pequeno-horizontal-con-fondo-oscuro.png" alt="" name="epayco5" /></a></li>
                                                                <li><a href="#"><img class="flag" src="https://multimedia.epayco.co/epayco-landing/btns/epayco-logos-medios-de-pago-pequeno-horizontal-con-fondo-blanco.png" alt="" name="epayco6" /></a></li>
                                                            </ul>
                                                        </dd>
                                                    </dl>
                                                   
                                                    <span id="result"></span>
                                             
                                                <script type="text/javascript">
                                                jQuery( document ).ready( function( $ ) {
                                                    $(".dropdown img.flag").addClass("flagvisibility");
                                                    $(".dropdown dt a").click(function(event) {
                                                        event.preventDefault();
                                                        $(".dropdown dd ul").toggle();
                                                    });         
                                                    $(".dropdown dd ul li a").click(function(event) {
                                                        event.preventDefault();
                                                        var text = $(this).html();
                                                        $(".dropdown dt div span").html(text);
                                                        $(".dropdown dd ul").hide();      
                                                    });    
                                                    $(document).bind("click", function(e) {
                                                        var $clicked = $(e.target);
                                                        if (! $clicked.parents().hasClass("dropdown"))
                                                            $(".dropdown dd ul").hide();
                                                    });
                                                    $(".dropdown img.flag").toggleClass("flagvisibility");
                                                })
                                                </script>
                                                <form method="post" action="#" enctype="multipart/form-data">
                                                    <label for="woocommerce_epayco_enabled">
                                                      </label>
                                                      <input type="button" class="button-primary woocommerce-save-button upload" value="Subir">
                                                      
                                                  </form>  
                                                <br><br>'.
                                        $path  = '';
                                    $url_icon = plugin_dir_url(__FILE__)."lib";
                                    $dir_ = __DIR__."/lib";
                                    if(is_dir($dir_)) {
                                        try {
                                            $gestor = opendir($dir_);
                                            if($gestor){
                                                while (($image = readdir($gestor)) !== false){
                                                    if($image != '.' && $image != '..'){
                                                        if($image == "epayco.png"){
                                                            $image_ = $url_icon."/".$image;
                                                            echo "<img class='card-img-top' src='$image_' width='400px'/><br>";
                                                        }
                                                    }
                                                }
                                            }
                                        }catch (Exception $e){
                                            __return_null();
                                        }
                                    }'.
                                            </fieldset>
                                          </td>
                                        </tr>';
                                else :
                                    if ( is_admin() && ! defined( 'DOING_AJAX')) {
                                        echo '<div class="error"><p><strong>' . __( 'ePayco: cambio de logo', 'epayco-woocommerce' ) . '</strong>: ' . sprintf(__('%s', 'epayco-woocommerce' ), '<a href="' . admin_url() . 'admin.php?page=wc-settings&tab=general#s2id_woocommerce_currency">' . __( 'Click aquí para configurar!', 'epayco-woocommerce') . '</a>' ) . '</p></div>';
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
                    'epayco_cancelled_endorder_state' => array(
                        'title' => __('Estado Cancelado del Pedido', 'epayco_woocommerce'),
                        'type' => 'select',
                        'css' =>'line-height: inherit',
                        'description' => __('Seleccione el estado del pedido que se aplicará cuando la transacción es cancelar o rechazada', 'epayco_woocommerce'),
                        'options' => array(
                            'epayco-cancelled'=>"ePayco Pago Cancelado",
                            "epayco-failed"=>"ePayco Pago Fallido",
                            'cancelled'=>"Cancelado",
                            "failed"=>"Fallido"
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
                    'epayco_privatekey' => array(
                        'title' => __('<span class="epayco-required">PRIVATE_KEY</span>', 'epayco_woocommerce'),
                        'type' => 'text',
                        'description' => __('LLave para autenticar y consumir los servicios de ePayco, Proporcionado en su panel de clientes en la opción configuración.', 'epayco_woocommerce'),
                        'default' => '',
                        'placeholder' => ''
                    ),
                    'force_redirect' => array(
                        'title' => __('Habilitar redirección al cierre del checkout', 'epayco_woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Habilitar redirección de pagador a URL de respuesta en caso de que cierre el Checkout', 'epayco_woocommerce'),
                        'description' => __('Habilite si desea que el usuario pagador al cancelar la transacción o cerrar el checkout sea redirigido a la URL de respuesta configurada.', 'epayco_woocommerce'),
                        'default' => 'no',
                    ),
                    'clear_cart' => array(
                        'title' => __('Habilitar vaciado de carrito', 'epayco_woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Habilitar vaciado de carrito de compras cuando la transacción no quede en estado aprobado o pendiente', 'epayco_woocommerce'),
                        'description' => __('Habilite si desea que el carrito de compras quede vacio cuando la transaccion quede en estado no aprobado', 'epayco_woocommerce'),
                        'default' => 'no',
                    ),
                    'epayco_split_payment' => array(
                        'title' => __('Habilitar splitpayment', 'epayco_woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Habilitar splitpayment', 'epayco_woocommerce'),
                        'description' => __('Habilitar splitpayment', 'epayco_woocommerce'),
                        'default' => 'no',
                    ),
                    'epayco_split_payment_type' => array(
                        'title' => __('Tipo de splitpayment', 'epayco_woocommerce'),
                        'type' => 'select',
                        'css' =>'line-height: inherit',
                        'description' => __('Seleccione el tipo de splitpayment', 'epayco_woocommerce'),
                        'options' => array('01' => 'fija','02' => 'porcentaje'),
                    ),
                    'alg_wc_custom_order_numbers_enabled' => array(
                        'title'    => __( 'WooCommerce Custom Order Numbers', 'epayco_woocommerce' ),
                        'desc'     => '<strong>' . __( 'Enable plugin', 'epayco_woocommerce' ) . '</strong>',
                        'desc_tip' => __( 'Custom Order Numbers for WooCommerce.', 'epayco_woocommerce' ),
                        'id'       => 'alg_wc_custom_order_numbers_enabled',
                        'default'  => 'yes',
                        'type'     => 'checkbox',
                    ),
                    'alg_wc_custom_order_numbers_prefix' => array(
                        'title'    => __( 'Order number custom prefix', 'epayco_woocommerce' ),
                        'desc_tip' => __( 'Prefix before order number (optional). This will change the prefixes for all existing orders.', 'epayco_woocommerce' ),
                        'id'       => 'alg_wc_custom_order_numbers_prefix',
                        'default'  => '',
                        'type'     => 'text',
                    )
                );
            }

            /**
             * @param $order_id
             * @return array
             */
            public function process_payment($order_id)
            {
                $order = new WC_Order($order_id);
                $order_id_ = $order->get_id();
                $order_key = $order->get_order_key();
                if (version_compare( WOOCOMMERCE_VERSION, '2.1', '>=')) {
                    return array(
                        'result'    => 'success',
                        'redirect'  => add_query_arg('order-pay',  $order_id_, add_query_arg('key', $order_key, wc_get_checkout_url()))
                    );
                } else {
                    return array(
                        'result'    => 'success',
                        'redirect'  => add_query_arg('order',  $order_id_, add_query_arg('key', $order_key, wc_get_checkout_url()))
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
                $receiversData = [];
                $isProductoWhitSplit=false;
                $totalSplitAmount=0;
                $tax=$order->get_total_tax();
                $iva=0;
                $ico=0;
                foreach($order->get_items('tax') as $item_id => $item ) {
                    if( strtolower( $item->get_label() ) == 'iva' ){
                        $iva = round($item->get_tax_total(),2);
                    }
                    if( strtolower( $item->get_label() ) == 'ico'){
                        $ico = round($item->get_tax_total(),2);
                    }
                }
                if($ico ==0 && $iva==0){
                    $iva = round($order->get_total_tax(),2);
                }
                if($ico == 0 && $iva !=0){
                    $iva = round($order->get_total_tax(),2);
                }
                if($ico != 0 && $iva ==0){
                    $ico = round($order->get_total_tax(),2);
                }
                foreach( $order->get_items( 'shipping' ) as $item_id => $item ){
                    $item_data = $item->get_data();
                    $shipping_data_total = $item_data['total'];
                    $shipping_data_taxes        = $item_data['taxes'];

                }
                $post_metas = get_post_meta(get_the_ID());
                $isSplit = $this->split_payment == "yes";
                foreach ($order->get_items() as $product) {
                    $epayco_p_cust_id_client = get_post_meta( $product["product_id"], 'p_cust_id_client' );
                    if ( !empty($epayco_p_cust_id_client[0]) && $isSplit ) {
                        $isProductoWhitSplit = true;
                        $totalSplitAmount=$totalSplitAmount+floatval($product['total']);
                        //$epayco_tipe_split= get_post_meta( $product["product_id"], 'epayco_ext' )[0];
                        $epayco_tipe_split= $this->epayco_split_payment_type;
                        if($epayco_tipe_split == '01'){
                            if($epayco_p_cust_id_client[0] != ""){
                                $receiversa['id'] = $epayco_p_cust_id_client[0];
                                $epayco_super_product = get_post_meta($product["product_id"], '_super_product');
                                $epayco_epayco_comition = get_post_meta($product["product_id"], 'epayco_comition');
                                if ($epayco_super_product[0] != "yes") {
                                    $productTotalComision = floatval($epayco_epayco_comition[0]) * $product["quantity"];
                                    $receiversa['total'] = floatval($product['total']);
                                    $fee = $productTotalComision;
                                    $receiversa['iva'] = 0;
                                    $receiversa['base_iva'] = 0;
                                    $receiversa['fee'] = $fee;
                                } else {
                                    $receiversa['total'] = floatval($product['total']);
                                    $receiversa['iva'] = 0;
                                    $receiversa['base_iva'] = 0;
                                    $receiversa['fee'] = 0;
                                }
                                if($epayco_p_cust_id_client[0]) {
                                    array_push($receiversData, $receiversa);
                                }
                            }else{
                                $receiversa['id'] = $this->epayco_customerid;
                                $receiversa['total'] = floatval($product['total']);
                                $receiversa['iva'] = 0;
                                $receiversa['base_iva'] = 0;
                                $receiversa['fee'] = 0;
                                array_push($receiversData, $receiversa);
                            }
                        }else{
                            if($epayco_p_cust_id_client[0] != ""){
                                $receiversa['id'] = $epayco_p_cust_id_client[0];
                                $epayco_super_product = get_post_meta($product["product_id"], '_super_product');
                                $epayco_epayco_comition = get_post_meta($product["product_id"], 'epayco_comition');

                                if ($epayco_super_product[0] != "yes") {
                                    $productTotalComision = ((floatval($epayco_epayco_comition[0])  * floatval($product['total']))/100);
                                    $receiversa['total'] = floatval($product['total']);
                                    $fee = $productTotalComision;
                                    $receiversa['iva'] = 0;
                                    $receiversa['base_iva'] = 0;
                                    $receiversa['fee'] = $fee;
                                } else {
                                    $receiversa['total'] = floatval($product['total']);
                                    $receiversa['iva'] = 0;
                                    $receiversa['base_iva'] = 0;
                                    $receiversa['fee'] = 0;
                                }
                                if($epayco_p_cust_id_client[0]) {
                                    array_push($receiversData, $receiversa);
                                }
                            }else{
                                $receiversa['id'] = $this->epayco_customerid;
                                $receiversa['total'] = floatval($product['total']);
                                $receiversa['iva'] = 0;
                                $receiversa['base_iva'] = 0;
                                $receiversa['fee'] = 0;
                                array_push($receiversData, $receiversa);
                            }
                        }

                    }else{
                        $shipingTotal = floatval($product['total'])+$iva;
                        $shipingBase  = floatval($product['total']);
                        $shipingTax = $iva;

                        $receiver['id'] = $this->epayco_customerid;
                        $receiver['total'] = $shipingTotal;
                        $receiver['iva'] = $shipingTax;
                        $receiver['base_iva'] = $shipingBase;
                        $receiver['fee'] = 0;
                        array_push($receiversData, $receiver);
                    }
                    $clearData = str_replace('_', ' ', $this->string_sanitize($product['name']));
                    $descripcionParts[] = $clearData;

                }
                $isSplitProducto = false;
                if(floatval($totalSplitAmount) != floatval($base_tax)){
                    foreach ($receiversData as  $receiverinfo) {
                        if($receiverinfo["id"] == $this->epayco_customerid){
                            $isSplitProducto = true;
                        }
                    }
                    $receivers= [];
                    $receiversWithProduct= [];
                    $receiverTotal = 0;
                    $receiverTax = 0;
                    $receiverBase = 0;

                    foreach ($receiversData as  $k => $dato) {
                        if($dato["id"] == $this->epayco_customerid){
                            $receiverTotal+=$dato["total"];
                            $receiverTax+=$dato["iva"];
                            $receiverBase+=$dato["base_iva"];
                            $receiver['id'] = $this->epayco_customerid;
                            $receiver['total'] = $receiverTotal;
                            $receiver['iva'] = $receiverTax;
                            $receiver['base_iva'] = $receiverBase;
                            $receiver['fee'] = 0;
                        }
                    }
                    array_push($receivers, $receiver);
                    if($isSplitProducto){
                        foreach ($receiversData as  $k => $dato) {
                            if($dato["id"] != $this->epayco_customerid){
                                $receiver['id'] = $dato["id"];
                                $receiver['total'] = $dato["total"];
                                $receiver['iva'] = $dato["iva"];
                                $receiver['base_iva'] = $dato["base_iva"];
                                $receiver['fee'] = 0;
                                array_push($receiversWithProduct, $receiver);
                            }
                        }
                        $receiversData = [];
                        $receiver_= [];
                        foreach ($receivers as  $k => $dato) {
                            if($dato["id"] == $this->epayco_customerid){
                                $receiver_['id'] = $this->epayco_customerid;
                                $receiver_['total'] = $dato["total"]+floatval($shipping_data_total);
                                $receiver_['iva'] = $dato["iva"];
                                $receiver_['base_iva'] = $dato["base_iva"]+floatval($shipping_data_total);
                                $receiver_['fee'] = 0;
                            }
                        }
                        array_push($receiversData, $receiver_);

                    }else{
                        $receiversa['id'] = $this->epayco_customerid;
                        $receiversa['total'] = floatval($shipping_data_total)+$iva;
                        $receiversa['iva'] = 0;
                        $receiversa['base_iva'] = 0;
                        $receiversa['fee'] = 0;
                        array_push($receiversData, $receiversa);
                    }

                }
                if($isProductoWhitSplit){
                    $receivers = array_merge($receiversWithProduct, $receiversData);
                }else{
                    $receivers = $receiversData;
                }
                $split = 'false';
                $receiversInfo = [];
                if(count($receivers) < 2){
                    $custId = isset($receivers[0]['id']) ? $receivers[0]['id'] : null;
                    if($custId){
                        $split = 'true';
                    }
                }else{
                    foreach ($receivers as $key => $receiver) {
                        foreach ( $receivers[$key] as $customer){
                            if($customer === '')
                            {
                                unset($receivers[$key]);
                            }
                        }
                    }
                    if(count($receivers) > 0){
                        $split = 'true';
                    }
                }

                foreach ($receivers as  $receiver) {
                    array_push($receiversInfo, $receiver);
                }
                if(count($receiversInfo) > 0){
                    foreach ($receiversInfo as  $receiver) {
                        if($receiver["id"] == $this->epayco_customerid && !$isProductoWhitSplit ){
                            $split = 'false';
                        }else{
                            $split = 'true';
                        }
                    }

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


                //Busca si ya se restauro el stock
                if (!EpaycoOrder::ifExist($order_id)) {
                    //si no se restauro el stock restaurarlo inmediatamente
                    EpaycoOrder::create($order_id,1);
                    $this->restore_order_stock($order->get_id(),"decrease");
                }
                $force_redirect = $this->force_redirect == "yes" ? "true" : "false";

                if ($this->epayco_lang !== "es") {
                    $msgEpaycoCheckout = '<span class="animated-points">Loading payment methods</span>
                     <br><small class="epayco-subtitle"> If they do not load automatically, click on the "Pay with ePayco" button</small>';
                    $epaycoButtonImage = plugin_dir_url(__FILE__).'lib/Boton-color-Ingles.png';
                }else{
                    $msgEpaycoCheckout = '<span class="animated-points">Cargando métodos de pago</span>
                    <br><small class="epayco-subtitle"> Si no se cargan automáticamente, de clic en el botón "Pagar con ePayco</small>';
                    $epaycoButtonImage =  plugin_dir_url(__FILE__).'lib/Boton-color-espanol.png';
                }

                $order_number_meta = get_post_meta( $order_id, '_alg_wc_full_custom_order_number', true );
                echo sprintf('
                        <div class="loader-container">
                            <div class="loading"></div>
                        </div>
                        <p style="text-align: center;" class="epayco-title">
                           '.$msgEpaycoCheckout.'
                        </p>    
                        <div hidden id="split">'.$split.'</div>  
                        <div hidden id="response">'.$redirect_url.'</div>          
                        <center>
                        <a id="btn_epayco" href="#">
                            <img src="'.$epaycoButtonImage.'">
                            </a>
                        <form id="appGateway">
                            <script
                               src="https://epayco-checkout-testing.s3.amazonaws.com/checkout.preprod.js">
                            </script>
                            <script>
                            var handler = ePayco.checkout.configure({
                                key: "%s",
                                test: "%s"
                            })
                            var date = new Date().getTime();
                            var data = {
                                name: "%s",
                                description: "%s",
                                invoice: "%s",
                                currency: "%s",
                                amount: "%s",
                                tax_base: "%s",
                                tax: "%s",
                                ico: "%s",
                                country: "%s",
                                lang: "%s",
                                external: "%s",
                                confirmation: "%s",
                                response: "%s",
                                //Atributos cliente
                                name_billing: "%s",
                                address_billing: "%s",
                                email_billing: "%s",
                                mobilephone_billing: "%s",
                            }
                            
                            let split = document.getElementById("split").textContent;
                            if(split == "true"){
                                var js_array ='.json_encode($receiversInfo).';
                                let split_receivers = [];
                                for(var jsa of js_array){
                                    split_receivers.push({
                                        "id" :  jsa.id,
                                        "total": jsa.total,
                                        "iva" : jsa.iva,
                                        "base_iva": jsa.base_iva,
                                        "fee" : jsa.fee
                                    });
                                }
                                data.split_app_id= "%s", //Id de la cuenta principal
                                data.split_merchant_id= "%s", //Id de la cuenta principal y a nombre de quien quedara la transacción
                                data.split_type= "01", // tipo de dispersión 01 -> fija ---- 02 -> porcentual
                                data.split_primary_receiver= "%s", // Id de la cuenta principal - parámetro para recibir valor de la dispersión destinado
                                data.split_primary_receiver_fee= "0", // Parámetro no a utilizar pero que debe de ir en cero
                                data.splitpayment= "true", // Indicación de funcionalidad split
                                data.split_rule= "multiple", // Parámetro para configuración de Split_receivers - debe de ir por defecto en multiple
                                data.split_receivers= split_receivers
                            }
    
                            let responseUrl = document.getElementById("response").textContent;
                            handler.onCloseModal = function () {};
                            var isForceRedirect='.$force_redirect.';
                            if(isForceRedirect == true){
                                let responseUrl = document.getElementById("response").textContent;
                                handler.onCreated(function(response) {
                                }).onResponse(function(response) {
                                }).onClosed(function(response) {
                                    window.location.href = responseUrl
                                });
                            }
                               
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
                    $order->get_subtotal(),
                    $iva,
                    $ico,
                    $basedCountry,
                    $this->epayco_lang,
                    $external,
                    $confirm_url,
                    $redirect_url,
                    $name_billing,
                    $address_billing,
                    $email_billing,
                    $phone_billing,
                    $this->epayco_customerid,
                    $this->epayco_customerid,
                    $this->epayco_customerid
                );
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

            function validate_ePayco_request(){
                @ob_clean();
                if ( ! empty( $_REQUEST ) ) {
                    header( 'HTTP/1.1 200 OK' );
                    do_action( "ePayco_init_validation", $_REQUEST );
                } else {
                    wp_die( __("ePayco Request Failure", 'epayco-woocommerce') );
                }
            }

            function change_logo(){
                @ob_clean();
                if ( ! empty( $_REQUEST ) ) {
                    header( 'HTTP/1.1 200 OK' );
                    do_action( "ePayco_change_logo", $_REQUEST );
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
                $clear_cart = !($this->clear_cart == "yes");
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
                        $woocommerce->cart->empty_cart();
                        if($clear_cart){
                            foreach ($order->get_items() as $item) {
                                // Get an instance of corresponding the WC_Product object
                                $product_id = $item->get_product()->id;
                                $qty = $item->get_quantity(); // Get the item quantity
                                WC()->cart->add_to_cart( $product_id ,(int)$qty);
                            }
                            wp_safe_redirect( wc_get_checkout_url() );
                            exit();
                        }else{
                            if ($this->get_option('epayco_url_response' ) == 0) {
                                $redirect_url = $order->get_checkout_order_received_url();
                            } else {

                                $redirect_url = get_permalink($this->get_option('epayco_url_response'));
                            }
                        }
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
                                        $order->add_order_note($message);
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
                                    if($isTestMode=="true" && $current_state == "epayco_on_hold"){
                                        if($orderStatus == "processing"){
                                            $this->restore_order_stock($order->get_id(),"decrease");
                                        }
                                        if($orderStatus == "completed"){
                                            $this->restore_order_stock($order->get_id(),"decrease");
                                        }
                                    }
                                    if($isTestMode != "true" && $current_state == "epayco-on-hold"){
                                        if($orderStatus == "processing"){
                                            $this->restore_order_stock($order->get_id());
                                        }
                                        if($orderStatus == "completed"){
                                            $this->restore_order_stock($order->get_id());
                                        }
                                    }
                                    if($current_state =="pending")
                                    {
                                        $this->restore_order_stock($order->get_id());
                                    }
                                    $order->payment_complete($x_ref_payco);
                                    $order->update_status($orderStatus);
                                    $order->add_order_note($message);
                                }
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
                                    $message = 'Pago rechazado Prueba: ' .$x_ref_payco;
                                    $messageClass = 'woocommerce-error';
                                    $order->update_status($orderStatus);
                                    $order->add_order_note($message);
                                    if($current_state =="epayco-cancelled"||
                                        $current_state == $orderStatus ){
                                    }else{
                                        $this->restore_order_stock($order->get_id());
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
                                    $order->update_status($this->epayco_cancelled_endorder_state);
                                    $order->add_order_note($message);
                                    if($current_state !=$this->epayco_cancelled_endorder_state){
                                        $this->restore_order_stock($order->get_id());
                                    }
                                }
                            }
                            echo "2";
                            if(!$isConfirmation && $clear_cart){
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
                                if($current_state == "epayco_failed" ||
                                    $current_state == "epayco_cancelled" ||
                                    $current_state == "failed" ||
                                    $current_state == "epayco-cancelled" ||
                                    $current_state == "epayco-failed"
                                ){
                                    $this->restore_order_stock($order->get_id(),"decrease");
                                }
                            }
                            echo "3";
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
                                        $this->restore_order_stock($order->get_id());
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
                                        $this->restore_order_stock($order->get_id());
                                    }
                                }
                            }
                            echo "4";
                            if(!$isConfirmation && $clear_cart){
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
                        case 6: {
                            $message = 'Pago Reversada' .$x_ref_payco;
                            $messageClass = 'woocommerce-error';
                            $order->update_status('refunded');
                            $order->add_order_note('Pago Reversado');
                            $this->restore_order_stock($order->get_id());
                            echo "6";
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
                                        $this->restore_order_stock($order->get_id());
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
                                        $this->restore_order_stock($order->get_id());
                                    }
                                }
                            }
                            echo "10";
                            if(!$isConfirmation && $clear_cart){
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
                                        if($current_state != "epayco-failed"){
                                            $this->restore_order_stock($order->get_id());
                                        }
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
                                    if($current_state !="epayco-cancelled" && $current_state != "epayco-failed"){
                                        $this->restore_order_stock($order->get_id());
                                    }
                                }
                            }
                            echo "11";
                            if(!$isConfirmation && $clear_cart){
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
                                    $order->add_order_note($message);
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


            function ePayco_new_logo()
            {
                $file = sanitize_text_field($_FILES);
                if(empty($file)){
                    $file = $_FILES;
                }
                if (is_array($file) && count($file) > 0) {
                    if (($file["file"]["type"] == "image/pjpeg")
                        || ($file["file"]["type"] == "image/jpeg")
                        || ($file["file"]["type"] == "image/png")
                        || ($file["file"]["type"] == "image/gif")) {

                        $nombre = $file['file']['name'];
                        $strpos = strpos($nombre, '.');
                        $strlen = strlen($nombre);
                        $posicion = $strlen - $strpos;
                        $typeImage = substr($nombre, -$posicion);
                        $typeImage = '.png';
                        $oldImageName = stristr($nombre, $typeImage,$posicion);
                        $newImageName = 'epayco'.$typeImage;

                        $newPath = __DIR__."/lib";
                        $gestor  = opendir($newPath);
                        if($gestor){
                            while (($image = readdir($gestor)) !== false){
                                if($image != '.' && $image != '..'){
                                    $strpos_image = strpos($image, '.');
                                    $strlen_image = strlen($image);
                                    $posicion_image = $strlen_image - $strpos_image;
                                    $type_image = substr($image, - $posicion_image);
                                    $name_image = substr($image,  0,$strpos_image);
                                    if($name_image == "epayco"){
                                        unlink($newPath."/".$image);
                                    }
                                }
                            }
                        }
                        if (move_uploaded_file($file["file"]["tmp_name"], $newPath."/".$newImageName)) {
                            $newPath =plugin_dir_url(__FILE__).'lib/epayco.png';
                            esc_html_e( $newPath , 'text_domain' );
                        } else {
                            esc_html_e(0, 'text_domain' );
                        }
                    } else {
                        esc_html_e(0, 'text_domain' );
                    }
                } else {
                    esc_html_e(0, 'text_domain' );
                }
                exit();
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
                wp_enqueue_script('epayco',  'https://epayco-checkout-testing.s3.amazonaws.com/checkout.preprod.js', array(), $this->version, null);
            }


            /* Enqueue JS script for showing fields as per the changes made in the settings.
            *
            * @version 1.3.0
            * @since   1.3.0
            */
            public static function alg_custom_order_numbers_setting_script() {
                $plugin_url       = plugins_url() . '/Plugin_ePayco_WooCommerce';
                $numbers_instance = alg_wc_custom_order_numbers();
                wp_enqueue_script(
                    'con_dismiss_notice',
                    $plugin_url . '/includes/js/con-dismiss-notice.js',
                    '',
                    $numbers_instance->version,
                    false
                );
                wp_localize_script(
                    'con_dismiss_notice',
                    'con_dismiss_param',
                    array(
                        'ajax_url' => admin_url( 'admin-ajax.php' ),
                    )
                );
            }
            /**
             * Check if HPOS is enabled or not.
             *
             * @since 1.8.0
             * return boolean true if enabled else false
             */
            public function con_wc_hpos_enabled() {
                if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
                    if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
                        return true;
                    }
                }
                return false;
            }

            /**
             * Function to show the admin notice to update the old CON meta key in the database when the plugin is updated.
             *
             * @version 1.3.0
             * @since   1.3.0
             */
            public static function alg_custom_order_numbers_update_admin_notice() {
                global $current_screen;
                $ts_current_screen = get_current_screen();
                // Return when we're on any edit screen, as notices are distracting in there.
                if ( ( method_exists( $ts_current_screen, 'is_block_editor' ) && $ts_current_screen->is_block_editor() ) || ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) ) {
                    return;
                }
                if ( 'yes' === get_option( 'alg_custom_order_numbers_show_admin_notice', '' ) ) {
                    if ( '' === get_option( 'alg_custom_order_numbers_update_database', '' ) ) {
                        ?>
                        <div class=''>
                            <div class="con-lite-message notice notice-info" style="position: relative;">
                                <p style="margin: 10px 0 10px 10px; font-size: medium;">
                                    <?php
                                    echo esc_html_e( 'From version 1.3.0, you can now search the orders by custom order numbers on the Orders page. In order to make the previous orders with custom order numbers searchable on Orders page, we need to update the database. Please click the "Update Now" button to do this. The database update process will run in the background.', 'epayco_woocommerce' );
                                    ?>
                                </p>
                                <p class="submit" style="margin: -10px 0 10px 10px;">
                                    <a class="button-primary button button-large" id="con-lite-update" href="edit.php?post_type=shop_order&action=alg_custom_order_numbers_update_old_con_in_database"><?php esc_html_e( 'Update Now', 'epayco_woocommerce' ); ?></a>
                                </p>
                            </div>
                        </div>
                        <?php
                    }
                }
                if ( 'yes' !== get_option( 'alg_custom_order_numbers_no_meta_admin_notice', '' ) ) {
                    if ( 'yes' === get_option( 'alg_custom_order_number_old_orders_to_update_meta_key', '' ) ) {
                        if ( '' === get_option( 'alg_custom_order_numbers_update_meta_key_in_database', '' ) ) {
                            ?>
                            <div class=''>
                                <div class="con-lite-message notice notice-info" style="position: relative;">
                                    <p style="margin: 10px 0 10px 10px; font-size: medium;">
                                        <?php
                                        echo esc_html_e( 'In order to make the previous orders searchable on Orders page where meta key of the custom order number is not present, we need to update the database. Please click the "Update Now" button to do this. The database update process will run in the background.', 'epayco_woocommerce' );
                                        ?>
                                    </p>
                                    <p class="submit" style="margin: -10px 0 10px 10px;">
                                        <a class="button-primary button button-large" id="con-lite-update" href="edit.php?post_type=shop_order&action=alg_custom_order_numbers_update_old_con_with_meta_key"><?php esc_html_e( 'Update Now', 'epayco_woocommerce' ); ?></a>
                                    </p>
                                </div>
                            </div>
                            <?php
                        }
                    }
                }
            }

            /**
             * Function to add a scheduled action when Update now button is clicked in admin notice.AS will run every 5 mins and will run the script to update the CON meta value in old orders.
             *
             * @version 1.3.0
             * @since   1.3.0
             */
            public function alg_custom_order_numbers_add_recurring_action() {
                if ( isset( $_REQUEST['action'] ) && 'alg_custom_order_numbers_update_old_con_in_database' === $_REQUEST['action'] ) { // phpcs:ignore
                    update_option( 'alg_custom_order_numbers_update_database', 'yes' );
                    $current_time = current_time( 'timestamp' ); // phpcs:ignore
                    update_option( 'alg_custom_order_numbers_time_of_update_now', $current_time );
                    if ( function_exists( 'as_next_scheduled_action' ) ) { // Indicates that the AS library is present.
                        as_schedule_recurring_action( time(), 300, 'alg_custom_order_numbers_update_old_custom_order_numbers' );
                    }
                    wp_safe_redirect( admin_url( 'edit.php?post_type=shop_order' ) );
                    exit;
                }
            }

            /**
             * Function to add a scheduled action when Update now button is clicked in admin notice.AS will run every 5 mins and will run the script to add the meta key of CON in old orders where it is missing.
             *
             * @version 1.3.0
             * @since   1.3.0
             */
            public function alg_custom_order_numbers_add_recurring_action_to_add_meta_key() {
                if ( isset( $_REQUEST['action'] ) && 'alg_custom_order_numbers_update_old_con_with_meta_key' === $_REQUEST['action'] ) { // phpcs:ignore
                    update_option( 'alg_custom_order_numbers_update_meta_key_in_database', 'yes' );
                    $current_time = current_time( 'timestamp' ); // phpcs:ignore
                    update_option( 'alg_custom_order_numbers_meta_key_time_of_update_now', $current_time );
                    if ( function_exists( 'as_next_scheduled_action' ) ) { // Indicates that the AS library is present.
                        as_schedule_recurring_action( time(), 300, 'alg_custom_order_numbers_update_meta_key_in_old_con' );
                    }
                    wp_safe_redirect( admin_url( 'edit.php?post_type=shop_order' ) );
                    exit;
                }
            }

            /**
             * Callback function for the AS to run the script to update the CON meta value for the old orders.
             *
             * @version 1.3.0
             * @since   1.3.0
             */
            public function alg_custom_order_numbers_update_old_custom_order_numbers_callback() {
                $args        = array(
                    'post_type'      => 'shop_order',
                    'posts_per_page' => 10000, // phpcs:ignore
                    'post_status'    => 'any',
                    'meta_query'     => array( // phpcs:ignore
                        'relation' => 'AND',
                        array(
                            'key'     => '_alg_wc_custom_order_number',
                            'compare' => 'EXISTS',
                        ),
                        array(
                            'key'     => '_alg_wc_custom_order_number_updated',
                            'compare' => 'NOT EXISTS',
                        ),
                    ),
                );
                $loop_orders = new WP_Query( $args );
                if ( ! $loop_orders->have_posts() ) {
                    update_option( 'alg_custom_order_numbers_no_old_orders_to_update', 'yes' );
                    return;
                }
                foreach ( $loop_orders->posts as $order_ids ) {
                    $order_id = $order_ids->ID;
                    if ( $this->con_wc_hpos_enabled() ) {
                        $order_number_meta = get_meta( '_alg_wc_custom_order_number' );
                    } else {
                        $order_number_meta = get_post_meta( $order_id, '_alg_wc_custom_order_number', true );
                    }
                    if ( '' === $order_number_meta ) {
                        $order_number_meta = $order_id;
                    }
                    $is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
                    $order                 = wc_get_order( $order_id );
                    $order_timestamp       = strtotime( ( $is_wc_version_below_3 ? $order->order_date : $order->get_date_created() ) );
                    $time                  = get_option( 'alg_custom_order_numbers_time_of_update_now', '' );
                    if ( $order_timestamp > $time ) {
                        return;
                    }
                    $con_order_number = apply_filters(
                        'alg_wc_custom_order_numbers',
                        sprintf( '%s%s', do_shortcode( $this->alg_wc_custom_order_numbers_prefix ), $order_number_meta ),
                        'value',
                        array(
                            'order_timestamp'   => $order_timestamp,
                            'order_number_meta' => $order_number_meta,
                        )
                    );
                    if ( $this->con_wc_hpos_enabled() ) {
                        $order->update_meta_data( '_alg_wc_full_custom_order_number', $con_order_number );
                        $order->update_meta_data( '_alg_wc_custom_order_number_updated', 1 );
                        $order->save();
                    } else {
                        update_post_meta( $order_id, '_alg_wc_full_custom_order_number', $con_order_number );
                        update_post_meta( $order_id, '_alg_wc_custom_order_number_updated', 1 );
                    }
                }
                $loop_old_orders = $this->alg_custom_order_number_old_orders_without_meta_key_data();
                if ( '' === $loop_old_orders ) {
                    update_option( 'alg_custom_order_numbers_no_old_orders_to_update', 'yes' );
                    return;
                }
                foreach ( $loop_old_orders->posts as $order_ids ) {
                    $order_id              = $order_ids->ID;
                    $order_number_meta     = $order_id;
                    $is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
                    $order                 = wc_get_order( $order_id );
                    $order_timestamp       = strtotime( ( $is_wc_version_below_3 ? $order->order_date : $order->get_date_created() ) );
                    $time                  = get_option( 'alg_custom_order_numbers_meta_key_time_of_update_now', '' );
                    if ( $order_timestamp > $time ) {
                        return;
                    }
                    $con_order_number = apply_filters(
                        'alg_wc_custom_order_numbers',
                        sprintf( '%s%s', do_shortcode( $this->alg_wc_custom_order_numbers_prefix ), $order_number_meta ),
                        'value',
                        array(
                            'order_timestamp'   => $order_timestamp,
                            'order_number_meta' => $order_number_meta,
                        )
                    );
                    if ( $this->con_wc_hpos_enabled() ) {
                        $order->update_meta_data( '_alg_wc_full_custom_order_number', $con_order_number );
                        $order->update_meta_data( '_alg_wc_custom_order_number_meta_key_updated', 1 );
                        $order->save();
                    } else {
                        update_post_meta( $order_id, '_alg_wc_full_custom_order_number', $con_order_number );
                        update_post_meta( $order_id, '_alg_wc_custom_order_number_meta_key_updated', 1 );
                    }
                }
                if ( 10000 > count( $loop_orders->posts ) && 500 > count( $loop_old_orders->posts ) ) {
                    update_option( 'alg_custom_order_numbers_no_old_orders_to_update', 'yes' );
                }
            }

            /**
             * Callback function for the AS to run the script to add the CON meta key for the old orders where it is missing.
             */
            public function alg_custom_order_numbers_update_meta_key_in_old_con_callback() {
                $loop_orders = $this->alg_custom_order_number_old_orders_without_meta_key_data();
                if ( '' === $loop_orders ) {
                    update_option( 'alg_custom_order_number_no_old_con_without_meta_key', 'yes' );
                    return;
                }
                foreach ( $loop_orders->posts as $order_ids ) {
                    $order_id              = $order_ids->ID;
                    $order_number_meta     = $order_id;
                    $is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
                    $order                 = wc_get_order( $order_id );
                    $order_timestamp       = strtotime( ( $is_wc_version_below_3 ? $order->order_date : $order->get_date_created() ) );
                    $time                  = get_option( 'alg_custom_order_numbers_meta_key_time_of_update_now', '' );
                    if ( $order_timestamp > $time ) {
                        return;
                    }
                    $con_order_number = apply_filters(
                        'alg_wc_custom_order_numbers',
                        sprintf( '%s%s', do_shortcode( $this->alg_wc_custom_order_numbers_prefix ), $order_number_meta ),
                        'value',
                        array(
                            'order_timestamp'   => $order_timestamp,
                            'order_number_meta' => $order_number_meta,
                        )
                    );
                    if ( $this->con_wc_hpos_enabled() ) {
                        $order->update_meta_data( '_alg_wc_full_custom_order_number', $con_order_number );
                        $order->update_meta_data( '_alg_wc_custom_order_number_meta_key_updated', 1 );
                        $order->save();
                    } else {
                        update_post_meta( $order_id, '_alg_wc_full_custom_order_number', $con_order_number );
                        update_post_meta( $order_id, '_alg_wc_custom_order_number_meta_key_updated', 1 );
                    }
                }
                if ( 500 > count( $loop_orders->posts ) ) {
                    update_option( 'alg_custom_order_number_no_old_con_without_meta_key', 'yes' );
                }
            }

            /**
             * Function to get the old orders where CON meta key is missing.
             */
            public function alg_custom_order_number_old_orders_without_meta_key() {
                if ( 'yes' !== get_option( 'alg_custom_order_number_no_old_con_without_meta_key', '' ) && 'yes' !== get_option( 'alg_custom_order_number_no_old_orders_to_update_meta_key', '' ) ) {
                    $args        = array(
                        'post_type'      => 'shop_order',
                        'posts_per_page' => 1, // phpcs:ignore
                        'post_status'    => 'any',
                        'meta_query'     => array( // phpcs:ignore
                            'relation' => 'AND',
                            array(
                                'key'     => '_alg_wc_custom_order_number',
                                'compare' => 'NOT EXISTS',
                            ),
                            array(
                                'key'     => '_alg_wc_custom_order_number_meta_key_updated',
                                'compare' => 'NOT EXISTS',
                            ),
                        ),
                    );
                    $loop_orders = new WP_Query( $args );
                    update_option( 'alg_custom_order_number_no_old_orders_to_update_meta_key', 'yes' );
                    if ( ! $loop_orders->have_posts() ) {
                        return '';
                    } else {
                        update_option( 'alg_custom_order_number_old_orders_to_update_meta_key', 'yes' );
                        return $loop_orders;
                    }
                }
            }

            /**
             * Function to get the old orders data where CON meta key is missing.
             */
            public function alg_custom_order_number_old_orders_without_meta_key_data() {
                $args        = array(
                    'post_type'      => 'shop_order',
                    'posts_per_page' => 500, // phpcs:ignore
                    'post_status'    => 'any',
                    'meta_query'     => array( // phpcs:ignore
                        'relation' => 'AND',
                        array(
                            'key'     => '_alg_wc_custom_order_number',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key'     => '_alg_wc_custom_order_number_meta_key_updated',
                            'compare' => 'NOT EXISTS',
                        ),
                    ),
                );
                $loop_orders = new WP_Query( $args );
                if ( ! $loop_orders->have_posts() ) {
                    return '';
                } else {
                    return $loop_orders;
                }
            }

            /**
             * Stop AS when there are no old orders left to update the CON meta key.
             *
             * @version 1.3.0
             * @since   1.3.0
             */
            public static function alg_custom_order_numbers_stop_recurring_action() {
                if ( 'yes' === get_option( 'alg_custom_order_numbers_no_old_orders_to_update', '' ) ) {
                    as_unschedule_all_actions( 'alg_custom_order_numbers_update_old_custom_order_numbers' );
                }
                if ( 'yes' === get_option( 'alg_custom_order_number_no_old_con_without_meta_key', '' ) ) {
                    as_unschedule_all_actions( 'alg_custom_order_numbers_update_meta_key_in_old_con' );
                }
            }

            /**
             * Function to show the Success Notice when all the old orders CON meta value are updated.
             *
             * @version 1.3.0
             * @since   1.3.0
             */
            public function alg_custom_order_numbers_update_success_notice() {
                if ( 'yes' === get_option( 'alg_custom_order_numbers_no_old_orders_to_update', '' ) ) {
                    if ( 'dismissed' !== get_option( 'alg_custom_order_numbers_success_notice', '' ) ) {
                        ?>
                        <div>
                            <div class="con-lite-message con-lite-success-message notice notice-success is-dismissible" style="position: relative;">
                                <p>
                                    <?php
                                    echo esc_html_e( 'Database updated successfully. In addition to new orders henceforth, you can now also search the old orders on Orders page with the custom order numbers.', 'epayco_woocommerce' );
                                    ?>
                                </p>
                            </div>
                        </div>
                        <?php
                    }
                }
                if ( 'yes' !== get_option( 'alg_custom_order_numbers_no_meta_admin_notice', '' ) ) {
                    if ( 'yes' === get_option( 'alg_custom_order_number_no_old_con_without_meta_key', '' ) ) {
                        if ( 'dismissed' !== get_option( 'alg_custom_order_numbers_success_notice_for_meta_key', '' ) ) {
                            ?>
                            <div>
                                <div class="con-lite-message con-lite-meta-key-success-message notice notice-success is-dismissible" style="position: relative;">
                                    <p>
                                        <?php
                                        echo esc_html_e( 'Database updated successfully. In addition to new orders henceforth, you can now also search the old orders on Orders page with the custom order numbers.', 'epayco_woocommerce' );
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <?php
                        }
                    }
                }
            }

            /**
             * Function to dismiss the admin notice.
             *
             * @version 1.3.0
             * @since   1.3.0
             */
            public function alg_custom_order_numbers_admin_notice_dismiss() {
                $admin_choice = isset( $_POST['admin_choice'] ) ? sanitize_text_field( wp_unslash( $_POST['admin_choice'] ) ) : ''; // phpcs:ignore
                update_option( 'alg_custom_order_numbers_success_notice', $admin_choice );
            }

            /**
             * Function to dismiss the admin notice.
             */
            public function alg_custom_order_numbers_admin_meta_key_notice_dismiss() {
                $admin_choice = isset( $_POST['alg_admin_choice'] ) ? sanitize_text_field( wp_unslash( $_POST['alg_admin_choice'] ) ) : ''; // phpcs:ignore
                update_option( 'alg_custom_order_numbers_success_notice_for_meta_key', $admin_choice );
            }

            /**
             * Function to update the prefix in the databse when settings are saved.
             *
             * @version 1.3.0
             * @since   1.3.0
             */
            public function woocommerce_settings_save_alg_wc_custom_order_numbers_callback() {
                if ( '1' === get_option( 'alg_wc_custom_order_numbers_prefix_suffix_changed' ) ) {
                    $args        = array(
                        'post_type'      => 'shop_order',
                        'post_status'    => 'any',
                        'posts_per_page' => -1,
                    );
                    $loop_orders = new WP_Query( $args );
                    if ( ! $loop_orders->have_posts() ) {
                        update_option( 'alg_wc_custom_order_numbers_prefix_suffix_changed', '' );
                        return;
                    }
                    foreach ( $loop_orders->posts as $order_ids ) {
                        $order_id = $order_ids->ID;
                        $order    = wc_get_order( $order_id );
                        if ( $this->con_wc_hpos_enabled() ) {
                            $order_number_meta = $order->get_meta( '_alg_wc_custom_order_number' );
                        } else {
                            $order_number_meta = get_post_meta( $order_id, '_alg_wc_custom_order_number', true );
                        }
                        if ( '' === $order_number_meta ) {
                            $order_number_meta = $order_id;
                        }
                        $is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
                        $order_timestamp       = strtotime( ( $is_wc_version_below_3 ? $order->order_date : $order->get_date_created() ) );
                        $full_order_number     = apply_filters(
                            'alg_wc_custom_order_numbers',
                            sprintf( '%s%s', do_shortcode( $this->alg_wc_custom_order_numbers_prefix ), $order_number_meta ),
                            'value',
                            array(
                                'order_timestamp'   => $order_timestamp,
                                'order_number_meta' => $order_number_meta,
                            )
                        );
                        if ( $this->con_wc_hpos_enabled() ) {
                            $order->update_meta_data( '_alg_wc_full_custom_order_number', $full_order_number );
                            $order->save();
                        } else {
                            update_post_meta( $order_id, '_alg_wc_full_custom_order_number', $full_order_number );
                        }
                        update_option( 'alg_wc_custom_order_numbers_prefix_suffix_changed', '' );
                    }
                }
            }

            /**
             * Maybe_reset_sequential_counter.
             *
             * @param string $current_order_number - Current custom Order Number.
             * @param int    $order_id - WC Order ID.
             *
             * @version 1.2.2
             * @since   1.1.2
             * @todo    [dev] use MySQL transaction
             */
            public function maybe_reset_sequential_counter( $current_order_number, $order_id ) {
                return $current_order_number;
            }

            /**
             * Save_order_number_meta_box.
             *
             * @param int      $post_id - Order ID.
             * @param WC_Order $post - Post Object.
             * @version 1.1.1
             * @since   1.1.1
             */
            public function save_order_number_meta_box( $post_id, $post ) {
                if ( ! isset( $_POST['alg_wc_custom_order_numbers_meta_box'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
                    return;
                }

                if ( isset( $_POST['alg_wc_custom_order_number'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
                    $is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
                    $order                 = wc_get_order( $post_id );
                    $order_timestamp       = strtotime( ( $is_wc_version_below_3 ? $order->order_date : $order->get_date_created() ) );
                    $current_order_number  = '';
                    if ( isset( $_POST['alg_wc_custom_order_number'] ) ) { // phpcs:ignore
                        $current_order_number = sanitize_text_field( wp_unslash( $_POST['alg_wc_custom_order_number'] ) ); // phpcs:ignore
                    }
                    $full_custom_order_number = apply_filters(
                        'alg_wc_custom_order_numbers',
                        sprintf( '%s%s', do_shortcode( $this->alg_wc_custom_order_numbers_prefix ), $current_order_number ),
                        'value',
                        array(
                            'order_timestamp'   => $order_timestamp,
                            'order_number_meta' => $current_order_number,
                        )
                    );
                    if ( $this->con_wc_hpos_enabled() ) {
                        $order->update_meta_data( '_alg_wc_custom_order_number', $current_order_number );
                        $order->update_meta_data( '_alg_wc_full_custom_order_number', $full_custom_order_number );
                        $order->save();
                    } else {
                        update_post_meta( $post_id, '_alg_wc_custom_order_number', $current_order_number );
                        update_post_meta( $post_id, '_alg_wc_full_custom_order_number', $full_custom_order_number );
                    }
                }
            }

            /**
             * Add_order_number_meta_box.
             *
             * @version 1.1.1
             * @since   1.1.1
             */
            public function add_order_number_meta_box() {
                if ( $this->con_wc_hpos_enabled() ) {
                    add_meta_box(
                        'alg-wc-custom-order-numbers-meta-box',
                        __( 'Order Number', 'epayco_woocommerce' ),
                        array( $this, 'create_order_number_meta_box' ),
                        wc_get_page_screen_id( 'shop-order' ),
                        'side',
                        'low'
                    );

                } else {
                    add_meta_box(
                        'alg-wc-custom-order-numbers-meta-box',
                        __( 'Order Number', 'epayco_woocommerce' ),
                        array( $this, 'create_order_number_meta_box' ),
                        'shop_order',
                        'side',
                        'low'
                    );
                }
            }

            /**
             * Create_order_number_meta_box.
             *
             * @version 1.1.1
             * @since   1.1.1
             */
            public function create_order_number_meta_box() {
                if ( $this->con_wc_hpos_enabled() ) {
                    $order = wc_get_order( get_the_ID() );
                    $meta  = $order->get_meta( '_alg_wc_custom_order_number' );
                } else {
                    $meta = get_post_meta( get_the_ID(), '_alg_wc_custom_order_number', true );
                }
                ?>
                <input type="number" name="alg_wc_custom_order_number" style="width:100%;" value="<?php echo esc_attr( $meta ); ?>">
                <input type="hidden" name="alg_wc_custom_order_numbers_meta_box">
                <?php
            }

            /**
             * Renumerate orders function.
             *
             * @version 1.1.2
             * @since   1.0.0
             */
            public function renumerate_orders() {
                $total_renumerated = 0;
                $last_renumerated  = 0;
                $offset            = 0;
                $block_size        = 512;
                while ( true ) {
                    $args        = array(
                        'type'    => array( 'shop_order', 'shop_subscription' ),
                        'status'  => 'any',
                        'limit'   => $block_size,
                        'orderby' => 'date',
                        'order'   => 'ASC',
                        'offset'  => $offset,
                        'return'  => 'ids',
                    );
                    $loop_orders = wc_get_orders( $args );
                    if ( count( $loop_orders ) <= 0 ) {
                        break;
                    }
                    foreach ( $loop_orders as $order_id ) {
                        $last_renumerated = $this->add_order_number_meta( $order_id, true );
                        $total_renumerated++;
                    }
                    $offset += $block_size;
                }
                return array( $total_renumerated, $last_renumerated );
            }

            /**
             * Function search_by_custom_number.
             *
             * @param array $metakeys Array of the metakeys to search order numbers on shop order page.
             * @version 1.3.0
             * @since   1.3.0
             */
            public function search_by_custom_number( $metakeys ) {
                $metakeys[] = '_alg_wc_full_custom_order_number';
                $metakeys[] = '_alg_wc_custom_order_number';
                return $metakeys;
            }

            /**
             * Display order number.
             *
             * @param string $order_number - Custom Order Number.
             * @param object $order - WC_Order object.
             *
             * @version 1.2.1
             * @since   1.0.0
             */
            public function display_order_number( $order_number, $order ) {
                $is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
                $order_id              = ( $is_wc_version_below_3 ? $order->id : $order->get_id() );
                $order_timestamp       = strtotime( ( $is_wc_version_below_3 ? $order->order_date : $order->get_date_created() ) );
                $con_wc_hpos_enabled   = $this->con_wc_hpos_enabled();
                if ( 'yes' !== get_option( 'alg_custom_order_numbers_show_admin_notice', '' ) || 'yes' === get_option( 'alg_custom_order_numbers_no_old_orders_to_update', '' ) ) {
                    // This code of block is added to update the meta key '_alg_wc_full_custom_order_number' in the subscription orders as the order numbers were getting changed after the database update.
                    if ( $con_wc_hpos_enabled ) {
                        $subscription_orders_updated = $order->get_meta( 'subscription_orders_updated' );
                    } else {
                        $subscription_orders_updated = get_post_meta( $order_id, 'subscription_orders_updated', true );
                    }
                    if ( 'yes' !== $subscription_orders_updated ) {
                        if ( $con_wc_hpos_enabled ) {
                            $post_type = OrderUtil::get_order_type( $order_id );
                        } else {
                            $post_type = get_post_type( $order_id );
                        }
                        if ( 'shop_subscription' === $post_type ) {
                            if ( $con_wc_hpos_enabled ) {
                                $order_number_meta = $order->get_meta( '_alg_wc_custom_order_number' );
                            } else {
                                $order_number_meta = get_post_meta( $order_id, '_alg_wc_custom_order_number', true );
                            }
                            if ( '' === $order_number_meta ) {
                                $order_number_meta = $order_id;
                            }
                            $order_number = apply_filters(
                                'alg_wc_custom_order_numbers',
                                sprintf( '%s%s', do_shortcode( $this->alg_wc_custom_order_numbers_prefix ), $order_number_meta ),
                                'value',
                                array(
                                    'order_timestamp'   => $order_timestamp,
                                    'order_number_meta' => $order_number_meta,
                                )
                            );
                            if ( $con_wc_hpos_enabled ) {
                                $order->update_meta_data( '_alg_wc_full_custom_order_number', $order_number );
                                $order->update_meta_data( 'subscription_orders_updated', 'yes' );
                                $order->save();
                            } else {
                                update_post_meta( $order_id, '_alg_wc_full_custom_order_number', $order_number );
                                update_post_meta( $order_id, 'subscription_orders_updated', 'yes' );
                            }
                            return $order_number;
                        }
                    }
                    if ( $con_wc_hpos_enabled ) {
                        $order_number_meta = $order->get_meta( '_alg_wc_full_custom_order_number' );
                    } else {
                        $order_number_meta = get_post_meta( $order_id, '_alg_wc_full_custom_order_number', true );
                    }
                    // This code of block is added to update the meta key '_alg_wc_full_custom_order_number' in new orders which were placed after the update of v1.3.0 where counter type is set to order id.
                    if ( $con_wc_hpos_enabled ) {
                        $new_orders_updated = $order->get_meta( 'new_orders_updated' );
                    } else {
                        $new_orders_updated = get_post_meta( $order_id, 'new_orders_updated', true );
                    }
                    if ( 'yes' !== $new_orders_updated ) {
                        $counter_type = 'sequential';
                        if ( 'order_id' === $counter_type ) {
                            $order_number_meta = $order_id;
                            $order_number      = apply_filters(
                                'alg_wc_custom_order_numbers',
                                sprintf( '%s%s', do_shortcode( $this->alg_wc_custom_order_numbers_prefix ), $order_number_meta ),
                                'value',
                                array(
                                    'order_timestamp'   => $order_timestamp,
                                    'order_number_meta' => $order_number_meta,
                                )
                            );
                            if ( $con_wc_hpos_enabled ) {
                                $order->update_meta_data( '_alg_wc_full_custom_order_number', $order_number );
                                $order->update_meta_data( 'new_orders_updated', 'yes' );
                                $order->save();
                            } else {
                                update_post_meta( $order_id, '_alg_wc_full_custom_order_number', $order_number );
                                update_post_meta( $order_id, 'new_orders_updated', 'yes' );
                            }
                            return $order_number;
                        }
                    }
                    if ( '' === $order_number_meta ) {
                        $order_number_meta = $order_id;
                        $order_number_meta = apply_filters(
                            'alg_wc_custom_order_numbers',
                            sprintf( '%s%s', do_shortcode( $this->alg_wc_custom_order_numbers_prefix ), $order_number_meta ),
                            'value',
                            array(
                                'order_timestamp'   => $order_timestamp,
                                'order_number_meta' => $order_number_meta,
                            )
                        );
                    }
                    return $order_number_meta;
                } else {
                    if ( $con_wc_hpos_enabled ) {
                        $order_number_meta = $order->get_meta( '_alg_wc_custom_order_number' );
                    } else {
                        $order_number_meta = get_post_meta( $order_id, '_alg_wc_custom_order_number', true );
                    }
                    if ( '' === $order_number_meta ) {
                        $order_number_meta = $order_id;
                    }
                    $order_number = apply_filters(
                        'alg_wc_custom_order_numbers',
                        sprintf( '%s%s', do_shortcode( $this->alg_wc_custom_order_numbers_prefix ), $order_number_meta ),
                        'value',
                        array(
                            'order_timestamp'   => $order_timestamp,
                            'order_number_meta' => $order_number_meta,
                        )
                    );
                    return $order_number;
                }
                return $order_number;
            }

            /**
             * Add_new_order_number.
             *
             * @param int $order_id - Order ID.
             *
             * @version 1.0.0
             * @since   1.0.0
             */
            public function add_new_order_number( $order_id ) {
                $this->add_order_number_meta( $order_id, false );
            }

            /**
             * Add/update order_number meta to order.
             *
             * @param int  $order_id - Order ID.
             * @param bool $do_overwrite - Change the order number to a custom number.
             *
             * @version 1.2.0
             * @since   1.0.0
             */
            public function add_order_number_meta( $order_id, $do_overwrite ) {
                $con_wc_hpos_enabled = $this->con_wc_hpos_enabled();
                if ( $con_wc_hpos_enabled ) {
                    if ( ! in_array( OrderUtil::get_order_type( $order_id ), array( 'shop_order', 'shop_subscription' ), true ) ) {
                        return false;
                    }
                }
                if ( ! $con_wc_hpos_enabled ) {
                    if ( ! in_array( get_post_type( $order_id ), array( 'shop_order', 'shop_subscription' ), true ) ) {
                        return false;
                    }
                }
                $order = wc_get_order( $order_id );
                if ( true === $do_overwrite || '' ==  ( $con_wc_hpos_enabled ? $order->get_meta( '_alg_wc_custom_order_number' ) : get_post_meta( $order_id, '_alg_wc_custom_order_number', true ) ) ) { // phpcs:ignore
                    $is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
                    $order_timestamp       = strtotime( ( $is_wc_version_below_3 ? $order->order_date : $order->get_date_created() ) );
                    $counter_type          = 'sequential';
                    if ( 'sequential' === $counter_type ) {
                        // Using MySQL transaction, so in case of a lot of simultaneous orders in the shop - prevent duplicate sequential order numbers.
                        global $wpdb;
                        $wpdb->query( 'START TRANSACTION' ); //phpcs:ignore
                        $wp_options_table = $wpdb->prefix . 'options';
                        $result_select    = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . $wpdb->prefix . 'options` WHERE option_name = %s', 'alg_wc_custom_order_numbers_counter' ) ); //phpcs:ignore
                        if ( null !== $result_select ) {
                            $current_order_number     = $this->maybe_reset_sequential_counter( $result_select->option_value, $order_id );
                            $result_update            = $wpdb->update( // phpcs:ignore
                                $wp_options_table,
                                array( 'option_value' => ( $current_order_number + 1 ) ),
                                array( 'option_name' => 'alg_wc_custom_order_numbers_counter' )
                            );
                            $current_order_number_new = $current_order_number + 1;
                            if ( null !== $result_update || $current_order_number_new === $result_select->option_value ) {
                                $full_custom_order_number = apply_filters(
                                    'alg_wc_custom_order_numbers',
                                    sprintf( '%s%s', do_shortcode( $this->alg_wc_custom_order_numbers_prefix ), $current_order_number ),
                                    'value',
                                    array(
                                        'order_timestamp'   => $order_timestamp,
                                        'order_number_meta' => $current_order_number,
                                    )
                                );
                                // all ok.
                                $wpdb->query( 'COMMIT' ); //phpcs:ignore
                                if ( $con_wc_hpos_enabled ) {
                                    $order->update_meta_data( '_alg_wc_custom_order_number', $current_order_number );
                                    $order->update_meta_data( '_alg_wc_full_custom_order_number', $full_custom_order_number );
                                    $order->save();
                                } else {
                                    update_post_meta( $order_id, '_alg_wc_custom_order_number', $current_order_number );
                                    update_post_meta( $order_id, '_alg_wc_full_custom_order_number', $full_custom_order_number );
                                }
                            } else {
                                // something went wrong, Rollback.
                                $wpdb->query( 'ROLLBACK' ); //phpcs:ignore
                                return false;
                            }
                        } else {
                            // something went wrong, Rollback.
                            $wpdb->query( 'ROLLBACK' ); //phpcs:ignore
                            return false;
                        }
                    }
                    return $current_order_number;
                }
                return false;
            }

            /**
             * Updates the custom order number for a renewal order created
             * using WC Subscriptions
             *
             * @param WC_Order $renewal_order - Order Object of the renewed order.
             * @param object   $subscription - Subscription for which the order has been created.
             * @return WC_Order $renewal_order
             * @since 1.2.6
             */
            public function remove_order_meta_renewal( $renewal_order, $subscription ) {
                $new_order_id = $renewal_order->get_id();
                // update the custom order number.
                $this->add_order_number_meta( $new_order_id, true );
                return $renewal_order;
            }

            /**
             * Updates the custom order number for the WC Subscription
             *
             * @param object $subscription - Subscription for which the order has been created.
             * @since 1.2.6
             */
            public function update_custom_order_meta( $subscription ) {

                $subscription_id = $subscription->get_id();
                // update the custom order number.
                $this->add_order_number_meta( $subscription_id, true );

            }

            /**
             * Remove the WooCommerc filter which convers the order numbers to integers by removing the * * characters.
             */
            public function alg_remove_tracking_filter() {
                remove_filter( 'woocommerce_shortcode_order_tracking_order_id', 'wc_sanitize_order_id' );
            }

            /**
             * Function to unset the CON meta key at the time of renewal of subscription.
             *
             * @param Array  $meta Array of a meta key present in the subscription.
             * @param Object $to_order  Order object.
             * @param Objec  $from_order Subscription object.
             */
            public function remove_con_metakey_in_wcs_order_meta( $meta, $to_order, $from_order ) {
                $to_order_id = $to_order->get_id();
                if ( $this->con_wc_hpos_enabled() ) {
                    $from_order_type = OrderUtil::get_order_type( $from_order->get_id() );
                } else {
                    $from_order_type = get_post_type( $from_order->get_id() );
                }
                if ( 0 === $to_order_id && 'shop_subscription' === $from_order_type ) {
                    foreach ( $meta as $key => $value ) {
                        if ( '_alg_wc_custom_order_number' === $value['meta_key'] ) {
                            unset( $meta[ $key ] );
                        }
                        if ( '_alg_wc_full_custom_order_number' === $value['meta_key'] ) {
                            unset( $meta[ $key ] );
                        }
                    }
                }
                return $meta;
            }

            /**
             * Function to see if prefix value is changed or not.
             *
             * @param string $new_value New setting value which is selected.
             * @param string $old_value Old setting value which is saved in the database.
             */
            public function pre_alg_wc_custom_order_numbers_prefix( $new_value, $old_value ) {
                if ( $new_value !== $old_value ) {
                    update_option( 'alg_wc_custom_order_numbers_prefix_suffix_changed', '1' );
                }
                return $new_value;
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


    function epayco_update_db_check()
    {
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
            .order-status.status-<?php esc_html_e( $order_status_failed, 'text_domain' );  ?> {
                background: #eba3a3;
                color: #761919;
            }
            .order-status.status-<?php esc_html_e( $order_status_on_hold, 'text_domain' ); ?> {
                background: #f8dda7;
                color: #94660c;
            }
            .order-status.status-<?php esc_html_e( $order_status_processing, 'text_domain' ); ?> {
                background: #c8d7e1;
                color: #2e4453;
            }
            .order-status.status-<?php esc_html_e( $order_status_processing_, 'text_domain' ); ?> {
                background: #c8d7e1;
                color: #2e4453;
            }
            .order-status.status-<?php esc_html_e( $order_status_completed, 'text_domain' ); ?> {
                background: #d7f8a7;
                color: #0c942b;
            }
            .order-status.status-<?php esc_html_e( $order_status_completed_, 'text_domain' ); ?> {
                background: #d7f8a7;
                color: #0c942b;
            }
            .order-status.status-<?php esc_html_e( $order_status_cancelled, 'text_domain' ); ?> {
                background: #eba3a3;
                color: #761919;
            }
        </style>

        <?php
    }

    add_filter('woocommerce_product_data_tabs', 'epayco_product_settings_tabs' );
    function epayco_product_settings_tabs( $tabs ){
        $tabs['epayco'] = array(
            'label'    => 'Receivers',
            'target'   => 'epayco_product_data',
            'class'    => array('show_if_simple'),
            'priority' => 21,
        );
        return $tabs;

    }

    /*add_filter('woocommerce_product_data_tabs', 'epayco_tax_settings_tabs' );
    function epayco_tax_settings_tabs( $tabs ){
        $tabs['epayco_tax'] = array(
            'label'    => 'ICO',
            'target'   => 'epayco_tax_data',
            'class'    => array('show_if_simple'),
            'priority' => 21,
        );
        return $tabs;

    }*/

    /*
     * Tab content
     */
    add_action( 'woocommerce_product_data_panels', 'epayco_product_panels' );
    function epayco_product_panels(){
        global $post;
        echo '<div id="epayco_product_data" class="panel woocommerce_options_panel hidden">';

        woocommerce_wp_text_input( array(
            'id'                => 'p_cust_id_client',
            'value'             => get_post_meta( get_the_ID(), 'p_cust_id_client', true ),
            'label'             => 'Id customer',
            'description'       => 'Id del usuario que va a recibir el pago'
        ) );

        woocommerce_wp_checkbox( array(
            'id'      => '_super_product',
            'value'   => get_post_meta( get_the_ID(), '_super_product', true ),
            'label'   => 'Valor del producto',
            'class'             => '_super_product',
            'style'             => '',
            'wrapper_class'     => '',
            'desc_tip' => false,
            'description' => 'la comisión se realiza sobre el mismo valor del producto',
        ) );

        woocommerce_wp_textarea_input( array(
            'id'          => 'epayco_comition',
            'value'       => get_post_meta( get_the_ID(), 'epayco_comition', true ),
            'label'       => 'Comisión',
            'desc_tip'    => true,
            'description' => 'Valor de la comisión que se paga al comercio',
            'wrapper_class' => 'epayco_comition',
        ) );

        /*woocommerce_wp_select(array(
            'id' => 'epayco_ext',
            'value' => get_post_meta(get_the_ID(), 'epayco_ext', true),
            'wrapper_class' => 'epayco_ext',
            'label' => 'Tipo de dispersión',
            'options' => array('01' => 'fija','02' => 'porcentaje'),
            'desc_tip'    => true,
            'description' => 'hace referencia al tipo de fee que se enviará al comercio principal',
        ));*/
        echo '</div>';
        echo  '<script type="text/javascript">
                 function update_wjecf_apply_silently_field(  ) { 
                        if (!jQuery("#_super_product").prop("checked")) {
						jQuery(".epayco_comition").show();
                        } else {
                            jQuery(".epayco_comition").hide();
                        }
                }
                update_wjecf_apply_silently_field()
                jQuery("#_super_product").click( update_wjecf_apply_silently_field );
                </script>
        ';

    }


    /*add_action( 'woocommerce_product_data_panels', 'epayco_tax_panels' );
    function epayco_tax_panels(){
        global $post;
        echo '<div id="epayco_tax_data" class="panel woocommerce_options_panel hidden">';

        woocommerce_wp_text_input( array(
            'id'                => 'tax_epayco',
            'value'             => get_post_meta( get_the_ID(), 'tax_epayco', true ),
            'label'             => 'ico',
            'description'       => 'porcentaje del impuesto'
        ) );

    }*/


    add_action( 'woocommerce_process_product_meta', 'epayco_save_fields', 10, 2 );
    function epayco_save_fields( $id, $post ){
        update_post_meta( $id, '_super_product', sanitize_text_field($_POST['_super_product']) );
        update_post_meta( $id, 'p_cust_id_client',sanitize_text_field($_POST['p_cust_id_client']) );
        update_post_meta( $id, 'epayco_comition', sanitize_text_field($_POST['epayco_comition']) );
        //update_post_meta( $id, 'epayco_ext', sanitize_text_field($_POST['epayco_ext']) );
        update_post_meta( $id, 'tax_epayco', sanitize_text_field($_POST['tax_epayco']) );
    }
    add_action('admin_head', 'epayco_css_icon');
    function epayco_css_icon(){
        echo '<style>
        #woocommerce-product-data ul.wc-tabs li.epayco_options.epayco_tab a:before{
            content: "\f307";
        }
        </style>';
    }

    add_filter( 'woocommerce_calculated_total', 'add_hundred_dollars_to_cart_total', 10, 2 );
    function add_hundred_dollars_to_cart_total( $total, $cart ) {
        return $total;
    }


    add_filter( 'woocommerce_cart_shipping_total', 'woocommerce_cart_shipping_total_filter_callback', 11, 2 );
    function woocommerce_cart_shipping_total_filter_callback( $total, $cart )
    {
        // HERE set the percentage
        $percentage = 50;

        if ( 0 < $cart->get_shipping_total() ) {
            if ( $cart->display_prices_including_tax() ) {
                $total = wc_price( ( $cart->shipping_total + $cart->shipping_tax_total ) * $percentage / 10 );
                if ( $cart->shipping_tax_total > 0 && ! wc_prices_include_tax() ) {
                    $total .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . ' fffff </small>';
                }
            } else {
                $total = wc_price( $cart->shipping_total * $percentage / 100  );
                if ( $cart->shipping_tax_total > 0 && wc_prices_include_tax() ) {
                    $total .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat()  . ' fff ff</small>';
                }
            }
        }
        return  $total;
    }

    add_filter('woocommerce_cart_item_name','add_usr_custom_session',1,3);
    function add_usr_custom_session($product_name, $values, $cart_item_key ) {

        $return_string = $product_name . "<br />" ;// . "<br />" . print_r($values['_custom_options']);
        return $return_string;

    }

    add_action( 'woocommerce_before_calculate_totals', 'update_custom_price', 1, 1 );
    function update_custom_price( $cart_object ) {
        foreach ( $cart_object->cart_contents as $cart_item_key => $value ) {

        }
    }


    /*add_action( 'woocommerce_cart_calculate_fees','custom_tax_surcharge_for_swiss', 10, 1 );
    function custom_tax_surcharge_for_swiss( $cart ) {
        if ( is_admin() && ! defined('DOING_AJAX') ) return;
        global $woocommerce, $post;
        $ico_value=0;
        foreach (WC()->session->get('cart') as $key => $value) {
            $product_id = $woocommerce->cart->get_cart_contents()[$key]['product_id'];
            $line_subtotal = $woocommerce->cart->get_cart_contents()[$key]['line_subtotal'];
            $line_subtotal_tax = $woocommerce->cart->get_cart_contents()[$key]['line_subtotal_tax'];
            $line_total = $woocommerce->cart->get_cart_contents()[$key]['line_total'];
            $product_name = $woocommerce->cart->get_cart_contents()[$key]['data']->get_name();
            $ico = get_post_meta( $product_id, 'tax_epayco' ) ?  intval(get_post_meta( $product_id, 'tax_epayco' )[0]) : 0 ;
            $ico_value = $ico_value + ( $line_subtotal  ) * $ico / 100;
        }

        if($ico_value>0){
            // Add the fee (tax third argument disabled: false)
            $cart->add_fee( __( 'ICO', 'woocommerce')."", $ico_value, false );
        }

    }*/




}

if ( ! class_exists( 'Alg_WC_Custom_Order_Numbers' ) ) :
    /**
     * Main Alg_WC_Custom_Order_Numbers Class
     *
     * @class   Alg_WC_Custom_Order_Numbers
     * @version 1.2.3
     * @since   1.0.0
     */
    final class Alg_WC_Custom_Order_Numbers {

        /**
         * Plugin version.
         *
         * @var   string
         * @since 1.0.0
         */
        public $version = '1.4.0';

        /**
         * The single instance of the class
         *
         * @var   Alg_WC_Custom_Order_Numbers The single instance of the class
         * @since 1.0.0
         */
        protected static $instance = null;

        /**
         * Main Alg_WC_Custom_Order_Numbers Instance
         *
         * Ensures only one instance of Alg_WC_Custom_Order_Numbers is loaded or can be loaded.
         *
         * @version 1.0.0
         * @since   1.0.0
         * @static
         * @return  Alg_WC_Custom_Order_Numbers - Main instance
         */
        public static function instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Alg_WC_Custom_Order_Numbers Constructor.
         *
         * @version 1.0.0
         * @since   1.0.0
         * @access  public
         */
        public function __construct() {

            // Set up localisation.
            load_plugin_textdomain( 'epayco_woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );

            // Include required files.
            $this->includes();

            // Settings & Scripts.
            if ( is_admin() ) {
                add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_woocommerce_settings_tab' ) );
            }
        }

        /**
         * Include required core files used in admin and on the frontend.
         *
         * @version 1.2.0
         * @since   1.0.0
         */
        public function includes() {
            // Settings.
            //require_once 'includes/admin/class-alg-wc-custom-order-numbers-settings-section.php';
            $this->settings            = array();
            //$this->settings['general'] = require_once 'includes/admin/class-alg-wc-custom-order-numbers-settings-general.php';
            if ( is_admin() && get_option( 'alg_custom_order_numbers_version', '' ) !== $this->version ) {
                foreach ( $this->settings as $section ) {
                    foreach ( $section->get_settings() as $value ) {
                        if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
                            $autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
                            add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
                        }
                    }
                }
                if ( '' !== get_option( 'alg_custom_order_numbers_version', '' ) ) {
                    update_option( 'alg_custom_order_numbers_show_admin_notice', 'yes' );
                }
                if ( '' !== get_option( 'alg_custom_order_numbers_version', '' ) && '1.3.0' > get_option( 'alg_custom_order_numbers_version', '' ) ) {
                    update_option( 'alg_custom_order_numbers_no_meta_admin_notice', 'yes' );
                }
                update_option( 'alg_custom_order_numbers_version', $this->version );
            }
            // Core file needed.
            //require_once 'includes/class-alg-wc-custom-order-numbers-core.php';
        }

        /**
         * Add Custom Order Numbers settings tab to WooCommerce settings.
         *
         * @param array $settings - List containing all the plugin files which will be displayed in the Settings.
         * @return array $settings
         *
         * @version 1.2.2
         * @since   1.0.0
         */
        public function add_woocommerce_settings_tab( $settings ) {
            $settings[] = include 'includes/admin/class-alg-wc-settings-custom-order-numbers.php';
            return $settings;
        }

        /**
         * Get the plugin url.
         *
         * @version 1.0.0
         * @since   1.0.0
         * @return  string
         */
        public function plugin_url() {
            return untrailingslashit( plugin_dir_url( __FILE__ ) );
        }

        /**
         * Get the plugin path.
         *
         * @version 1.0.0
         * @since   1.0.0
         * @return  string
         */
        public function plugin_path() {
            return untrailingslashit( plugin_dir_path( __FILE__ ) );
        }

    }

endif;

if ( ! function_exists( 'alg_wc_custom_order_numbers' ) ) {
    /**
     * Returns the main instance of Alg_WC_Custom_Order_Numbers to prevent the need to use globals.
     *
     * @version 1.0.0
     * @since   1.0.0
     * @return  Alg_WC_Custom_Order_Numbers
     */
    function alg_wc_custom_order_numbers() {
        return Alg_WC_Custom_Order_Numbers::instance();
    }
}

alg_wc_custom_order_numbers();