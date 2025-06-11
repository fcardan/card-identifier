<?php

namespace App\Tests;

use App\CreditCard;
use PHPUnit\Framework\TestCase;
use DateTime;

/**
 * Tests for the CreditCard class, focusing on getValidationReport() and isValid().
 */
class CreditCardTest extends TestCase
{
    private array $validatorKeys = ['luhn', 'card_type', 'expiration_date', 'cvv'];

    // --- Helper method to get future expiration date ---
    private function getFutureExpirationDate(): array
    {
        $date = new DateTime();
        $date->modify('+2 years'); // Ensure it's well in the future
        return [(int)$date->format('m'), (int)$date->format('Y')];
    }

    // --- Helper for report structure assertions ---
    private function assertReportStructure(array $report): void
    {
        $this->assertArrayHasKey('overall_valid', $report);
        $this->assertIsBool($report['overall_valid']);
        $this->assertArrayHasKey('details', $report);
        $this->assertIsArray($report['details']);
        $this->assertArrayHasKey('messages', $report);
        $this->assertIsArray($report['messages']);

        foreach ($this->validatorKeys as $key) {
            $this->assertArrayHasKey($key, $report['details'], "Report details missing key: {$key}");
            $this->assertIsArray($report['details'][$key], "Report details for {$key} is not an array");
            $this->assertArrayHasKey('valid', $report['details'][$key], "Report details for {$key} missing 'valid' key");
            $this->assertIsBool($report['details'][$key]['valid'], "Report details for {$key} 'valid' is not a bool");
            $this->assertArrayHasKey('message', $report['details'][$key], "Report details for {$key} missing 'message' key");
            $this->assertIsString($report['details'][$key]['message'], "Report details for {$key} 'message' is not a string");
        }
    }

    /**
     * Tests getValidationReport() and isValid() with known valid Visa and Mastercard details.
     * @covers \App\CreditCard::getValidationReport
     * @covers \App\CreditCard::isValid
     */
    public function testReportValidCreditCard(): void
    {
        [$expMonth, $expYear] = $this->getFutureExpirationDate();

        $cards = [
            'Visa' => new CreditCard('49927398716', 'Test Holder Visa', $expMonth, $expYear, '123'),
            'Mastercard' => new CreditCard('5186190000000000', 'Test Holder Mastercard', $expMonth, $expYear, '321')
        ];

        foreach ($cards as $type => $card) {
            $report = $card->getValidationReport();
            $this->assertReportStructure($report);

            $this->assertTrue($report['overall_valid'], "{$type} card should be overall valid.");
            $this->assertTrue($card->isValid(), "{$type} card isValid() should return true.");
            $this->assertSame($report['overall_valid'], $card->isValid());

            foreach ($this->validatorKeys as $key) {
                $this->assertTrue($report['details'][$key]['valid'], "{$type} card, validator '{$key}' should be valid.");
                $this->assertEmpty($report['details'][$key]['message'], "{$type} card, validator '{$key}' message should be empty.");
            }
            $this->assertEmpty($report['messages'], "{$type} card should have no overall error messages.");
        }
    }

    /**
     * Tests a card that is invalid only due to a failed Luhn check.
     * @covers \App\CreditCard::getValidationReport
     * @covers \App\CreditCard::isValid
     */
    public function testReportCardInvalidDueToLuhn(): void
    {
        [$expMonth, $expYear] = $this->getFutureExpirationDate();
        $card = new CreditCard('49927398717', 'Test Holder', $expMonth, $expYear, '123'); // Invalid Luhn

        $report = $card->getValidationReport();
        $this->assertReportStructure($report);

        $this->assertFalse($report['overall_valid']);
        $this->assertFalse($card->isValid());
        $this->assertSame($report['overall_valid'], $card->isValid());

        $this->assertFalse($report['details']['luhn']['valid']);
        $this->assertEquals('Invalid Luhn checksum.', $report['details']['luhn']['message']);
        $this->assertContains('Invalid Luhn checksum.', $report['messages']);
        $this->assertCount(1, $report['messages']);

        // Check other validators are fine
        $this->assertTrue($report['details']['card_type']['valid']);
        $this->assertTrue($report['details']['expiration_date']['valid']);
        $this->assertTrue($report['details']['cvv']['valid']);
    }

    /**
     * Tests a card that is invalid only due to an unsupported card type.
     * @covers \App\CreditCard::getValidationReport
     * @covers \App\CreditCard::isValid
     */
    public function testReportCardInvalidDueToType(): void
    {
        [$expMonth, $expYear] = $this->getFutureExpirationDate();
        // 6011000000000000 is a Discover card number that passes Luhn.
        $card = new CreditCard('6011000000000000', 'Test Holder', $expMonth, $expYear, '123');

        $report = $card->getValidationReport();
        $this->assertReportStructure($report);

        $this->assertFalse($report['overall_valid']);
        $this->assertFalse($card->isValid());

        $expectedMessage = 'Invalid or unsupported card type. Only Visa and Mastercard are currently supported.';
        $this->assertFalse($report['details']['card_type']['valid']);
        $this->assertEquals($expectedMessage, $report['details']['card_type']['message']);
        $this->assertContains($expectedMessage, $report['messages']);

        // Luhn should pass
        $this->assertTrue($report['details']['luhn']['valid']);
        // Expiration date should pass
        $this->assertTrue($report['details']['expiration_date']['valid']);
        // CVV for unknown type is 'skipped' which is 'valid' => true
        $this->assertTrue($report['details']['cvv']['valid']);
        $this->assertEquals('CVV validation skipped for unknown card type.', $report['details']['cvv']['message']);

        // Even though CVV is 'skipped' (valid=true), only card_type message should be in the main messages array
        $this->assertCount(1, $report['messages']);
    }

