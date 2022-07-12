<?php
/**
 * @since             1.0.0
 * @package           ePayco_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       ePayco Gateway WooCommerce
 * Description:       Plugin ePayco Gateway for WooCommerce.
 * Version:           6.4.0
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
            public function __construct()
            {
                $this->id = 'epayco';
                $this->version = '6.2.0';
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
                $this->epayco_url_response=$this->get_option('epayco_url_response');
                $this->epayco_url_confirmation=$this->get_option('epayco_url_confirmation');
                $this->epayco_lang=$this->get_option('epayco_lang')?$this->get_option('epayco_lang'):'es';
                $this->response_data = $this->get_option('response_data');
                $this->force_redirect = $this->get_option('force_redirect');
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

                if ($this->epayco_testmode == "yes") {
                    if (class_exists('WC_Logger')) {
                        $this->log = new WC_Logger();
                    } else {
                        $this->log = WC_ePayco::woocommerce_instance()->logger();
                    }
                }
            }

            function order_received_message( $text, $order ) {
                if(!empty(sanitize_text_field($_GET['msg']))){
                    return $text .' '.sanitize_text_field($_GET['msg']);
                }
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
                foreach ($order->get_items() as $product) {
                    $epayco_p_cust_id_client = get_post_meta( $product["product_id"], 'p_cust_id_client' );
                    $receiversa['id'] = $epayco_p_cust_id_client[0];
                    $epayco_super_product = get_post_meta( $product["product_id"], '_super_product' );
                    $epayco_epayco_comition = get_post_meta( $product["product_id"], 'epayco_comition' );
                    if($epayco_super_product[0] != "yes"){
                        $productTotalComision = floatval($epayco_epayco_comition[0])*$product["quantity"];
                        $receiversa['total'] = floatval($product['total']) ;
                        $fee = floatval($product['total'])-$productTotalComision;
                        $receiversa['iva'] = 0;
                        $receiversa['base_iva'] = 0;
                        $receiversa['fee'] = $fee;
                    }else{
                        $receiversa['total'] =  floatval($product['total']);
                        $receiversa['iva'] = 0;
                        $receiversa['base_iva'] = 0;
                        $receiversa['fee'] = 0;
                    }
                    $clearData = str_replace('_', ' ', $this->string_sanitize($product['name']));
                    $descripcionParts[] = $clearData;
                    if($epayco_p_cust_id_client[0]) {
                        array_push($receiversData, $receiversa);
                    }
                }
                $receivers = $receiversData;
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
                               src="https://epayco-checkout-testing.s3.amazonaws.com/checkout.preprod.js?version=1643645084821">
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
                            
                            var openChekout = function () {
                              handler.open(data)
                            }
                            var bntPagar = document.getElementById("btn_epayco");
                            bntPagar.addEventListener("click", openChekout);
    
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

                            setTimeout(openChekout, 2000)  
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
                    $tax,
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
                }else{

                    if (!$ref_payco)
                    {
                        $explode=explode('=',$order_id);
                        $ref_payco=$explode[1];
                    }

                    $url = 'https://secure.epayco.io/validation/v1/reference/'.$ref_payco;
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
                $x_approval_code_value = intval($x_approval_code);
                if(floatval($order->get_total()) == floatval($x_amount)){
                    if("yes" == $isTestPluginMode){
                        $validation = true;
                    }
                    if("no" == $isTestPluginMode ){
                        if($x_approval_code_value > 0 && $x_cod_transaction_state == 1){
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
                                if($current_state != $orderStatus){
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
                        case 6: {
                            $message = 'Pago Reversada' .$x_ref_payco;
                            $messageClass = 'woocommerce-error';
                            $order->update_status('refunded');
                            $order->add_order_note('Pago Reversado');
                            $this->restore_order_stock($order->get_id());
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
                            if($x_cod_transaction_state!=1){
                                if($current_state == "epayco_failed" ||
                                $current_state == "epayco_cancelled" ||
                                $current_state == "failed" ||
                                $current_state == "epayco-cancelled" ||
                                $current_state == "epayco-failed"
                            ){}else{
                                $this->restore_order_stock($order->get_id());
                                }
                            }
                        }

                    }
                    $order->update_status($orderStatus);
                    $order->add_order_note($message);
                    $messageClass = 'error';
                }

                if (isset($_REQUEST['confirmation'])) {
                    echo $x_cod_transaction_state;
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
             * @param $validationData
             */
            function ePayco_successful_validation($validationData)
            {
                $username = sanitize_text_field($validationData['epayco_publickey']);
                $password = sanitize_text_field($validationData['epayco_privatey']);
                $response = wp_remote_post( 'https://apify.epayco.co/login', array(
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

        woocommerce_wp_select(array(
            'id' => 'epayco_ext',
            'value' => get_post_meta(get_the_ID(), 'epayco_ext', true),
            'wrapper_class' => 'epayco_ext',
            'label' => 'Tipo de dispersión',
            'options' => array('01' => 'fija'),
            'desc_tip'    => true,
            'description' => 'hace referencia al tipo de fee que se enviará al comercio principal',
        ));
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
    add_action( 'woocommerce_process_product_meta', 'epayco_save_fields', 10, 2 );
    function epayco_save_fields( $id, $post ){
        update_post_meta( $id, '_super_product', sanitize_text_field($_POST['_super_product']) );
        update_post_meta( $id, 'p_cust_id_client',sanitize_text_field($_POST['p_cust_id_client']) );
        update_post_meta( $id, 'epayco_comition', sanitize_text_field($_POST['epayco_comition']) );
        update_post_meta( $id, 'epayco_ext', sanitize_text_field($_POST['epayco_ext']) );
    }
    add_action('admin_head', 'epayco_css_icon');
    function epayco_css_icon(){
        echo '<style>
        #woocommerce-product-data ul.wc-tabs li.epayco_options.epayco_tab a:before{
            content: "\f307";
        }
        </style>';
    }

}