<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class Epayco_Transaction_Handler {

    public static function handle_transaction($order, $data, $settings) {
        $order_id = $order->get_id();
        $current_state = $order->get_status();
        $modo = $settings['test_mode'] === "true" ? "pruebas" : "Producción";

        $ref = $order->get_meta('refDavipay') ?: $order->get_meta('refPayco');
        $existingRefPayco = $ref;
        if (!empty($existingRefPayco) && $existingRefPayco === $data['x_ref_payco']) {
          
            //return;
        }
        self::save_epayco_metadata($order, $modo, $data);

        $estado_final_exitoso = self::get_success_status($settings);
        $estado_cancelado = self::get_cancel_status($settings);
        
        switch ($data['x_cod_transaction_state']) {
            case 1: // Approved
                self::handle_approved($order, $order_id, $current_state, $settings, $estado_final_exitoso,$data['x_franchise']);
                echo "1";
                break;

            case 2: case 4: case 10: case 11: // Cancelled, failed or rejected
            self::handle_failed($order, $current_state, $estado_cancelado, $data['is_confirmation'],$settings,$data['x_franchise']);
            echo "2";
            break;

            case 3: case 7: // Pending
            self::handle_pending($order, $order_id, $current_state, $settings,$data['x_franchise']);
            echo "3";
            break;

            case 6: // Reversed
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
        $order->update_meta_data('refDavipay', esc_attr($data['x_ref_payco']));
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

    private static function success_states() {
        return [
            'processing', 'completed', 'processing_test', 'completed_test',
            'epayco-processing', 'epayco-completed', 'epayco_processing', 'epayco_completed'
        ];
    }

    private static function handle_approved($order, $order_id, $current_state, $settings, $estado_final_exitoso, $franchise) {
    $logger = new WC_Logger();
    try {
        $reduce_pending = ($settings['reduce_stock_pending'] ?? 'yes') === 'yes';

        if ($current_state === 'pending') {
            // Aprobado pero a la espera de confirmación: solo descuenta si la opción lo pide.
            $order->update_status('on-hold');
            $order->save();
            if ($reduce_pending) {
                self::reduce_stock($order_id);
            }
            return;
        }

        if (!in_array($current_state, self::success_states(), true)) {
            $ref = $order->get_meta('refDavipay') ?: $order->get_meta('refPayco');
            $order->payment_complete($ref);
            $order->update_status($estado_final_exitoso);
            $order->save();
        }

        // Aprobación final: descuenta una sola vez (idempotente).
        self::reduce_stock($order_id);
    } catch (\Exception $ex) {
        $error_message = "handle_approved got error: {$ex->getMessage()}";
        $logger->add('handle_approved', $error_message);
        throw new \Exception($error_message);
    }
}

    private static function handle_failed($order, $current_state, $estado_cancelado, $isConfirmation, $settings, $franchise) {
        $logger = new WC_Logger();
        try {
            // Un pedido ya pagado no se cancela por un webhook tardío.
            if (in_array($current_state, self::success_states(), true)) {
                return;
            }

            $order->update_status($estado_cancelado);
            $order->save();

            // Restaura solo si el stock estaba descontado (p. ej. pendiente con reserva).
            // En rechazos sucesivos no hace nada.
            self::restore_stock_once($order->get_id());

            if ($settings['reduce_stock_pending'] === "yes" && $current_state === 'pending' && !$isConfirmation) {
                wp_safe_redirect(wc_get_checkout_url());
                exit;
            }
        } catch (\Exception $ex) {
            $error_message = "handle_failed got error: {$ex->getMessage()}";
            $logger->add('handle_failed', $error_message);
            throw new \Exception($error_message);
        }
    }

    private static function handle_pending($order, $order_id, $current_state, $settings, $franchise) {
        if (in_array($current_state, self::success_states(), true)) {
            return;
        }

        if ($current_state !== 'on-hold') {
            $order->update_status('on-hold');
        }

        // Reserva de stock al entrar en pendiente, solo si la opción está activa.
        if ($settings['reduce_stock_pending'] === "yes") {
            self::reduce_stock($order_id);
        }
    }

    private static function handle_reversed($order) {
        $order->update_status('refunded');
        $order->add_order_note('Payment Reversed');
        self::restore_stock_once($order->get_id());
    }

    private static function handle_default($order, $current_state) {
        if (!in_array($current_state, ['processing', 'completed'], true)) {
            $order->update_status('epayco-failed');
            $order->add_order_note('Payment failed or abandoned');
            self::restore_stock_once($order->get_id());
        }
    }



   /* ---------- Helpers de stock (idempotentes, usan el flag nativo de WooCommerce) ---------- */

    public static function reduce_stock($order_id) {
        if (function_exists('wc_reduce_stock_levels')) {
            wc_reduce_stock_levels($order_id);
        }
    }

    public static function restore_stock_once($order_id) {
        if (function_exists('wc_increase_stock_levels')) {
            wc_increase_stock_levels($order_id);
        }
    }

    // Se mantiene por compatibilidad con llamadas existentes.
    public static function restore_stock($order_id, $direction = 'increase') {
        if ($direction === 'decrease') {
            self::reduce_stock($order_id);
        } else {
            self::restore_stock_once($order_id);
        }
    }
}