<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Dummy Payments Blocks integration
 *
 * @since 1.0.3
 */
final class WC_Gateway_Epayco_Support extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_Epayco
	 */
	private $gateway;

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
		$script_url        = plugin_url_epayco() . $script_path;

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

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       =>  $this->get_epayco_option( 'title', 'epayco'),
			'description' => $this->get_epayco_option( 'description', 'epayco'),
			'supports'    => array(
				'products',
				'refunds',
			),
		);
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

