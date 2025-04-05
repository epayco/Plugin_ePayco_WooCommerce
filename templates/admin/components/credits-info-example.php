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

<div class="credits-info-example-text">
    <label><?php echo esc_html($title); ?></label>
    <p><?php echo esc_html($subtitle); ?></p>
</div>
<div class="credits-info-example-container">
    <div class="credits-info-example-buttons-container">
        <div class="credits-info-example-buttons-child selected">
            <div id="btn-first" class="credits-info-example-blue-badge"></div>
            <div class="credits-info-example-buttons-content">
                <div>
                    <img class="icon-image" alt="computer" src="<?php echo esc_html(plugins_url('../../assets/images/checkouts/credits/desktop-gray-icon.png', plugin_dir_path(__FILE__))); ?>">
                </div>
                <div>
                    <p><?php echo esc_html($desktop); ?>
                </div>
            </div>

        </div>
        <div class="credits-info-example-buttons-child">
            <div id="btn-second" class="credits-info-example-blue-badge"></div>
            <div class="credits-info-example-buttons-content">
                <div>
                    <img class="icon-image" alt="cellphone" src="<?php echo esc_html(plugins_url('../../assets/images/checkouts/credits/cellphone-gray-icon.png', plugin_dir_path(__FILE__))); ?>">
                </div>

                <div>
                    <p><?php echo esc_html($cellphone); ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="credits-info-example-gif-container">
        <div class="credits-info-example-gif">
            <img id="gif-image" alt="example" src="<?php echo esc_html(plugins_url('../../assets/images/checkouts/credits/view_desktop.gif', plugin_dir_path(__FILE__))); ?>">
        </div>
        <p id="credits-info-example-gif-footer">
        <?php echo esc_html($footer); ?>
        </p>
    </div>
</div>
