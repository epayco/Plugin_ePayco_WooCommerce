<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WCGatewayEpaycoUnitTest extends TestCase
{
    private static string $EPAYCO_CUSTOMER_ID;
    private static string $EPAYCO_SECRET_KEY;
    private static string $EPAYCO_PUBLIC_KEY;
    private static string $EPAYCO_PRIVATE_KEY;
    private static int    $X_COD_TRANSACTION_STATE;
    private static int    $X_REF_PAYCO;
    private static string $X_TRANSACTION_ID;
    private static string $X_AMOUNT;
    private static string $X_CURRENCY_CODE;
    private static string $X_TEST_REQUEST;
    private static string $X_APPROVAL_CODE;
    private static string $X_FRANCHISE;
    private static string $X_FECHA_TRANSACCION;
    private static string $X_CURRENCY;
    private static string $X_SIGNATURE;
    private static int    $ID_ORDER;
    private static string $EPAYCO_ENDORDER_STATE;
    private static string $EPAYCO_CANCELLED_ENDORDER_STATE;
    private static string $EPAYCO_REDUCE_STOCK_PENDING;
    private static string $EPAYCO_TESTMODE;
    private static string $EPAYCO_VALIDATION_URL;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$EPAYCO_CUSTOMER_ID              = getenv('EPAYCO_CUSTOMER_ID');
        self::$EPAYCO_SECRET_KEY               = getenv('EPAYCO_SECRET_KEY');
        self::$EPAYCO_PUBLIC_KEY               = getenv('EPAYCO_PUBLIC_KEY');
        self::$EPAYCO_PRIVATE_KEY              = getenv('EPAYCO_PRIVATE_KEY');
        self::$X_COD_TRANSACTION_STATE         = (int)(getenv('X_COD_TRANSACTION_STATE'));
        self::$X_REF_PAYCO                     = (int)(getenv('X_REF_PAYCO'));
        self::$X_TRANSACTION_ID                = getenv('X_TRANSACTION_ID');
        self::$X_AMOUNT                        = getenv('X_AMOUNT');
        self::$X_CURRENCY_CODE                 = getenv('X_CURRENCY_CODE');
        self::$X_TEST_REQUEST                  = getenv('X_TEST_REQUEST');
        self::$X_APPROVAL_CODE                 = getenv('X_APPROVAL_CODE');
        self::$X_FRANCHISE                     = getenv('X_FRANCHISE');
        self::$X_FECHA_TRANSACCION             = getenv('X_FECHA_TRANSACCION');
        self::$X_CURRENCY                      = getenv('X_CURRENCY');
        self::$X_SIGNATURE                     = getenv('X_SIGNATURE');
        self::$ID_ORDER                        = (int)(getenv('ID_ORDER'));
        self::$EPAYCO_ENDORDER_STATE           = getenv('EPAYCO_ENDORDER_STATE');
        self::$EPAYCO_CANCELLED_ENDORDER_STATE = getenv('EPAYCO_CANCELLED_ENDORDER_STATE');
        self::$EPAYCO_REDUCE_STOCK_PENDING     = getenv('EPAYCO_REDUCE_STOCK_PENDING');
        self::$EPAYCO_TESTMODE                 = getenv('EPAYCO_TESTMODE');
        self::$EPAYCO_VALIDATION_URL           = getenv('EPAYCO_VALIDATION_URL');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['stock_updates'] = [];
        $GLOBALS['wc_logs'] = [];
        $GLOBALS['wp_remote_get_map'] = [];
        $GLOBALS['wc_orders'] = [];
        $GLOBALS['wp_redirect_url'] = null;
        $GLOBALS['wp_safe_redirect_url'] = null;
        $GLOBALS['wp_options'] = ['woocommerce_manage_stock' => 'yes'];
        EpaycoOrder::$stockDiscountByOrder = [];
        WC_Gateway_Epayco::$logger = new WC_Logger();
        $_GET = [];
        $_REQUEST = [];
    }

    private function makeGateway(): WC_Gateway_Epayco
    {
        $reflection = new ReflectionClass(WC_Gateway_Epayco::class);
        $gateway = $reflection->newInstanceWithoutConstructor();
        $gateway->id = 'epayco';
        $gateway->settings = [
            'epayco_customerid'               => self::$EPAYCO_CUSTOMER_ID,
            'epayco_secretkey'                => self::$EPAYCO_SECRET_KEY,
            'epayco_publickey'                => self::$EPAYCO_PUBLIC_KEY,
            'epayco_privatekey'               => self::$EPAYCO_PRIVATE_KEY,
            'epayco_endorder_state'           => self::$EPAYCO_ENDORDER_STATE,
            'epayco_cancelled_endorder_state' => self::$EPAYCO_CANCELLED_ENDORDER_STATE,
            'epayco_reduce_stock_pending'     => self::$EPAYCO_REDUCE_STOCK_PENDING,
            'epayco_testmode'                 => self::$EPAYCO_TESTMODE,
        ];
        return $gateway;
    }

    public function test_auth_signature_validation_uses_expected_hash(): void
    {
        $gateway = $this->makeGateway();

        $signature = $gateway->authSignature(self::$X_REF_PAYCO, self::$X_TRANSACTION_ID, self::$X_AMOUNT, self::$X_CURRENCY);
        $expected = self::$X_SIGNATURE;

        $this->assertSame($expected, $signature);
    }

    public function test_state_code_mapping_returns_expected_values(): void
    {
        $gateway = $this->makeGateway();

        $this->assertSame(1, $gateway->get_epayco_estado_codigo_detallado('aceptada'));
        $this->assertSame(2, $gateway->get_epayco_estado_codigo_detallado('rechazada'));
        $this->assertSame(4, $gateway->get_epayco_estado_codigo_detallado('fallida'));
        $this->assertSame(3, $gateway->get_epayco_estado_codigo_detallado('pendiente'));
    }

    public function test_get_ref_payco_returns_mapped_payload_from_api_response(): void
    {
        $gateway = $this->makeGateway();
        $url = self::$EPAYCO_VALIDATION_URL . self::$X_REF_PAYCO;

        $GLOBALS['wp_remote_get_map'][$url] = [
            'body' => json_encode([
                'status' => true,
                'data' => [
                    'x_signature' => self::$X_SIGNATURE,
                    'x_cod_transaction_state' => self::$X_COD_TRANSACTION_STATE,
                    'x_ref_payco' => (string) self::$X_REF_PAYCO,
                    'x_transaction_id' => self::$X_TRANSACTION_ID,
                    'x_amount' => self::$X_AMOUNT,
                    'x_currency_code' => self::$X_CURRENCY_CODE,
                    'x_test_request' => self::$X_TEST_REQUEST,
                    'x_approval_code' => self::$X_APPROVAL_CODE,
                    'x_franchise' => self::$X_FRANCHISE,
                    'x_fecha_transaccion' => self::$X_FECHA_TRANSACCION,
                ],
            ]),
            'response' => ['code' => 200],
        ];

        $result = $gateway->getRefPayco((string) self::$X_REF_PAYCO);

        $this->assertIsArray($result);
        $this->assertSame(self::$X_SIGNATURE, $result['x_signature']);
        $this->assertSame(self::$X_COD_TRANSACTION_STATE, $result['x_cod_transaction_state']);
        $this->assertSame((string) self::$X_REF_PAYCO, $result['x_ref_payco']);
        $this->assertSame(self::$X_TRANSACTION_ID, $result['x_transaction_id']);
        $this->assertSame(self::$X_AMOUNT, $result['x_amount']);
        $this->assertSame(self::$X_CURRENCY_CODE, $result['x_currency_code']);
    }
    

    public function test_order_confirmation_and_marks_stock(): void
    {
        $orderId = self::$ID_ORDER;
        $order = new WC_Order($orderId);
        $GLOBALS['wc_orders'][$orderId] = $order;

        $gateway = new class extends WC_Gateway_Epayco {
            public array $mockResponse = [];

            public function getRefPayco($refPayco)
            {
                return $this->mockResponse;
            }
        };
        $gateway->settings = [
            'epayco_customerid'               => self::$EPAYCO_CUSTOMER_ID,
            'epayco_secretkey'                => self::$EPAYCO_SECRET_KEY,
            'epayco_publickey'                => self::$EPAYCO_PUBLIC_KEY,
            'epayco_privatekey'               => self::$EPAYCO_PRIVATE_KEY,
            'epayco_endorder_state'           => self::$EPAYCO_ENDORDER_STATE,
            'epayco_cancelled_endorder_state' => self::$EPAYCO_CANCELLED_ENDORDER_STATE,
            'epayco_reduce_stock_pending'     => self::$EPAYCO_REDUCE_STOCK_PENDING,
            'epayco_testmode'                 => 'no',
            'response_data' => 'no',
            'epayco_url_response' => 0,
        ];

        $signature = $gateway->authSignature(self::$X_REF_PAYCO, self::$X_TRANSACTION_ID, self::$X_AMOUNT, self::$X_CURRENCY);
        $mockResponse = [
            'x_signature' => $signature,
            'x_cod_transaction_state' => self::$X_COD_TRANSACTION_STATE,
            'x_ref_payco' => self::$X_REF_PAYCO,
            'x_transaction_id' => self::$X_TRANSACTION_ID,
            'x_amount' => self::$X_AMOUNT,
            'x_currency_code' => self::$X_CURRENCY_CODE,
            'x_test_request' => self::$X_TEST_REQUEST,
            'x_approval_code' => self::$X_APPROVAL_CODE,
            'x_franchise' => self::$X_FRANCHISE,
            'x_fecha_transaccion' => self::$X_FECHA_TRANSACCION,
        ];
        $gateway->mockResponse = $mockResponse;

        $_GET['order_id'] = (string)$orderId;
        $_GET['confirmation'] = '0';
        $_REQUEST['ref_payco'] = self::$X_REF_PAYCO;

        ob_start();
        $gateway->successful_request([]);
        ob_end_clean();


        switch (self::$X_COD_TRANSACTION_STATE) {
            case 1: // Approved
                $this->assertSame('on-hold', $order->get_status());
                $this->assertFalse(EpaycoOrder::ifStockDiscount($orderId));
                $this->assertNotEmpty($GLOBALS['wp_redirect_url']);
                break;

            case 2: case 4: case 10: case 11: // Rejected
                $this->assertSame('epayco-failed', $order->get_status());
                $this->assertNotEmpty($GLOBALS['stock_updates']);
                $this->assertSame('increase', $GLOBALS['stock_updates'][0]['direction']);
                $this->assertSame(2, $GLOBALS['stock_updates'][0]['qty']);
                $this->assertNotEmpty($GLOBALS['wp_redirect_url']); 
                break;

            case 3: case 7: // Pending
                $this->assertSame('pending', $order->get_status());
                $this->assertFalse(EpaycoOrder::ifStockDiscount($orderId));
                $this->assertNotEmpty($GLOBALS['wp_redirect_url']);
                break;
            
        }


    }
}