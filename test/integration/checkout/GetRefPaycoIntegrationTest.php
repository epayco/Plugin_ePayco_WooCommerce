<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class GetRefPaycoIntegrationTest extends TestCase
{
    private const DEFAULT_REF_PAYCO = '69bd8b3151d79d7d68426ee5';

    public function test_get_ref_payco_with_real_endpoint(): void
    {
        //RUN_INTEGRATION=1 composer run test:get-ref-payco
        if (getenv('RUN_INTEGRATION') !== '1') {
            $this->markTestSkipped('Set RUN_INTEGRATION=1 to execute integration tests.');
        }

        $reference = (string) (getenv('REF_PAYCO') ?: self::DEFAULT_REF_PAYCO);

        $reflection = new ReflectionClass(WC_Gateway_Epayco::class);
        $gateway = $reflection->newInstanceWithoutConstructor();
        $gateway->id = 'epayco';
        WC_Gateway_Epayco::$logger = new WC_Logger();

        $_GET['ref_payco'] = $reference;

        $result = $gateway->getRefPayco($reference);

        if ($result === false) {
            $this->markTestSkipped('Reference endpoint unavailable or returned invalid data.');
        }

        $this->assertIsArray($result);
        $this->assertArrayHasKey('x_ref_payco', $result);
        $this->assertArrayHasKey('x_transaction_id', $result);
        $this->assertArrayHasKey('x_cod_transaction_state', $result);
        $this->assertArrayHasKey('x_signature', $result);
    }
}