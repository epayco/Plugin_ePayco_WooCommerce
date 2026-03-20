<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AuthSignatureIntegrationTest extends TestCase
{
    private const EPAYCO_CUSTOMER_ID = '9898';
    private const EPAYCO_SECRET_KEY = 'aeccf023f2ff3f568d5c4c5a157db66e41cd9334';
    private const X_REF_PAYCO = '347270768';
    private const X_TRANSACTION_ID = '48772121300385';
    private const X_AMOUNT = '10000';
    private const X_CURRENCY_CODE = 'COP';
    private const EXPECTED_SIGNATURE = '5a4023f83b2f3d087ee6e14050d81761e70cf42053aff6b743b95e7e2bc14cee';

    public function test_auth_signature_matches_expected_value(): void
    {
        $reflection = new ReflectionClass(WC_Gateway_Epayco::class);
        $gateway = $reflection->newInstanceWithoutConstructor();
        $gateway->settings = [
            'epayco_customerid' => self::EPAYCO_CUSTOMER_ID,
            'epayco_secretkey' => self::EPAYCO_SECRET_KEY,
        ];

        $signature = $gateway->authSignature(
            self::X_REF_PAYCO,
            self::X_TRANSACTION_ID,
            self::X_AMOUNT,
            self::X_CURRENCY_CODE
        );

        $this->assertSame(self::EXPECTED_SIGNATURE, $signature);
    }
}