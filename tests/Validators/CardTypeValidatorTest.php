<?php

namespace App\Tests\Validators;

use App\CreditCard;
use App\Validators\CardTypeValidator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the CardTypeValidator class.
 */
class CardTypeValidatorTest extends TestCase
{
    private CardTypeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CardTypeValidator();
    }

    // --- Data Providers ---

    /**
     * Provides valid Visa card numbers.
     * @return array<string[]>
     */
    public function validVisaProvider(): array
    {
        return [
            ['4111111111111'],    // 13 digits
            ['4111111111111111'], // 16 digits
            ['4111111111111111111'],// 19 digits
            ['4999999999999999'], // 16 digits
            ['4000000000000'],    // 13 digits
        ];
    }

    /**
     * Provides invalid Visa card numbers (wrong prefix or length).
     * @return array<string[]>
     */
    public function invalidVisaProvider(): array
    {
        return [
            ['5111111111111'],    // Not a Visa prefix
            ['411111111111'],     // Too short (12 digits)
            ['41111111111111111111'],// Too long (20 digits)
            ['3111111111111111'], // Wrong prefix
        ];
    }

    /**
     * Provides valid Mastercard numbers.
     * @return array<string[]>
     */
    public function validMastercardProvider(): array
    {
        return [
            ['5100000000000000'], // Starts with 51, 16 digits
            ['5599999999999999'], // Starts with 55, 16 digits
            ['2221000000000000'], // Starts with 2221, 16 digits
            ['2720999999999999'], // Starts with 2720, 16 digits
            ['2345678901234567'], // Starts with 2345, 16 digits
        ];
    }

    /**
     * Provides invalid Mastercard numbers.
     * @return array<string[]>
     */
    public function invalidMastercardProvider(): array
    {
        return [
            ['5000000000000000'], // Invalid prefix (50)
            ['510000000000000'],  // Too short (15 digits)
            ['55999999999999999'],// Too long (17 digits)
            ['2220000000000000'], // Invalid prefix (2220)
            ['2721000000000000'], // Invalid prefix (2721)
            ['6100000000000000'], // Invalid prefix
        ];
    }

    /**
     * Provides other card numbers (not Visa or Mastercard).
     * @return array<string[]>
     */
    public function otherCardNumbersProvider(): array
    {
        return [
            ['340000000000000'],  // Amex (example, 15 digits)
            ['370000000000000'],  // Amex (example, 15 digits)
            ['6011000000000000'], // Discover (example, 16 digits)
            ['1234567890123'],    // Made up, 13 digits
            ['9999999999999999'], // Made up, 16 digits
        ];
    }

    /**
     * Provides card numbers with spaces/hyphens to test stripping.
     * @return array<array{string, bool}>
     */
    public function numbersWithFormattingProvider(): array
    {
        return [
            // Valid Visa
            ['4111-1111-1111-1111', true],
            ['4111 1111 1111 1111', true],
            // Valid Mastercard
            ['5100-0000-0000-0000', true],
            ['2221 0000 0000 0000', true],
            // Invalid (wrong prefix but formatted)
            ['6011-0000-0000-0000', false], // Discover pattern, should be false
            // Invalid (wrong length but formatted)
            ['4111-1111-1111-111', false],
        ];
    }

    // --- Test Methods ---

    /**
     * @dataProvider validVisaProvider
     * @param string $cardNumber A valid Visa card number.
     */
    public function testValidVisaCards(string $cardNumber): void
    {
        $card = new CreditCard($cardNumber, 'Test Holder', 12, 2030, '123');
        $this->assertTrue($this->validator->validate($card), "Failed for valid Visa: {$cardNumber}");
    }

    /**
     * @dataProvider invalidVisaProvider
     * @param string $cardNumber An invalid Visa card number.
     */
    public function testInvalidVisaCards(string $cardNumber): void
    {
        $card = new CreditCard($cardNumber, 'Test Holder', 12, 2030, '123');
        $this->assertFalse($this->validator->validate($card), "Passed for invalid Visa: {$cardNumber}");
    }

    /**
     * @dataProvider validMastercardProvider
     * @param string $cardNumber A valid Mastercard number.
     */
    public function testValidMastercardCards(string $cardNumber): void
    {
        $card = new CreditCard($cardNumber, 'Test Holder', 12, 2030, '123');
        $this->assertTrue($this->validator->validate($card), "Failed for valid Mastercard: {$cardNumber}");
    }

    /**
     * @dataProvider invalidMastercardProvider
     * @param string $cardNumber An invalid Mastercard number.
     */
    public function testInvalidMastercardCards(string $cardNumber): void
    {
        $card = new CreditCard($cardNumber, 'Test Holder', 12, 2030, '123');
        $this->assertFalse($this->validator->validate($card), "Passed for invalid Mastercard: {$cardNumber}");
    }

    /**
     * @dataProvider otherCardNumbersProvider
     * @param string $cardNumber A card number that is not Visa or Mastercard.
     */
    public function testOtherCardNumbers(string $cardNumber): void
    {
        $card = new CreditCard($cardNumber, 'Test Holder', 12, 2030, '123');
        $this->assertFalse($this->validator->validate($card), "Passed for non-Visa/Mastercard: {$cardNumber}");
    }

    /**
     * @dataProvider numbersWithFormattingProvider
     * @param string $cardNumber Card number with spaces or hyphens.
     * @param bool $expectedValidity Expected validation result.
     */
    public function testCardNumbersWithSpacesAndHyphens(string $cardNumber, bool $expectedValidity): void
    {
        $card = new CreditCard($cardNumber, 'Test Holder', 12, 2030, '123');
        $this->assertSame($expectedValidity, $this->validator->validate($card), "Validation with formatting failed for: {$cardNumber}");
    }
}
