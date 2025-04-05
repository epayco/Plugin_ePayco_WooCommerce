<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Dummy Payments Blocks integration
 *
 * @since 1.0.3
 */
final class CustomBlock extends AbstractPaymentMethodType {

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'epayco';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_epayco_settings', array() );

	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->get_epayco_option( 'enabled', 'epayco' );
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/assets/js/frontend/blocks.js';
		$script_url        = untrailingslashit( plugins_url( '/', __FILE__ ) ) . $script_path;

		wp_register_script(
			'wc-epayco-payments-blocks',
			$script_url,
            array(),
            '1.2.0',
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			//wp_set_script_translations( 'wc-epayco-payments-blocks', 'woo-epayco-gateway', plugin_abspath_epayco() . 'languages/' );
		}

		return array( 'wc-epayco-payments-blocks' );
	}

    public function get_epayco_option( $option, $gateway ) {

        $options = get_option( 'woocommerce_' . $gateway . '_settings' );

        if ( ! empty( $options ) ) {
            $epayco_options = maybe_unserialize( $options );
            if ( array_key_exists( $option, $epayco_options ) ) {
                $option_value = $epayco_options[ $option ];
                return $option_value;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


}
