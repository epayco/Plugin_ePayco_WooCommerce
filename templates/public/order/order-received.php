<?php

/**
 * Part of Woo Epayco Module
 * Author - Epayco
 * Developer
 * Copyright - Copyright(c) Epayco [https://www.epayco.com]
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * @package Epayco
 */

if (!defined('ABSPATH')) {
    exit;
}
?>


<?php if ($status) : ?>
    <!--<div style="height: 82px; background: black; display: flex; justify-content: center; align-items: center;">
    <img src="<?php echo esc_attr($epayco_icon); ?>" alt="epayco" style="width: 115px; height: 30px;">
</div>-->


    <div class="landingResumen">
        <nav class="navEpayco">
            <img src="https://secure.epayco.co/img/new-logo.svg" alt="logo">
        </nav>
        <div class="containerResumen">
            <div class="hole"></div>
            <div class="containerFacture">
                <div class="transaction">
                    <img src="<?php echo esc_attr($iconUrl); ?>" alt="check" style="display: block; margin: auto; border-bottom: 25px;">
                    <div class="transactionText">
                        <div class="h1Facture h1Bold" style="color: <?php echo esc_attr($iconColor); ?>;">
                            <?php echo esc_html($message); ?>
                        </div>
                        <div class="h1Facture">
                            <h2 style="font-size: 22px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;font-weight: bold"><?php echo esc_html($epayco_refecence); ?> #<?php echo esc_html($refPayco); ?></h2>
                        </div>
                        <div class="pFacture">
                            <?php echo esc_html($fecha); ?>
                        </div>
                    </div>
                </div>
                <div class="medioPago">
                    <div class="medios">
                        <div class="h2Facture"> <?php echo esc_html($paymentMethod); ?></div>
                        <div class="parDescription">
                            <div class="titleAndText">
                                <div class="h3Facture"> <?php echo esc_html($paymentMethod); ?> </div>
                                <div class="pageAndImage">
                                    <img class="metodoPago" src="<?php echo esc_attr($franchise_logo); ?>" id="metodoPagoId" alt="logoTransacción">
                                    <div class="pFacture"><?php echo esc_attr($x_cardnumber); ?></div>
                                </div>
                            </div>
                            <div class="titleAndTextRight">
                                <div class="h3Facture"><?php echo esc_html($authorizations); ?>
                                </div>
                                <div class="pFacture">
                                    <?php echo esc_html($authorization); ?>
                                </div>
                            </div>
                        </div>
                        <div class="parDescription">
                            <div class="titleAndText">
                                <div class="h3Facture"><?php echo esc_html($receipt); ?></div>
                                <div class="pFacture"><?php echo esc_html($factura); ?></div>
                            </div>
                            <div class="titleAndTextRight">
                                <div class="h3Facture"><?php echo esc_html($iPaddress); ?></div>
                                <div class="pFacture"><?php echo esc_html($ip); ?></div>
                            </div>
                        </div>
                        <?php if ($is_cash) : ?>
                            <div class="parDescription">
                                <div class="titleAndText">
                                    <div class="h3Facture"><?php echo esc_html($response); ?></div>
                                    <div class="pFacture"><?php echo esc_html($response_reason_text); ?></div>
                                </div>
                                <div class="titleAndTextRight">
                                    <div class="h3Facture"><?php echo esc_html($expirationDateText); ?></div>
                                    <div class="pFacture"><?php echo esc_html($expirationDate); ?></div>
                                </div>
                            </div>
                            <div class="parDescription">
                                <div class="pFacture"><?php echo esc_html($ticket_header); ?></div>
                            </div>
                            <div class="parDescription">
                                <div class="titleAndText">
                                    <div class="h3Facture"><?php echo esc_html($code); ?></div>
                                    <div class="pFacture"><?php echo esc_html($codeProject); ?></div>
                                </div>
                                <div class="titleAndTextRight">
                                    <div class="h3Facture">Pin</div>
                                    <div class="pFacture"><?php echo esc_html($pin); ?></div>
                                </div>
                            </div>

                        <?php else : ?>
                            <div class="parDescription">
                                <div class="titleAndTextComplete">
                                    <div class="h3Facture"><?php echo esc_html($response); ?></div>
                                    <div class="pFacture"><?php echo esc_html($response_reason_text); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="medios">
                        <div class="h2Facture"> <?php echo esc_html($purchase); ?></div>
                        <div class="parDescription">
                            <div class="titleAndText">
                                <div class="h3Facture"><?php echo esc_html($reference); ?></div>
                                <div class="pFacture"><?php echo esc_html($refPayco); ?></div>
                            </div>
                            <div class="titleAndTextRight">
                                <div class="h3Facture"><?php echo esc_html($description); ?></div>
                                <div class="pFacture"><?php echo esc_html($descripcion_order); ?></div>
                            </div>
                        </div>
                        <div class="parDescription">
                            <div class="titleAndText">
                                <div class="h3Facture"><?php echo esc_html($totalValue); ?></div>
                                <div class="pFacture">$<?php echo esc_html($valor); ?> <?php echo esc_html($currency); ?></div>
                            </div>
                            <div class="titleAndTextRight">
                                <div class="h3Facture">Subtotal</div>
                                <div class="pFacture">$<?php echo esc_html($x_amount_base); ?> <?php echo esc_html($currency); ?></div>
                            </div>
                        </div>
                        <?php if ($is_cash) : ?>
                            <div class="parDescription">
                                <div class="titleAndTextComplete">
                                    <div class="pFacture"><?php echo esc_html($ticket_footer); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <button class="buttonPrint hidden-print" style="color: white">
                <a href="<?php echo esc_html($donwload_url); ?>" target="_blank" style="color: white; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif">
                    <?php echo esc_html($donwload_text); ?>
                </a>
            </button>

        </div>

    </div>


