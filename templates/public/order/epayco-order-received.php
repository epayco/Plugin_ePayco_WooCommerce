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

<p>
<p>
    <?php echo esc_html($print_daviplata_label); ?>
</p>
<p><iframe src="<?php echo esc_attr($transaction_details); ?>" style="width:100%; height:600px;"></iframe></p>
<a id="submit-payment" target="_blank" href="<?php echo esc_attr($transaction_details); ?>" class="button alt" style="font-size:1.25rem; width:75%; height:48px; line-height:24px; text-align:center;">
    <?php echo esc_html($print_daviplata_link); ?>
</a>
</p>
