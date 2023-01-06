<?php
/**
 * Custom Order Numbers for WooCommerce - Settings
 *
 * @version 1.2.0
 * @since   1.0.0
 * @author  Tyche Softwares
 * @package Custom-Order-Numbers-Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Alg_WC_Settings_Custom_Order_Numbers' ) ) :

    /**
     * Class File for Plugin Settings.
     */
    class Alg_WC_Settings_Custom_Order_Numbers extends WC_Settings_Page {

        /**
         * Constructor.
         *
         * @version 1.0.0
         * @since   1.0.0
         */
        public function __construct() {
            $this->id    = 'epayco-woocommerce';
            $this->label = __( 'Custom Order Numbers', 'epayco-woocommerce' );
            parent::__construct();
        }

        /**
         * Get_settings.
         *
         * @version 1.0.0
         * @since   1.0.0
         */
        public function get_settings() {
            global $current_section;
            return apply_filters( 'woocommerce_get_settings_' . $this->id . '_' . $current_section, array() );
        }

        /**
         * Maybe_reset_settings.
         *
         * @version 1.2.0
         * @since   1.2.0
         */
        public function maybe_reset_settings() {
            global $current_section;
            if ( 'yes' === get_option( $this->id . '_' . $current_section . '_reset', 'no' ) ) {
                foreach ( $this->get_settings() as $value ) {
                    if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
                        delete_option( $value['id'] );
                        $autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
                        add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
                    }
                }
            }
        }

        /**
         * Save settings.
         *
         * @version 1.2.0
         * @since   1.2.0
         * @todo    [now] maybe reload page after save
         */
        public function save() {
            parent::save();
            $this->maybe_reset_settings();
        }

    }

endif;

return new Alg_WC_Settings_Custom_Order_Numbers();
