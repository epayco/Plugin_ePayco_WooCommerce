<?php

/**
 * @var string $style
 * @var string $general
 * @var string $card_style
 * @var string $cardsjscss
 * @var string $logo_comercio
 * @var string $amount
 * @var string $epayco
 * @var string $shop_name
 * @var string $product_name_
 * @var string $currency
 * @var string $email_billing
 * @var string $name_billing
 * @var string $redirect_url
 * @var string $str_countryCode
 * @var string $stylemin
 * @var string $apiKey
 * @var string $privateKey
 * @var string $lang
 * @var string $card_unmin
 * @var string $epaycojs
 * @var string $indexjs
 * @var string $appjs
 * @var string $cardsjs
 * @var string $icon_warning
 * @see \EpaycoSubscription\Woocommerce\Gateways\EpaycoSuscription
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<head>
    <link  rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/all.css"
          integrity="sha384-hWVjflwFxL6sNzntih27bfxkr27PmbbK/iSvJ+a4+0owXq79v+lsFkW54bOGbiDQ" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/10.4.2/css/bootstrap-slider.min.css"
          rel="stylesheet">
</head>
<body>
    <div class=""  id="movil_mainContainer" style="top:0px">
        <section class="modal-container">
            <style>
                .form-container .icon {
                    color: #ED9733 !important;
                }
                .button-container .pay-type {
                    border: 1px solid #ED9733;
                }
                .button-container .pay-type .icon {
                    color: #ED9733;
                }
                .cont-btn {
                    padding: 8px;
                    background-color: #f3f3f3;
                    border-radius: 0 0 10px 10px;
                    text-align: center;
                }
            </style>
            <div class="loading-home op" style="display: none"  id="loading_home">
                <div class="circulo ">
                    <div class="lock">
                        <svg class="svg-inline--fa fa-lock fa-w-14" aria-hidden="true" data-prefix="fa" data-icon="lock" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg=""><path fill="currentColor" d="M400 224h-24v-72C376 68.2 307.8 0 224 0S72 68.2 72 152v72H48c-26.5 0-48 21.5-48 48v192c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V272c0-26.5-21.5-48-48-48zm-104 0H152v-72c0-39.7 32.3-72 72-72s72 32.3 72 72v72z"></path></svg><!-- <i class="fa fa-lock"></i> -->
                    </div>
                    Procesando Pago...
                </div>
            </div>
            <section class="modal" hidden id="movil_modal" style="padding-top: 0rem !important;">
                <header class="animated fadeInDown" style="background-color: #ED9733 !important">
                    <div class="title-container  ">
                        <div class="logo-commerce">
                            <div class="logo-container" style="">
                                <img width="90%" src="<?php echo esc_html($epayco); ?>">
                            </div>
                        </div>
                        <div class="col title">
                            <div class="comercio-name ">
                            <?php  echo esc_html($shop_name); ?>
                            </div>
                            <div class="description-cont ">
                                <p><?php echo esc_html($product_name_ ); ?></p>
                                <strong class="monto">
                                    $<?php echo esc_html($amount); ?>
                                    <input type="hidden" value="<?php echo esc_html($amount); ?>" id="currentAmount">
                                    <span class="moneda"><?php echo esc_html($currency); ?></span>
                                </strong>
                            </div>
                        </div>
                    </div>
                    <div class="langaugeCancelt">
                        <div class="cancelPayment" id="cancel-t">
                            <svg class="svg-inline--fa fa-times fa-w-12" aria-hidden="true" data-prefix="fa" data-icon="times" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" data-fa-i2svg=""><path fill="currentColor" d="M323.1 441l53.9-53.9c9.4-9.4 9.4-24.5 0-33.9L279.8 256l97.2-97.2c9.4-9.4 9.4-24.5 0-33.9L323.1 71c-9.4-9.4-24.5-9.4-33.9 0L192 168.2 94.8 71c-9.4-9.4-24.5-9.4-33.9 0L7 124.9c-9.4 9.4-9.4 24.5 0 33.9l97.2 97.2L7 353.2c-9.4 9.4-9.4 24.5 0 33.9L60.9 441c9.4 9.4 24.5 9.4 33.9 0l97.2-97.2 97.2 97.2c9.3 9.3 24.5 9.3 33.9 0z"></path></svg><!-- <i class="fa fa-times"></i> -->
                        </div>
                        <div class="language-switch">
                            <a class="dn set-lang pointer l-es" data-lang="es" id="data_lang_es">ES</a>
                            <a class=" set-lang pointer l-en" data-lang="en" id="data_lang_en">EN</a>
                        </div>
                    </div>
                    <div id="email-container" class="email-container active">
                        <?php echo esc_html($email_billing); ?>
                        <div class="container-acvive-email " style="display: none;">
                            <div class="back-button">
                                <svg class="svg-inline--fa fa-angle-left fa-w-8"  aria-hidden="true" data-prefix="fa" data-icon="angle-left" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512" data-fa-i2svg=""><path fill="currentColor" d="M31.7 239l136-136c9.4-9.4 24.6-9.4 33.9 0l22.6 22.6c9.4 9.4 9.4 24.6 0 33.9L127.9 256l96.4 96.4c9.4 9.4 9.4 24.6 0 33.9L201.7 409c-9.4 9.4-24.6 9.4-33.9 0l-136-136c-9.5-9.4-9.5-24.6-.1-34z"></path></svg><!-- <button class="fa fa-angle-left" ></button> -->
                            </div>
                            <div class="volverSalir">
                                <p class="email ">&nbsp;&nbsp;ricardo.saldarriaga@epayco.com</p>
                                <button class="log-out " onclick="goBack();">
                                    <span class="logout-text">Cerrar sesión</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </header>
                <section class="content animated zoomIn " style="
              background-color: white; padding-top: 0rem;">
                    <div id="content-errors"></div>
                    <form id="form-action" method="post" novalidate=""  action="<?php echo esc_html($redirect_url ); ?>" >
                        <div class="step step-tdc main-steps active" data-group="tdc" active="" style="margin: 0px;">
                            <div class="step-container">
                                <div class="step-form">
                                    <!-- Name -->
                                    <div class="form-container extra-label">
                                        <svg class="svg-inline--fa fa-user fa-w-16 icon" aria-hidden="true" data-prefix="fa" data-icon="user" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><path fill="currentColor" d="M256 0c88.366 0 160 71.634 160 160s-71.634 160-160 160S96 248.366 96 160 167.634 0 256 0zm183.283 333.821l-71.313-17.828c-74.923 53.89-165.738 41.864-223.94 0l-71.313 17.828C29.981 344.505 0 382.903 0 426.955V464c0 26.51 21.49 48 48 48h416c26.51 0 48-21.49 48-48v-37.045c0-44.052-29.981-82.45-72.717-93.134z"></path></svg><!-- <i class="icon fa fa-1.5x fa-user"></i> -->
                                        <div class="label-container ">
                                            <label for="card" id="label_name_es" style="display:table-cell">Nombre</label>
                                        </div>
                                        <div class="input-container">
                                            <input type="text" name="name" placeholder="Nombre propietario de tarjeta" value="ricardo saldarriaga">
                                        </div>
                                    </div>
                                    <div class="form-container">
                                        <svg class="svg-inline--fa fa-credit-card fa-w-18 icon" aria-hidden="true" data-prefix="fa" data-icon="credit-card" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" data-fa-i2svg=""><path fill="currentColor" d="M0 432c0 26.5 21.5 48 48 48h480c26.5 0 48-21.5 48-48V256H0v176zm192-68c0-6.6 5.4-12 12-12h136c6.6 0 12 5.4 12 12v40c0 6.6-5.4 12-12 12H204c-6.6 0-12-5.4-12-12v-40zm-128 0c0-6.6 5.4-12 12-12h72c6.6 0 12 5.4 12 12v40c0 6.6-5.4 12-12 12H76c-6.6 0-12-5.4-12-12v-40zM576 80v48H0V80c0-26.5 21.5-48 48-48h480c26.5 0 48 21.5 48 48z"></path></svg><!-- <i class="icon fa fa-credit-card"></i> -->
                                        <div class="label-container ">
                                            <label for="card" id="label_card_es" style="display:table-cell">Tarjeta</label>
                                        </div>
                                        <div class="input-container">
                                            <div class="card-jss" data-icon-colour="#158CBA">
                                                <div class="card-number2-wrapper">
                                                    <input class="card-number2" id="the-card-number2-element"
                                                           data-epayco="card[number]" required="" name="card-number2"
                                                           placeholder="**** **** **** ****" type="tel" maxlength="19"
                                                           x-autocompletetype="cc-number"
                                                           autocompletetype="cc-number"
                                                           autocorrect="off" spellcheck="off"
                                                           autocapitalize="off" style="
                                padding-left: 0px;">
                                                </div>
                                            </div>
                                            <img class="img-card" src="https://msecure.epayco.co/img/credit-cards/disable.png" id="logo_franchise">
                                            <input type="hidden" name="valid_franchise" value="false">
                                        </div>
                                    </div>
                                    <!-- End Name -->
                                    <div class="dosCampos">
                                        <!-- Expiry Date -->
                                        <div class="form-container dateYex fecha-exp">
                                            <svg class="svg-inline--fa fa-calendar fa-w-14 icon" aria-hidden="true" data-prefix="fa" data-icon="calendar" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg=""><path fill="currentColor" d="M12 192h424c6.6 0 12 5.4 12 12v260c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V204c0-6.6 5.4-12 12-12zm436-44v-36c0-26.5-21.5-48-48-48h-48V12c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v52H160V12c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v52H48C21.5 64 0 85.5 0 112v36c0 6.6 5.4 12 12 12h424c6.6 0 12-5.4 12-12z"></path></svg><!-- <i class="icon fa fa-calendar "></i> -->
                                            <div class="label-cvv-container RO">
                                                <label for="expiry" id="label_expiry_es" style="display:table-cell">Vence</label>
                                            </div>
                                            <div class="input-cvv-container ">
                                                <input name="expiry" type="tel" placeholder="MM/YY" id="expiry-input" class="">
                                            </div>
                                        </div>
                                        <!-- End Expiry Date -->
                                        <!-- Card Secret Code -->
                                        <div class="form-container dateYex  text-center line code-sec">
                                            <div class="label-cvv-container " style="display: block;">
                                                <svg class="svg-inline--fa fa-lock fa-w-14 icon" aria-hidden="true" data-prefix="fa" data-icon="lock" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg=""><path fill="currentColor" d="M400 224h-24v-72C376 68.2 307.8 0 224 0S72 68.2 72 152v72H48c-26.5 0-48 21.5-48 48v192c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V272c0-26.5-21.5-48-48-48zm-104 0H152v-72c0-39.7 32.3-72 72-72s72 32.3 72 72v72z"></path></svg><!-- <i class="fa fa-lock icon"></i> -->
                                                <label for="cvc">CVV</label>
                                            </div>
                                            <div class="input-cvv-container">
                                                <input type="tel" placeholder="123" maxlength="4" name="cvc" id="cvc-input" class="">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </section>
            </section>
        </section>
        <footer class="footer-buttons" hidden id="movil_footer">
            <div class="button-actions" style="display: none;">
                <button class="action-oneclick cancel-oneclick" style="background-color: #D8D8D8">Cancelar</button>
                <button class="action-oneclick save-oneclik">Guardar</button>
            </div>
            <button id="continue-tdc" class="continue-container text-center btnpay" style="background-color: #28303d;" type="submit">
                Pagar
                <svg class="svg-inline--fa fa-angle-right fa-w-8" aria-hidden="true" data-prefix="fas" data-icon="angle-right" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512" data-fa-i2svg=""><path fill="currentColor" d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z"></path></svg><!-- <i class="fas fa-angle-right"></i> -->
            </button>
            <div class="brand-footer">
                <p style="color:#1C0E49">
                    <svg class="svg-inline--fa fa-lock fa-w-14 secure" aria-hidden="true" data-prefix="fa" data-icon="lock" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg=""><path fill="currentColor" d="M400 224h-24v-72C376 68.2 307.8 0 224 0S72 68.2 72 152v72H48c-26.5 0-48 21.5-48 48v192c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V272c0-26.5-21.5-48-48-48zm-104 0H152v-72c0-39.7 32.3-72 72-72s72 32.3 72 72v72z"></path></svg><!-- <i class="fa fa-lock secure"></i> --> Pago seguro por </p>
                <img src="https://msecure.epayco.co/img/new_epayco_logo.png" alt="ePayco Logo" height="15px">
            </div>
        </footer>
        <div class="cancelT-modal dn" id="cancelT_modal" style="display:none">
            <div class="ventana dn">
                <div class="icono">
                    <svg class="svg-inline--fa fa-exclamation-circle fa-w-16" aria-hidden="true" data-prefix="fa" data-icon="exclamation-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><path fill="currentColor" d="M504 256c0 136.997-111.043 248-248 248S8 392.997 8 256C8 119.083 119.043 8 256 8s248 111.083 248 248zm-248 50c-25.405 0-46 20.595-46 46s20.595 46 46 46 46-20.595 46-46-20.595-46-46-46zm-43.673-165.346l7.418 136c.347 6.364 5.609 11.346 11.982 11.346h48.546c6.373 0 11.635-4.982 11.982-11.346l7.418-136c.375-6.874-5.098-12.654-11.982-12.654h-63.383c-6.884 0-12.356 5.78-11.981 12.654z"></path></svg>
                </div>
                <p>¿Está seguro de Cancelar esta Transacción?</p>
                <div class="acciones">
                    <button id="regresa-t">Regresar</button>
                    <button id="cancel-transaction">Cancelar Transacción</button>
                </div>
            </div>
        </div>
        <div class="modal-expiration-time dn" id="mdlInactivityTime"  style="display:none">
            <div class="ventana dn" id="mdlInactivityTimeBody">
                <div class="mdl-expiration-time">
                    <p class="mdl-expiration-time-title">Cuidado</p>
                    <p class="mdl-expiration-time-content padding-10">
                        Su sesión va a expirar en:
                    </p>
                    <div class="text-center">
                        <span class="spinner"></span>
                        <h1 id="counterInactivity">45</h1>
                        <p class="mdl-expiration-time-content">Segundos</p>
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-block">Continuar</button>
            </div>
        </div>
        <div class="modal-expiration-time  dn" id="mdlTimeExpired"  style="display:none">
            <div class="ventana dn" id="mdlTimeExpiredBody">
                <div class="mdl-expiration-time">
                    <div class="text-center">
                        <img src="https://msecure.epayco.co/img/reloj.png" class="img-65x65" alt="icono-warning" style="
                 display: block;
                 margin: auto;
                 text-align: center;
                  ">
                    </div>
                    <p class="mdl-expiration-time-title">Su sesión ha expirado por inactividad</p>
                    <p class="mdl-expiration-time-content text-center">
                        De clic en cerrar para regresar e iniciar una nueva transacción.
                    </p>
                    <button type="button" class="btn btn-primary btn-block" id="btnMdlTimeExpired">Cerrar</button>
                </div>
            </div>
        </div>
        <div id="p_c" hidden="true"><?php echo esc_html($apiKey ); ?></div>
        <div id="p_p" hidden="true"><?php echo esc_html($privateKey); ?></div>
        <div id="lang_epayco" hidden="true"><?php echo esc_html($lang ); ?></div>
        <div class="loader-container">
            <div class="loading"></div>
        </div>
        <p style="text-align: center;" class="epayco-title" id="epayco_title">
            <span class="animated-points">Cargando métodos de pago</span>
            <br>
            <small class="epayco-subtitle"> Si no se cargan automáticamente, de clic en el botón "Pagar con ePayco"</small>
        </p>
        <center>
            <button data-modal-target="#centered" id="button_epayco" style="
                  background-image: url(https://multimedia.epayco.co/plugins-sdks/Boton-color-espanol.png);
                  background-repeat:no-repeat;
                  height:39px;
                  width:144px;
                  background-position:center;
                  border-color: #28303d;
                  border-radius: 6px;
                  background-color: #28303d;
                  ">
            </button>
        </center>
        <div class="middle-xs bg_onpage porcentbody m-0" style="margin: 0">
            <div class="centered" id="centered">
                <div class="loadoverlay" id="loadoverlay">
                    <div class="loader loadimg"></div>
                    <i class="fa fa-lock fa-lg loadshield2" style="color:gray;position:fixed;" aria-hidden="true"></i>
                    <span class="loadtext">Procesando Pago</span>
                </div>
                <div class="onpage relative" id="web-checkout-content">
                    <div class="header-modal hidden-print">
                        <div class="logo-comercio">
                            <img class="image-safari" width="90%" src="<?php echo esc_html($logo_comercio); ?>">
                        </div>
                        <div class="header-modal-text">
                            <h1 style="font-size: 17px;margin-bottom:3px;height: 20px;margin: 0.2rem  1.5rem !important;color: #16161D ;"><?php echo esc_html($product_name_);?></h1>
                            <h2 style="font-size: 12px;margin-bottom:3px;color: #848484;margin: 0.2rem 1.5rem !important;"><?php echo esc_html($shop_name)?></h2>
                            <h1 style="font-size: 17px;margin-bottom:3px;height: 20px;margin: 0.2rem  1.5rem !important;color: #3582b7;font-weight: 900;">$<?php echo esc_html($amount);?> <?php echo esc_html($currency )?></h1>
                        </div>
                        <div class="color-exit hidden-print closeIcon" id="closeModal"><div data-close-button class="icon-cancel">&times;</div>
                        </div>
                    </div>
                    <div class="body-modal fix-top-safari">
                        <div class="bar-option hidden-print">
                            <div class="dropdown select-pais pointer" id="sample">
                                <dd>
                                    <ul id="foo"></ul>
                                </dd>
                                <p style="position: absolute !important;">
                                    <a class="dropdown-toggle blockd" style="background: none; border: none;" type="button" data-toggle="dropdown">
                                        <div class="flag flag-icon-background flag-icon-co" data-toggle="dropdown"  id="flag"></div>

                                        <div class="estilosContryName" id="countryName">Colombia</div>
                                        <i  class="fa fa-caret-down caret-languaje" id="icon-flecha" aria-hidden="true" ></i>

                                    </a>
                                </p>
                                <ul class="dropdown-menu" id="dropdown-countries"></ul>
                            </div>
                            <p style="display: flex; margin: 0px"><span id="result" hidden><?php echo esc_html($str_countryCode)?></span><a id="esButton" class="languaje pointer" data-es-button data-language="es">ES</a><a id="enButton" class="languaje pointer" data-en-button data-language="en">EN</a></p>
                        </div>
                        <div class="wc scroll-content">
                            <div class="separate">
                                <h2 class="title-body" style="text-align: left;width: calc(100% - 1.5em);
                            margin: 0 auto 1em; font-size: 16px; font-weight: 500; color: #3a3a3a;" id="info_es">Información de la tarjeta
                                </h2>
                                <h2 class="title-body" style="text-align: left;width: calc(100% - 1.5em);
                            margin: 0 auto 1em; font-size: 16px; font-weight: 500; color: #3a3a3a;" id="info_en">Credit card information
                                </h2>
                            </div>
                            <div class="menu-select">
                                <form id="token-credit" action="<?php echo esc_html($redirect_url)?>" method="post">
                                    <div class="card-js" data-icon-colour="#158CBA">
                                        <input class="name" id="the-card-name-element" data-epayco="card[name]" required value="<?php echo esc_html($name_billing)?>">
                                        <input class="card-number my-custom-class" data-epayco="card[number]" required id="the-card-number-element" name="card_number">
                                    </div>
                                    <div class="input-form" hidden>
                                        <span class="icon-credit-card color icon-input"><i class="fas fa-envelope"></i></span>
                                        <input type="tel" class="binding-input inspectletIgnore"  name="card_email"  autocomplete="off" hidden="true" data-epayco="card[email]" value="<?php echo esc_html($email_billing)?>">
                                    </div>
                                    <div class="select-option bordergray vencimiento" style="float:left" id="expiration">
                                        <div class="input-form full-width noborder monthcredit nomargin">
                                            <span class="icon-date_range color icon-select"><i class="far fa-calendar-alt"></i></span><input class="binding-input inspectletIgnore" id="month-value" name="month" placeholder="MM" maxlength="2" autocomplete="off" data-epayco="card[exp_month]"  required>
                                        </div>
                                        <div class="" style="
                              float:left;
                              width:12%;
                              margin:0;
                              text-align:center;
                              line-height: 40px;
                              height: 37px;
                              background-color: white;
                              color:#a3a3a3;">/</div>
                                        <div class="input-form full-width normalinput noborder yearcredit nomargin">
                                            <input name="year" id="year-value" placeholder="YYYY" maxlength="4" autocomplete="off" data-epayco="card[exp_year]"  required >
                                        </div>
                                    </div>
                                    <div class="input-form normalinput cvv_style" id="cvc_">
                                        <input type="password" placeholder="CVC" class="nomargin binding-input" name="cvc" id="card_cvc" autocomplete="off" maxlength="4" data-epayco="card[cvc]">
                                        <i class="fa color fa-question-circle pointer" aria-hidden="true" style="right: 10px;padding: 0;top: -5px;font-size: 21px !important;!i;!;!;"></i>
                                    </div>
                                    <br>
                                    <div class="clearfix"></div>
                                    <button class="call_action bgcolor white_font pointer load hidden-print" id="send-form">
                                        <h2 style="color: white;" id="pagar_es">Pagar</h2>
                                        <h2 style="color: white;" id="pagar_en">Pay</h2>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="footer-modal hidden-print" id="footer-animated">
                    <p id="pagar_logo_es">
                        <i class="fa fa-lock fa-lg" style="color: #2ECC71" aria-hidden="true"></i>Pago seguro por<img src="https://secure.epayco.co/img/new_epayco_white.png" height="20" style="display: inline;">
                    </p>
                    <p id="pagar_logo_en">
                        <i class="fa fa-lock fa-lg" style="color: #2ECC71" aria-hidden="true"></i>Secure payment by<img src="https://secure.epayco.co/img/new_epayco_white.png" height="20" style="display: inline;">
                    </p>
                </div>
            </div>
            <div id="overlay"></div>
        </div>
        <div id="style_min" hidden><?php echo esc_html($stylemin)?>"</div>
    </div>
</body>
<script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
<!--<script src="<?php echo esc_html($card_unmin )?>"></script>-->
<script src="<?php echo esc_html($epaycojs)?>"></script>
<script src="<?php echo esc_html($indexjs )?>"></script>
<div id="movil" hidden><?php echo esc_html($appjs )?></div>
<!--<script src="<?php echo esc_html($cardsjs )?>"></script>-->