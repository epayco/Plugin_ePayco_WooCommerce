<?php

/**
 * Part of Woo Epayco Module
 * Author - Epayco
 * Developer
 * Copyright - Copyright(c) Epayco [https://www.epayco.com]
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * @package Epayco
 *
 * @var array $settings
 *
 * @see \Epayco\Woocommerce\Gateways\AbstractGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<tr valign="top">
    <td class="forminp">
        <div class="credits-info-example-text">
            <label><?= esc_html($settings['title']) ?></label>
            <p><?= esc_html($settings['subtitle']) ?></p>
        </div>
        <div class="credits-info-preview-container">
            <div class="credits-info-example-image-container">
                <p class="credits-info-example-preview-pill"><?= esc_html($settings['pill_text']) ?></p>
                <div class="credits-info-example-image">
                    <img alt='example' src="<?= esc_html($settings['image']) ?>">
                </div>
                <p class="credits-info-example-preview-footer"><?= esc_html($settings['footer']) ?></p>
            </div>
        </div>
    </td>
</tr>