<?php endif; ?>

<!-- Fuente personalizada -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

    #transactionBody {
        height: 550px;
        max-width: 550px;
        margin: auto;
        position: relative;
    }

    .div-description {
        max-height: 46px;
        display: flex;
        flex-direction: column;
    }

    .description-title {
        font-size: 16px;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif !important;
        color: darkgray;
        margin: 0px;
    }

    .descripcion-payment {
        font-size: 15px;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif !important;
        margin: 0px;
    }

    @media only screen and (max-width: 425px) {
        #transactionBody {
            padding-bottom: 200px;
        }
    }


    .landingResumen {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .navEpayco {
        justify-content: center;
        height: 6.5rem;
        background: #1d1d1d;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .containerResumen {
        flex-direction: column;
        justify-content: flex-start;
        height: fit-content;
        gap: 1rem;
        padding-top: 3rem;
        padding-bottom: 4.8rem;
    }

    .containerResumen,
    .navEpayco {
        display: flex;
        align-items: center;
        width: 100%;
        /*height: 82px;*/
    }

    .navEpayco {
        display: flex;
        align-items: center;
        width: 100%;
        height: 55px !important;
    }

    .hole {
        padding-top: 1.6rem;
        overflow: visible;
        width: 557px;
        height: 0px;
        border-radius: 1.6rem;
        background: #1d1d1d;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .containerFacture,
    .hole {
        display: flex;
        justify-content: center;
    }

    @media (max-width: 570px) {
        .hole {
            width: 95vw;
        }
    }

    .containerFacture {
        position: relative;
        align-items: center;
        transform: translateY(-1.95rem);
        flex-direction: column;
        background: #f9f9f9;
        height: fit-content;
        width: 490px;
        padding: 32px 24px 40px;
        gap: 18px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -1px rgba(0, 0, 0, .06);
        border-radius: 0 0 10px 10px;
        border-right: 1px solid var(--grey-grey-80, #cacaca);
        border-bottom: 1px solid var(--grey-grey-80, #cacaca);
        border-left: 1px solid var(--grey-grey-80, #cacaca);
        box-shadow: 0 8px 16px 0 rgba(0, 0, 0, .08);
        top: 5px;
    }

    @media (max-width: 570px) {
        .containerFacture {
            width: 76vw;
        }
    }

    .containerFacture,
    .hole {
        display: flex;
        justify-content: center;
    }

    .transaction {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: #1d1d1d;
        gap: 1.6rem;
    }

    .transactionText {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .5rem;
    }

    @media (max-width: 570px) {
        .h1Facture {
            font-size: 20px;
        }
    }

    .h1Bold {
        font-weight: 600;
    }

    .h1Facture {
        font-size: 24px;
        display: block;
        font-style: normal;
        font-weight: bold;
        line-height: normal;
    }

    .pFacture {
        font-size: 16px;
        color: #000;
    }

    .h3Facture,
    .pFacture {
        font-style: normal;
        font-weight: 400;
        line-height: normal;
    }

    @media (max-width: 570px) {

        .buttonContact,
        .buttonPrint {
            margin-top: 1rem;
            width: 90vw;
        }
    }

    .buttonContact,
    .buttonPrint {
        background: #1d1d1d;
        color: #fff;
        height: 2.8rem;
        width: 599px;
        cursor: pointer;
        border-radius: 6px;
        border: none;
        font-size: 18px;
        font-style: normal;
        font-weight: 600;
        line-height: normal;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .buttonContact,
    .buttonPrint:hover {
        background: #1d1d1dc7;
    }

    .buttonContact,
    .buttonPrint a {
        text-decoration: none;
    }

    .medioPago,
    .medios {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .medioPago {
        width: 100%;
        justify-content: center;
        align-items: center;
    }

    .medioPago,
    .medios {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .medios {
        width: 380px;
        align-items: self-start;
    }

    @media (max-width: 570px) {
        .medios {
            width: 65vw;
        }
    }

    .h2Facture {
        font-size: 16px;
        display: block;
        font-weight: 700;
    }

    .parDescription {
        display: flex;
        width: 100%;
        justify-content: space-between;
        align-items: flex-start;
    }

    .titleAndText,
    .titleAndTextRight {
        display: flex;
        flex-direction: column;
        width: 150px;
    }

    @media (max-width: 570px) {
        .titleAndText {
            margin-right: -10vw;
            width: 10.8rem;
        }
    }

    .h3Facture {
        font-size: 14px;
        color: #747474;
    }

    .pageAndImage {
        gap: 1rem;
        width: fit-content;
    }

    .metodoPago,
    .pageAndImage {
        display: flex;
        justify-content: center;
    }

    .metodoPago {
        align-items: center;
        height: 1.5rem !important;
    }

    .titleAndTextRight {
        margin: 0 -5px;
    }

    .titleAndText,
    .titleAndTextRight {
        display: flex;
        flex-direction: column;
        width: 150px;
    }

    .titleAndTextComplete {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
</style>