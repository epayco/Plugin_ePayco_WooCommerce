<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class Epayco_Transaction_Handler {

    public static function handle_transaction($order, $data, $settings) {
        $order_id = $order->get_id();
        $current_state = $order->get_status();
        $modo = $settings['test_mode'] === "true" ? "pruebas" : "Producción";

        self::save_epayco_metadata($order, $modo, $data);

        $estado_final_exitoso = self::get_success_status($settings);
        $estado_cancelado = self::get_cancel_status($settings);

        switch ($data['x_cod_transaction_state']) {
            case 1: // Aprobada
                self::handle_approved($order, $order_id, $current_state, $settings, $estado_final_exitoso,$data['x_franchise']);
                echo "1";
                break;

            case 2: case 4: case 10: case 11: // Cancelada, fallida o rechazada
            self::handle_failed($order, $current_state, $estado_cancelado, $data['is_confirmation'],$settings,$data['x_franchise']);
            echo "2";
            break;

            case 3: case 7: // Pendiente
            self::handle_pending($order, $order_id, $current_state, $settings,$data['x_franchise']);
            echo "3";
            break;

            case 6: // Reversado
                self::handle_reversed($order);
                echo "6";
                break;

            default:
                self::handle_default($order, $current_state);
                echo "default";
                break;
        }
    }

    private static function save_epayco_metadata($order, $modo, $data) {
        $order->update_meta_data('refPayco', esc_attr($data['x_ref_payco']));
        $order->update_meta_data('modo', esc_attr($modo));
        $order->update_meta_data('fecha', esc_attr($data['x_fecha_transaccion']));
        $order->update_meta_data('franquicia', esc_attr($data['x_franchise']));
        $order->update_meta_data('autorizacion', esc_attr($data['x_approval_code']));
        $order->save();
    }

    private static function get_success_status($settings) {
        if ($settings['test_mode'] === "true") {
            return ($settings['end_order_state'] == "processing") ? "processing_test" :
                ( ($settings['end_order_state'] == "completed") ? "completed_test" :
                    ( ($settings['end_order_state'] == "epayco-processing") ? "epayco_processing" :
                        ( ($settings['end_order_state'] == "epayco-completed") ? "epayco_completed" :
                            $settings['end_order_state'] )));
        }
        return $settings['end_order_state'];
    }

    private static function get_cancel_status($settings) {
        if ($settings['test_mode'] === "true") {
            return ($settings['cancel_order_state'] == "cancelled") ? "cancelled" :
                ( ($settings['cancel_order_state'] == "epayco-cancelled") ? "epayco_cancelled" :
                    ( ($settings['cancel_order_state'] == "epayco-failed") ? "epayco_failed" : "failed"));
        }
        return $settings['cancel_order_state'];
    }

    private static function handle_approved($order, $order_id, $current_state, $settings, $estado_final_exitoso,$franchise) {
        try{
            $logger = new WC_Logger();
            if ($settings['reduce_stock_pending'] === "yes" && in_array($current_state, ['epayco_failed', 'epayco_cancelled', 'failed', 'canceled','epayco-failed', 'epayco-cancelled'])) {
                if (!EpaycoOrder::ifStockDiscount($order_id)) {
                    EpaycoOrder::updateStockDiscount($order_id, 1);
                    if ( in_array($estado_final_exitoso, ['epayco-processing', 'epayco-completed'])) {
                        self::restore_stock($order_id, 'decrease');
                    }
                }
            } else {
                if (!EpaycoOrder::ifStockDiscount($order_id)) {
                    EpaycoOrder::updateStockDiscount($order_id, 1);
                }
            }

            if (in_array($current_state, ['pending'])) {

                $order->update_status('on-hold');
                if ($settings['reduce_stock_pending'] !== "yes"){
                    self::restore_stock($order_id);
                }
            }

            if (!in_array($current_state, ['processing', 'completed', 'processing_test', 'completed_test','epayco-processing', 'epayco-completed','epayco_processing', 'epayco_completed',])) {

                $order->payment_complete($order->get_meta('refPayco'));
                $order->update_status($estado_final_exitoso);
                if ($settings['reduce_stock_pending'] !== "yes"){
                    self::restore_stock($order_id, 'decrease');
                }
            }
        }catch (\Exception $ex) {
            $error_message = "handle_approved got error: {$ex->getMessage()}";
            $logger->add('handle_approved', $error_message);
            throw new Exception($error_message);
        }
    }

    private static function handle_failed($order, $current_state, $estado_cancelado, $isConfirmation,$settings,$franchise) {
        try{
            $logger = new WC_Logger();
            if (!in_array($current_state, [
                'processing',
                'completed',
                'processing_test',
                'completed_test',
                'epayco-processing',
                'epayco-completed',
                'epayco_processing',
                'epayco_completed'
            ])) {
                $order->update_status($estado_cancelado);
                if ($settings['reduce_stock_pending'] === "yes" && in_array($current_state, ['pending', 'on-hold'])) {
                    if($current_state == 'pending'){
                        if (!$isConfirmation) {
                            wp_safe_redirect(wc_get_checkout_url());
                            exit;
                        }
                    }else{
                        self::restore_stock($order->get_id());
                    }
                }

            }
        }catch (\Exception $ex) {
            $error_message = "handle_failed got error: {$ex->getMessage()}";
            $logger->add('handle_failed', $error_message);
            throw new Exception($error_message);
        }
    }

    private static function handle_pending($order, $order_id, $current_state, $settings,$franchise) {
        try{
            $logger = new WC_Logger();
            if (!EpaycoOrder::ifStockDiscount($order_id) && $settings['reduce_stock_pending'] != 'yes') {
                EpaycoOrder::updateStockDiscount($order_id, 1);
            }
            if ($settings['reduce_stock_pending'] === "yes" && in_array($current_state, ['epayco_failed', 'epayco_cancelled', 'failed', 'canceled','epayco-failed', 'epayco-cancelled'])) {
               // self::restore_stock($order_id, 'decrease');
                $order->update_status('on-hold');
            }else{
                if ($current_state != 'on-hold') {
                    $order->update_status('on-hold');
                    if ($settings['reduce_stock_pending'] !== "yes"){
                        self::restore_stock($order_id);
                    }
                }
            }


        }catch (\Exception $ex) {
            $error_message = "handle_pending got error: {$ex->getMessage()}";
            $logger->add('handle_pending', $error_message);
            throw new Exception($error_message);
        }
    }

    private static function handle_reversed($order) {
        $order->update_status('refunded');
        $order->add_order_note('Pago Reversado');
        self::restore_stock($order->get_id());
    }

    private static function handle_default($order, $current_state) {
        if (!in_array($current_state, ['processing', 'completed'])) {
            $order->update_status('epayco-failed');
            $order->add_order_note('Pago fallido o abandonado');
            self::restore_stock($order->get_id());
        }
    }

    public static function restore_stock($order_id, $direction = 'increase') {
        $order = wc_get_order($order_id);
        if (!get_option('woocommerce_manage_stock') == 'yes' && !sizeof($order->get_items()) > 0) {
            return;
        }
        foreach ($order->get_items() as $item) {
            // Get an instance of corresponding the WC_Product object
            $product = $item->get_product();
            $qty = $item->get_quantity(); // Get the item quantity
            wc_update_product_stock($product, $qty, $direction);
        }
        if (function_exists('restore_order_stock')) {
            // restore_order_stock($order_id, $direction);
        }
    }
}
