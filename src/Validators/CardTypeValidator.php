<?php

namespace App\Validators;

use App\CreditCard;

/**
 * Validates the card type based on its number (Visa, Mastercard).
 */
class CardTypeValidator implements ValidatorInterface
{
    /**
     * Validates if the credit card number matches Visa or Mastercard patterns.
     *
     * @param CreditCard $card The credit card to validate.
     * @return array{valid: bool, message: string} An array indicating validity and a message.
     *         'valid' is true if the card type is Visa or Mastercard and matches known patterns.
     *         'message' is 'Invalid or unsupported card type. Only Visa and Mastercard are currently supported.' if invalid, or empty if valid.
     */
    public function validate(CreditCard $card): array
    {
        $cardNumber = $card->getCardNumber();
        $cardNumber = preg_replace('/[^\d]/', '', $cardNumber); // Remove non-digits

        // Visa: Starts with '4', length 13, 16, or 19 digits.
        if (preg_match('/^4[0-9]{12}(?:[0-9]{3}){0,2}$/', $cardNumber)) {
            return ['valid' => true, 'message' => ''];
        }

        // Mastercard:
        // - Starts with '51' through '55', length 16 digits.
        // - Or starts with '2221' through '2720', length 16 digits.
        if (preg_match('/^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$/', $cardNumber)) {
            return ['valid' => true, 'message' => ''];
        }

        return ['valid' => false, 'message' => 'Invalid or unsupported card type. Only Visa and Mastercard are currently supported.'];
    }
}
