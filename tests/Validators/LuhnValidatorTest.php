<?php

namespace App\Tests\Validators;

use App\CreditCard;
use App\Validators\LuhnValidator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the LuhnValidator class.
 */
class LuhnValidatorTest extends TestCase
{
    private LuhnValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new LuhnValidator();
    }

    /**
     * Provides valid Luhn numbers.
     * @return array<string[]>
     */
    public function validLuhnNumbersProvider(): array
    {
        return [
            ['49927398716'], // Visa
            ['79927398713'], // Common example
            ['0000000000000000'], // All zeros (edge case, but mathematically valid by Luhn)
            ['1234567812345670'], // Another common example
        ];
    }

    /**
     * Provides invalid Luhn numbers.
     * @return array<string[]>
     */
    public function invalidLuhnNumbersProvider(): array
    {
        return [
            ['49927398717'], // Visa, invalid check digit
            ['79927398714'], // Common example, invalid check digit
            ['1234567812345678'], // Invalid
            ['0000000000000001'], // Invalid
        ];
    }

    /**
     * Provides Luhn numbers with non-numeric characters.
     * @return array<array{string, bool}>
     */
    public function luhnWithNonNumericCharsProvider(): array
    {
        return [
            ['4992-7398-716', true],   // Valid Visa with hyphens
            ['7992 7398 713', true],   // Valid common example with spaces
            ['4992-7398-717', false],  // Invalid Visa with hyphens
            ['1234567812345670abc', true], // Valid with trailing chars (should be stripped)
            ['ab1234567812345670', true], // Valid with leading chars (should be stripped)
            ['1234-abcd-5678', false], // Invalid, non-numeric in middle
            ['', false], // Empty string
        ];
    }

    /**
     * @dataProvider validLuhnNumbersProvider
     * @param string $validNumber A known valid Luhn number.
     */
    public function testValidLuhnNumbers(string $validNumber): void
    {
        $card = new CreditCard($validNumber, 'Test Holder', 12, 2030, '123');
        $this->assertTrue($this->validator->validate($card), "Luhn validation failed for valid number: {$validNumber}");
    }

    /**
     * @dataProvider invalidLuhnNumbersProvider
     * @param string $invalidNumber A known invalid Luhn number.
     */
    public function testInvalidLuhnNumbers(string $invalidNumber): void
    {
        $card = new CreditCard($invalidNumber, 'Test Holder', 12, 2030, '123');
        $this->assertFalse($this->validator->validate($card), "Luhn validation passed for invalid number: {$invalidNumber}");
    }

    /**
     * @dataProvider luhnWithNonNumericCharsProvider
     * @param string $numberWithChars A number string that may contain non-numeric characters.
     * @param bool $expectedValidity The expected validation result.
     */
    public function testLuhnWithNonNumericChars(string $numberWithChars, bool $expectedValidity): void
    {
        $card = new CreditCard($numberWithChars, 'Test Holder', 12, 2030, '123');
        $this->assertSame($expectedValidity, $this->validator->validate($card), "Luhn validation with non-numeric chars failed for: {$numberWithChars}");
    }

    /**
     * Test with an empty card number.
     */
    public function testEmptyCardNumber(): void
    {
        $card = new CreditCard('', 'Test Holder', 12, 2030, '123');
        $this->assertFalse($this->validator->validate($card), "Luhn validation should fail for an empty card number.");
    }
}
