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
     * Provides Luhn numbers with non-numeric characters and their expected validity.
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
            ['1234-abcd-5678', false], // Invalid, non-numeric in middle (results in '12345678' -> invalid)
            ['', false], // Empty string
            ['abc', false], // Only non-numeric chars, becomes empty
        ];
    }

    /**
     * @dataProvider validLuhnNumbersProvider
     * @param string $validNumber A known valid Luhn number.
     * @covers \App\Validators\LuhnValidator::validate
     */
    public function testValidLuhnNumbers(string $validNumber): void
    {
        $card = new CreditCard($validNumber, 'Test Holder', 12, 2030, '123');
        $expected = ['valid' => true, 'message' => ''];
        $this->assertSame($expected, $this->validator->validate($card), "Luhn validation failed for valid number: {$validNumber}");
    }

    /**
     * @dataProvider invalidLuhnNumbersProvider
     * @param string $invalidNumber A known invalid Luhn number.
     * @covers \App\Validators\LuhnValidator::validate
     */
    public function testInvalidLuhnNumbers(string $invalidNumber): void
    {
        $card = new CreditCard($invalidNumber, 'Test Holder', 12, 2030, '123');
        $expected = ['valid' => false, 'message' => 'Invalid Luhn checksum.'];
        $this->assertSame($expected, $this->validator->validate($card), "Luhn validation passed for invalid number: {$invalidNumber}");
    }

    /**
     * @dataProvider luhnWithNonNumericCharsProvider
     * @param string $numberWithChars A number string that may contain non-numeric characters.
     * @param bool $expectedValidity The expected boolean part of the validation result.
     * @covers \App\Validators\LuhnValidator::validate
     */
    public function testLuhnWithNonNumericChars(string $numberWithChars, bool $expectedValidity): void
    {
        $card = new CreditCard($numberWithChars, 'Test Holder', 12, 2030, '123');
        $actualResult = $this->validator->validate($card);

        $this->assertSame($expectedValidity, $actualResult['valid'], "Luhn validation validity mismatch for: {$numberWithChars}");

        if ($expectedValidity) {
            $this->assertEmpty($actualResult['message'], "Message should be empty for valid number: {$numberWithChars}");
        } else {
            // If the original string was empty OR becomes empty after stripping non-digits
            if (empty(preg_replace('/[^\d]/', '', $numberWithChars))) {
                $this->assertEquals('Invalid Luhn checksum. Card number is empty.', $actualResult['message'], "Incorrect message for empty/stripped-to-empty number: {$numberWithChars}");
            } else {
                $this->assertEquals('Invalid Luhn checksum.', $actualResult['message'], "Incorrect message for invalid number with chars: {$numberWithChars}");
            }
        }
    }

    /**
     * Test with an explicitly empty card number.
     * @covers \App\Validators\LuhnValidator::validate
     */
    public function testEmptyCardNumber(): void
    {
        $card = new CreditCard('', 'Test Holder', 12, 2030, '123');
        $expected = ['valid' => false, 'message' => 'Invalid Luhn checksum. Card number is empty.'];
        $this->assertSame($expected, $this->validator->validate($card), "Luhn validation should fail correctly for an empty card number.");
    }
}
