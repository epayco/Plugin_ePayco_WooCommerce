<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value)
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($url)
    {
        $GLOBALS['last_redirect_url'] = (string) $url;
        return true;
    }
}

if (!function_exists('wp_redirect')) {
    function wp_redirect($url)
    {
        $GLOBALS['last_redirect_url'] = (string) $url;
        return true;
    }
}

if (!function_exists('wc_get_checkout_url')) {
    function wc_get_checkout_url()
    {
        return 'https://example.test/checkout';
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($postId)
    {
        return 'https://example.test/page/' . (string) $postId;
    }
}

if (!function_exists('update_option')) {
    function update_option($key, $value)
    {
        $GLOBALS['wp_options'][$key] = $value;
        return true;
    }
}

if (!function_exists('get_option')) {
    function get_option($key)
    {
        $options = $GLOBALS['wp_options'] ?? [];
        return $options[$key] ?? null;
    }
}

if (!class_exists('EpaycoOrder')) {
    class EpaycoOrder
    {
        public static function ifStockDiscount($orderId)
        {
            return false;
        }

        public static function updateStockDiscount($orderId, $value)
        {
            return true;
        }

        public static function ifExist($orderId)
        {
            return true;
        }

        public static function create($orderId, $value)
        {
            return true;
        }
    }
}

if (!class_exists('WC_Order')) {
    class WC_Order
    {
        private int $id;
        private string $status;
        private float $total;
        private array $meta;

        public function __construct($orderId)
        {
            $this->id = (int) $orderId;
            $seed = $GLOBALS['integration_orders'][$this->id] ?? [];
            $this->status = (string) ($seed['status'] ?? 'pending');
            $this->total = (float) ($seed['total'] ?? 76000.0);
            $this->meta = (array) ($seed['meta'] ?? []);
        }

        public function get_id()
        {
            return $this->id;
        }

        public function get_status()
        {
            return $this->status;
        }

        public function update_status($status)
        {
            $this->status = (string) $status;
        }

        public function get_meta($key)
        {
            return $this->meta[$key] ?? '';
        }

        public function update_meta_data($key, $value)
        {
            $this->meta[$key] = $value;
        }

        public function add_meta_data($key, $value)
        {
            $existing = $this->meta[$key] ?? '';
            if ($existing === '' || $existing === null) {
                $this->meta[$key] = (string) $value;
                return;
            }

            $this->meta[$key] = (string) $existing . ', ' . (string) $value;
        }

        public function save()
        {
            $GLOBALS['integration_orders'][$this->id] = [
                'status' => $this->status,
                'total' => $this->total,
                'meta' => $this->meta,
            ];
            return true;
        }

        public function get_total()
        {
            return $this->total;
        }

        public function payment_complete($ref)
        {
            $this->meta['payment_complete_ref'] = (string) $ref;
        }

        public function get_checkout_order_received_url()
        {
            return 'https://example.test/order-received/' . $this->id;
        }

        public function get_items()
        {
            return [];
        }
    }
}

final class SuccessfulRequestIntegrationTest extends TestCase
{
    private const X_COD_TRANSACTION_STATE = 1;
    private const CUSTOMER_ID = '627579';
    private const SECRET_KEY = '170e3e02b3aa6086c6c020a25b1a7ff2e7c52585';
    private const ID_ORDER = 42;
    private const X_REF_PAYCO = '101767532';
    private const X_APPROVAL_CODE = '48771874411906';
    private const X_FRANCHISE = 'GA';
    private const X_FECHA_TRANSACCION = '2026-03-04 11:22:28';
    private const X_AMOUNT = 38000;
    private const X_CURRENCY = 'COP';
    private const END_ORDER_STATE = 'processing';
    private const CANCEL_ORDER_STATE = 'epayco-failed';

    private function useRealWooIntegration(): bool
    {
        return getenv('RUN_WC_REAL_INTEGRATION') === '1';
    }

    private function resolveOrderId(): int
    {
        $envOrderId = (int) (getenv('WC_REAL_ORDER_ID') ?: 0);
        return $envOrderId > 0 ? $envOrderId : self::ID_ORDER;
    }

    private function expectedOutputByOutcome(string $outcome): string
    {
        return match ($outcome) {
            'aceptada' => '1',
            'rechazada' => '2',
            'pendiente' => '3',
            default => 'default',
        };
    }

    private function resolveTransactionOutcome(int $transactionState): string
    {
        if ($transactionState === 1) {
            return 'aceptada';
        }

        if (in_array($transactionState, [2, 4, 10, 11], true)) {
            return 'rechazada';
        }

        if (in_array($transactionState, [3, 7], true)) {
            return 'pendiente';
        }

        return 'desconocida';
    }

    private function expectedStatusByOutcome(int $transactionState, string $initialStatus): string
    {
        if ($transactionState === 1) {
            return self::END_ORDER_STATE;
        }

        if (in_array($transactionState, [2, 4, 10, 11], true)) {
            return self::CANCEL_ORDER_STATE;
        }

        if (in_array($transactionState, [3, 7], true)) {
            return 'on-hold';
        }

        return 'epayco-failed';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['integration_orders'] = [];
        $GLOBALS['wp_options'] = [];
        $GLOBALS['last_redirect_url'] = null;
        $_GET = [];
        $_REQUEST = [];
    }

    public function test_successful_request_sets_expected_order_state_and_output_by_transaction_state(): void
    {
        $outcome = $this->resolveTransactionOutcome(self::X_COD_TRANSACTION_STATE);
        $expectedOutput = $this->expectedOutputByOutcome($outcome);
        $expectedStatus = $this->expectedStatusByOutcome(self::X_COD_TRANSACTION_STATE, 'on-hold');
        if (!$this->useRealWooIntegration()) {
            $this->markTestSkipped('Set RUN_WC_REAL_INTEGRATION=1 and WC_REAL_ORDER_ID to validate real DB updates.');
        }

        if (!function_exists('wc_get_order')) {
            $this->markTestSkipped('wc_get_order no está disponible; verifica WP_LOAD_PATH para cargar WordPress.');
        }

        $orderId = $this->resolveOrderId();
        $refPayco = self::X_REF_PAYCO;
        $transactionId = self::X_APPROVAL_CODE;
        $amount = (string) self::X_AMOUNT;
        $currency = self::X_CURRENCY;

        $order = wc_get_order($orderId);
        if (!$order) {
            $this->markTestSkipped('No existe la orden real con ID ' . $orderId . '.');
        }

        // $order->update_status('pending');
        // $order->update_meta_data('refPayco', '');
        // $order->save();

        $realTotal = (string) ((float) $order->get_total());
        if ($realTotal !== '' && (float) $realTotal > 0) {
            $amount = $realTotal;
        }

        $reflection = new ReflectionClass(WC_Gateway_Epayco::class);
        $gateway = $reflection->newInstanceWithoutConstructor();
        $gateway->id = 'epayco';
        $gateway->settings = [
            'epayco_customerid' => self::CUSTOMER_ID,
            'epayco_secretkey' => self::SECRET_KEY,
            'epayco_testmode' => 'no',
            'epayco_endorder_state' => 'processing',
            'epayco_cancelled_endorder_state' => 'epayco-failed',
            'reduce_stock_pending' => 'yes',
            'epayco_url_response' => 0,
            'response_data' => 'no',
        ];

        WC_Gateway_Epayco::$logger = new WC_Logger();

        $signature = $gateway->authSignature($refPayco, $transactionId, $amount, $currency);

        $_GET['order_id'] = $orderId . '?ref_payco=' . $refPayco;
        $_GET['confirmation'] = '1';

        $_REQUEST = [
            'x_signature' => $signature,
            'x_cod_transaction_state' => (string) self::X_COD_TRANSACTION_STATE,
            'x_ref_payco' => self::X_REF_PAYCO,
            'x_transaction_id' => self::X_APPROVAL_CODE,
            'x_amount' => $amount,
            'x_currency_code' => $currency,
            'x_test_request' => 'FALSE',
            'x_approval_code' => self::X_APPROVAL_CODE,
            'x_franchise' => self::X_FRANCHISE,
            'x_fecha_transaccion' => self::X_FECHA_TRANSACCION,
        ];

        if (function_exists('add_filter')) {
            add_filter('woocommerce_register_log_handlers', static function ($handlers) {
                return [];
            }, 999);

            add_filter('wp_redirect', static function ($location) {
                $GLOBALS['last_redirect_url'] = $location;
                return false;
            }, 999);
        }

        ob_start();
        $gateway->successful_request($_REQUEST);
        $output = ob_get_clean();

        $savedOrder = wc_get_order($orderId);

        $this->assertStringStartsWith((string) self::X_COD_TRANSACTION_STATE, trim((string) $output));
        $this->assertSame($expectedOutput, trim((string) $output));
        $this->assertSame($expectedStatus, $savedOrder ? $savedOrder->get_status() : null);
        $this->assertSame(self::X_REF_PAYCO, $savedOrder ? $savedOrder->get_meta('refPayco') : null);
        $this->assertNotEmpty($GLOBALS['last_redirect_url']);
    }
}
