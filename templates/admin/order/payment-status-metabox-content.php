<?php

/**
 * @var string $tip
 * @var string $title
 * @var string $value
 *
 * @see \Epayco\Woocommerce\Hooks\Order
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<div id="ep-payment-status-container" style="display: flex;">
    <div id="ep-payment-status-content" class="ep-status-sync-metabox-content"
         style="border-left: 4px solid <?php echo esc_html($border_left_color); ?>;
                 min-height: 70px;
                 display: inline-flex;
                align-items: center;
                 width: 50%;
                 ">
        <div class="ep-status-sync-metabox-icon" style="width: 0 !important; padding: 10px; display: contents;">
            <img
                alt="alert"
                src="<?php echo esc_url($img_src); ?>"
                class="ep-status-sync-metabox-circle-img"
                style="padding: 10px;"
            />
        </div>

        <div class="ep-status-sync-metabox-text">
            <h2 class="ep-status-sync-metabox-title" style="font-weight: 700; padding: 12px 0 0 0; : 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 16px">
                <?php echo esc_html($alert_title); ?>
            </h2>

            <p class="ep-status-sync-metabox-description" style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                <?php echo esc_html($alert_description); ?>
            </p>

            <!--<p style="margin: 12px 0 4px; display: flex; align-items: center; justify-content: flex-start;">

                <button type="button" id="ep-sync-payment-status-button" class="ep-status-sync-metabox-button primary">
                    <span><?php echo esc_html($sync_button_text); ?></span>
                    <div class="ep-status-sync-metabox-small-loader" style="display: none"></div>
                </button>

                <a
                    href="<?php echo esc_url($link); ?>"
                    target="__blank"
                    class="ep-status-sync-metabox-link"
                >
                    <?php echo esc_html($link_description); ?>
                </a>
            </p>-->
        </div>
    </div>

    <div>
        <div class="order_data_column_container">
            <div class="order_data_column">
                <div class="address">
                    <p><strong>Ref_payco:</strong> <?php echo esc_html($ref_payco); ?></p>
                    <p><strong>Modo:</strong> <?php echo esc_html($test); ?></p>
                </div>
            </div>
            <div class="order_data_column">
                <div class="address">
                    <p><strong>Fecha y hora transacción:</strong> <?php echo esc_html($transactionDateTime); ?></p>
                    <p><strong>Franquicia/Medio de pago:</strong> <?php echo esc_html($bank); ?></p>
                </div>
            </div>
            <div class="order_data_column">
                <div class="address">
                    <p><strong>Código de autorización:</strong> <?php echo esc_html($authorization); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
