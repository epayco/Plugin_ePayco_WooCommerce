<?php

/**
 * @var array $settings
 *
 * @see \Epayco\Woocommerce\Gateways\AbstractGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

?>


<p  class="ep-support-link-text">
   <span class="ep-support-link-bold_text"><?= esc_html($settings['bold_text']) ?></span>
   <span><?= esc_html($settings['text_before_link']) ?></span>
   <span><a href="<?= esc_html($settings['support_link']) ?>" target="_blank" class="ep-support-link-text-with-link"><?= esc_html($settings['text_with_link']) ?></a></span>
   <span><?= esc_html($settings['text_after_link']) ?></span>
</p>
