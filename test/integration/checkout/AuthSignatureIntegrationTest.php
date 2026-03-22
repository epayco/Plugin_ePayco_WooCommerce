<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AuthSignatureIntegrationTest extends TestCase
{
    private const EPAYCO_CUSTOMER_ID = '';
    private const EPAYCO_SECRET_KEY = '';
    private const X_REF_PAYCO = '';
    private const X_TRANSACTION_ID = '';
    private const X_AMOUNT = '';
    private const X_CURRENCY_CODE = '';
    private const EXPECTED_SIGNATURE = '';

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