    /**
     * Tests a card that is invalid only due to an expired date.
     * @covers \App\CreditCard::getValidationReport
     * @covers \App\CreditCard::isValid
     */
    public function testReportCardInvalidDueToExpiration(): void
    {
        $cardNumber = '49927398716'; // Valid Visa Luhn
        $pastMonth = 1;
        $pastYear = (int)(new DateTime())->format('Y') - 2; // Two years ago

        $card = new CreditCard($cardNumber, 'Test Holder', $pastMonth, $pastYear, '123');
        $report = $card->getValidationReport();
        $this->assertReportStructure($report);

        $this->assertFalse($report['overall_valid']);
        $this->assertFalse($card->isValid());

        $expectedMessage = 'Card has expired. Expiration year is in the past.';
        $this->assertFalse($report['details']['expiration_date']['valid']);
        $this->assertEquals($expectedMessage, $report['details']['expiration_date']['message']);
        $this->assertContains($expectedMessage, $report['messages']);
        $this->assertCount(1, $report['messages']);

        $this->assertTrue($report['details']['luhn']['valid']);
        $this->assertTrue($report['details']['card_type']['valid']);
        $this->assertTrue($report['details']['cvv']['valid']);
    }

    /**
     * Tests a card that is invalid only due to an incorrect CVV.
     * @covers \App\CreditCard::getValidationReport
     * @covers \App\CreditCard::isValid
     */
    public function testReportCardInvalidDueToCvv(): void
    {
        [$expMonth, $expYear] = $this->getFutureExpirationDate();
        $cardNumber = '49927398716'; // Valid Visa Luhn

        $card = new CreditCard($cardNumber, 'Test Holder', $expMonth, $expYear, '1234'); // Invalid CVV length
        $report = $card->getValidationReport();
        $this->assertReportStructure($report);

        $this->assertFalse($report['overall_valid']);
        $this->assertFalse($card->isValid());

        $expectedMessage = 'Invalid CVV length for Visa/Mastercard. Must be 3 digits.';
        $this->assertFalse($report['details']['cvv']['valid']);
        $this->assertEquals($expectedMessage, $report['details']['cvv']['message']);
        $this->assertContains($expectedMessage, $report['messages']);
        $this->assertCount(1, $report['messages']);

        $this->assertTrue($report['details']['luhn']['valid']);
        $this->assertTrue($report['details']['card_type']['valid']);
        $this->assertTrue($report['details']['expiration_date']['valid']);
    }

    /**
     * Tests a card that is invalid due to multiple reasons.
     * @covers \App\CreditCard::getValidationReport
     * @covers \App\CreditCard::isValid
     */
    public function testReportCardInvalidDueToMultipleReasons(): void
    {
        $cardNumber = '49927398717'; // Invalid Luhn
        $pastMonth = 1;
        $pastYear = (int)(new DateTime())->format('Y') - 2; // Expired
        $cvv = '12'; // Invalid CVV length for Visa

        $card = new CreditCard($cardNumber, 'Test Holder', $pastMonth, $pastYear, $cvv);
        $report = $card->getValidationReport();
        $this->assertReportStructure($report);

        $this->assertFalse($report['overall_valid']);
        $this->assertFalse($card->isValid());

        // Luhn
        $luhnMessage = 'Invalid Luhn checksum.';
        $this->assertFalse($report['details']['luhn']['valid']);
        $this->assertEquals($luhnMessage, $report['details']['luhn']['message']);
        $this->assertContains($luhnMessage, $report['messages']);

        // Card Type (should be fine if number starts with 4)
        $this->assertTrue($report['details']['card_type']['valid']);

        // Expiration
        $expMessage = 'Card has expired. Expiration year is in the past.';
        $this->assertFalse($report['details']['expiration_date']['valid']);
        $this->assertEquals($expMessage, $report['details']['expiration_date']['message']);
        $this->assertContains($expMessage, $report['messages']);

        // CVV
        $cvvMessage = 'Invalid CVV length for Visa/Mastercard. Must be 3 digits.';
        $this->assertFalse($report['details']['cvv']['valid']);
        $this->assertEquals($cvvMessage, $report['details']['cvv']['message']);
        $this->assertContains($cvvMessage, $report['messages']);

        $this->assertCount(3, $report['messages']);
    }
}
