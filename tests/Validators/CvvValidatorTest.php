<?php

namespace App\Tests\Validators;

use App\CreditCard;
use App\Validators\CvvValidator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the CvvValidator class.
 */
class CvvValidatorTest extends TestCase
{
    private CvvValidator $validator;
    private array $validResult = ['valid' => true, 'message' => ''];
    private array $skippedResult = ['valid' => true, 'message' => 'CVV validation skipped for unknown card type.'];
    private array $wrongLengthResult = ['valid' => false, 'message' => 'Invalid CVV length for Visa/Mastercard. Must be 3 digits.'];
    private array $nonNumericResult = ['valid' => false, 'message' => 'CVV must be numeric.'];
    private array $emptyCvvResult = ['valid' => false, 'message' => 'CVV is required and must be numeric.'];


    protected function setUp(): void
    {
        $this->validator = new CvvValidator();
    }

    // --- Data Providers ---

    /**
     * Provides valid card numbers and their corresponding valid CVVs.
     * @return array<array{string, string}>
     */
    public function validCvvsProvider(): array
    {
        return [
            // Visa - 3 digits
            ['4111111111111111', '123'], // Visa, 16 digits
            ['4111111111111', '987'],    // Visa, 13 digits
            ['4111111111111111111', '000'],// Visa, 19 digits

            // Mastercard - 3 digits
            ['5100000000000000', '321'], // Mastercard (51-55 range)
            ['5599999999999999', '789'], // Mastercard (51-55 range)
            ['2221000000000000', '456'], // Mastercard (2221-2720 range)
            ['2720999999999999', '654'], // Mastercard (2221-2720 range)
        ];
    }

    /**
     * Provides card numbers with CVVs of incorrect lengths.
     * @return array<array{string, string}>
     */
    public function invalidCvvsWrongLengthProvider(): array
    {
        return [
            // Visa
            ['4111111111111111', '12'],   // Too short
            ['4111111111111111', '1234'], // Too long

            // Mastercard
            ['5100000000000000', '12'],   // Too short
            ['2221000000000000', '1234'], // Too long
        ];
    }

    /**
     * Provides CVVs containing non-numeric characters or empty.
     * Card numbers provided are for known types (Visa/Mastercard) to ensure CVV check is triggered.
     * @return array<array{string, string}>
     */
    public function invalidCvvsNonNumericOrEmptyProvider(): array
    {
        return [
            ['4111111111111111', '12a'],   // Visa, non-numeric
            ['5100000000000000', 'b34'],   // Mastercard, non-numeric
            ['2221000000000000', '1c3'],   // Mastercard, non-numeric
            ['4111111111111111', 'abc'],   // Visa, non-numeric
            ['4111111111111111', '1 2'],   // Visa, non-numeric (space)
            ['4111111111111111', ''],      // Visa, Empty
        ];
    }

    /**
     * Provides unsupported card numbers with potentially valid CVV formats.
     * @return array<array{string, string}>
     */
    public function unsupportedCardTypeProvider(): array
    {
        return [
            ['340000000000000', '1234'], // Amex-like prefix, 4-digit CVV
            ['370000000000000', '987'],  // Amex-like prefix, 3-digit CVV
            ['6011000000000000', '123'], // Discover-like prefix, 3-digit CVV
            ['1234567890123', '000'],    // Unknown prefix, 3-digit CVV
        ];
    }

    // --- Test Methods ---

    /**
     * @dataProvider validCvvsProvider
     * @param string $cardNumber The credit card number.
     * @param string $cvv The valid CVV for the card.
     * @covers \App\Validators\CvvValidator::validate
     */
    public function testValidCvvs(string $cardNumber, string $cvv): void
    {
        $card = new CreditCard($cardNumber, 'Test Holder', 12, 2030, $cvv);
        $this->assertSame($this->validResult, $this->validator->validate($card), "Failed for valid CVV {$cvv} with card {$cardNumber}");
    }

    /**
     * @dataProvider invalidCvvsWrongLengthProvider
     * @param string $cardNumber The credit card number.
     * @param string $cvv The CVV with incorrect length.
     * @covers \App\Validators\CvvValidator::validate
     */
    public function testInvalidCvvsWrongLength(string $cardNumber, string $cvv): void
    {
        $card = new CreditCard($cardNumber, 'Test Holder', 12, 2030, $cvv);
        $this->assertSame($this->wrongLengthResult, $this->validator->validate($card), "Passed for CVV {$cvv} (wrong length) with card {$cardNumber}");
    }

    /**
     * @dataProvider invalidCvvsNonNumericOrEmptyProvider
     * @param string $cardNumber The credit card number.
     * @param string $cvv The non-numeric or empty CVV.
     * @covers \App\Validators\CvvValidator::validate
     */
    public function testInvalidCvvsNonNumericOrEmpty(string $cardNumber, string $cvv): void
    {
        $card = new CreditCard($cardNumber, 'Test Holder', 12, 2030, $cvv);
        $expectedResult = empty($cvv) ? $this->emptyCvvResult : $this->nonNumericResult;
        $this->assertSame($expectedResult, $this->validator->validate($card), "Validation for CVV '{$cvv}' failed with card {$cardNumber}");
    }

    /**
     * @dataProvider unsupportedCardTypeProvider
     * @param string $cardNumber Card number of an unsupported type.
     * @param string $cvv CVV (could be valid for its actual type).
     * @covers \App\Validators\CvvValidator::validate
     */
    public function testCvvForUnsupportedCardType(string $cardNumber, string $cvv): void
    {
        $card = new CreditCard($cardNumber, 'Test Holder', 12, 2030, $cvv);
        $this->assertSame($this->skippedResult, $this->validator->validate($card), "Failed for CVV {$cvv} with unsupported card type {$cardNumber}. Expected to be skipped.");
    }
}
