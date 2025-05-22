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
     * Provides CVVs containing non-numeric characters.
     * @return array<array{string, string}>
     */
    public function invalidCvvsNonNumericProvider(): array
    {
        return [
            ['4111111111111111', '12a'],
            ['5100000000000000', 'b34'],
            ['2221000000000000', '1c3'],
            ['4111111111111111', 'abc'],
            ['4111111111111111', '1 2'], // Space
            ['4111111111111111', ''],    // Empty
        ];
    }

    /**
     * Provides unsupported card numbers with potentially valid CVV formats.
     * @return array<array{string, string}>
     */
    public function unsupportedCardTypeProvider(): array
    {
        return [
            // Amex typically has 4-digit CVV, Discover 3-digit.
            // Validator should pass these for now.
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
     */
    public function testValidCvvs(string $cardNumber, string $cvv): void
    {
        $card = new CreditCard($cardNumber, 'Test Holder', 12, 2030, $cvv);
        $this->assertTrue($this->validator->validate($card), "Failed for valid CVV {$cvv} with card {$cardNumber}");
    }

    /**
     * @dataProvider invalidCvvsWrongLengthProvider
     * @param string $cardNumber The credit card number.
     * @param string $cvv The CVV with incorrect length.
     */
    public function testInvalidCvvsWrongLength(string $cardNumber, string $cvv): void
    {
        $card = new CreditCard($cardNumber, 'Test Holder', 12, 2030, $cvv);
        $this->assertFalse($this->validator->validate($card), "Passed for CVV {$cvv} (wrong length) with card {$cardNumber}");
    }

    /**
     * @dataProvider invalidCvvsNonNumericProvider
     * @param string $cardNumber The credit card number.
     * @param string $cvv The non-numeric CVV.
     */
    public function testInvalidCvvsNonNumeric(string $cardNumber, string $cvv): void
    {
        $card = new CreditCard($cardNumber, 'Test Holder', 12, 2030, $cvv);
        $this->assertFalse($this->validator->validate($card), "Passed for non-numeric CVV {$cvv} with card {$cardNumber}");
    }

    /**
     * @dataProvider unsupportedCardTypeProvider
     * @param string $cardNumber Card number of an unsupported type.
     * @param string $cvv CVV (could be valid for its actual type).
     */
    public function testCvvForUnsupportedCardType(string $cardNumber, string $cvv): void
    {
        $card = new CreditCard($cardNumber, 'Test Holder', 12, 2030, $cvv);
        $this->assertTrue($this->validator->validate($card), "Failed for CVV {$cvv} with unsupported card type {$cardNumber}. Expected to pass.");
    }
}
