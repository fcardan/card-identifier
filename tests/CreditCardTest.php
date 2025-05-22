<?php

namespace App\Tests;

use App\CreditCard;
use PHPUnit\Framework\TestCase;
use DateTime;

/**
 * Tests for the CreditCard class, focusing on the isValid() method.
 */
class CreditCardTest extends TestCase
{
    // --- Helper method to get future expiration date ---
    private function getFutureExpirationDate(): array
    {
        $date = new DateTime();
        $date->modify('+2 years'); // Ensure it's well in the future
        return [(int)$date->format('m'), (int)$date->format('Y')];
    }

    // --- Test Methods ---

    /**
     * Tests the isValid() method with known valid Visa and Mastercard details.
     */
    public function testValidCreditCard(): void
    {
        [$expMonth, $expYear] = $this->getFutureExpirationDate();

        // Valid Visa (passes Luhn, type, expiry, cvv)
        $validVisa = new CreditCard(
            '49927398716', // Valid Luhn
            'Test Holder Visa',
            $expMonth,
            $expYear,
            '123' // Valid CVV for Visa
        );
        $this->assertTrue($validVisa->isValid(), 'Valid Visa card failed validation.');

        // Valid Mastercard (passes Luhn, type, expiry, cvv)
        // Number: 5186190000000000 (Example valid Mastercard Luhn)
        $validMastercard = new CreditCard(
            '5186190000000000',
            'Test Holder Mastercard',
            $expMonth,
            $expYear,
            '321' // Valid CVV for Mastercard
        );
        $this->assertTrue($validMastercard->isValid(), 'Valid Mastercard failed validation.');
    }

    /**
     * Tests a card that is invalid only due to a failed Luhn check.
     */
    public function testCardInvalidDueToLuhn(): void
    {
        [$expMonth, $expYear] = $this->getFutureExpirationDate();
        $card = new CreditCard(
            '49927398717', // Invalid Luhn (one digit off from a valid one)
            'Test Holder',
            $expMonth,
            $expYear,
            '123' // CVV is fine for Visa type
        );
        $this->assertFalse($card->isValid(), 'Card with invalid Luhn should fail validation.');
    }

    /**
     * Tests a card that is invalid only due to an unsupported card type.
     */
    public function testCardInvalidDueToType(): void
    {
        [$expMonth, $expYear] = $this->getFutureExpirationDate();
        // This number passes Luhn: 3000000000000 (Diners Club, 14 digits, passes Luhn if last digit is 8)
        // Let's use a number that passes Luhn but isn't Visa/MC and is 16 digits
        // Example: 6011000000000000 (Discover, passes Luhn)
        $card = new CreditCard(
            '6011000000000000', // Discover prefix, passes Luhn
            'Test Holder',
            $expMonth,
            $expYear,
            '123' // CVV is fine (lenient for unknown types by CvvValidator, but CardTypeValidator fails)
        );
        $this->assertFalse($card->isValid(), 'Card with unsupported type should fail validation.');
    }

    /**
     * Tests a card that is invalid only due to an expired date.
     */
    public function testCardInvalidDueToExpiration(): void
    {
        // Valid Visa Luhn number
        $cardNumber = '49927398716';
        $pastMonth = 1;
        $pastYear = (int)(new DateTime())->format('Y') - 2; // Two years ago

        $card = new CreditCard(
            $cardNumber,
            'Test Holder',
            $pastMonth,
            $pastYear,
            '123' // CVV is fine
        );
        $this->assertFalse($card->isValid(), 'Card with expired date should fail validation.');
    }

    /**
     * Tests a card that is invalid only due to an incorrect CVV.
     */
    public function testCardInvalidDueToCvv(): void
    {
        [$expMonth, $expYear] = $this->getFutureExpirationDate();
        // Valid Visa Luhn number
        $cardNumber = '49927398716';

        $card = new CreditCard(
            $cardNumber,
            'Test Holder',
            $expMonth,
            $expYear,
            '1234' // Invalid CVV length for Visa (needs 3 digits)
        );
        $this->assertFalse($card->isValid(), 'Card with invalid CVV should fail validation.');
    }

    /**
     * Tests a card that is invalid due to multiple reasons (e.g., bad Luhn and expired).
     */
    public function testCardInvalidDueToMultipleReasons(): void
    {
        $cardNumber = '49927398717'; // Invalid Luhn
        $pastMonth = 1;
        $pastYear = (int)(new DateTime())->format('Y') - 2; // Expired

        $card = new CreditCard(
            $cardNumber,
            'Test Holder',
            $pastMonth,
            $pastYear,
            '12345' // Invalid CVV length
        );
        $this->assertFalse($card->isValid(), 'Card with multiple validation failures should fail.');
    }
}
