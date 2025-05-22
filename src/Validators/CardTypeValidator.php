<?php

namespace App\Validators;

use App\CreditCard;

/**
 * Validates the card type based on its number (Visa, Mastercard).
 */
class CardTypeValidator implements ValidatorInterface
{
    // Card type constants (optional, but good practice if these were used elsewhere)
    // const TYPE_VISA = 'Visa';
    // const TYPE_MASTERCARD = 'Mastercard';
    // const TYPE_UNKNOWN = 'Unknown';

    /**
     * Validates if the credit card number matches Visa or Mastercard patterns.
     *
     * @param CreditCard $card The credit card to validate.
     * @return bool True if the card number matches Visa or Mastercard patterns, false otherwise.
     */
    public function validate(CreditCard $card): bool
    {
        $cardNumber = $card->getCardNumber();
        $cardNumber = preg_replace('/[^\d]/', '', $cardNumber); // Remove non-digits

        // Visa: Starts with '4', length 13, 16, or 19 digits.
        if (preg_match('/^4[0-9]{12}(?:[0-9]{3}){0,2}$/', $cardNumber)) {
            // Optionally, you could set a card type property on the $card object here
            // $card->setCardType(self::TYPE_VISA);
            return true;
        }

        // Mastercard:
        // - Starts with '51' through '55', length 16 digits.
        // - Or starts with '2221' through '2720', length 16 digits.
        if (preg_match('/^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$/', $cardNumber)) {
            // Optionally, you could set a card type property on the $card object here
            // $card->setCardType(self::TYPE_MASTERCARD);
            return true;
        }

        // $card->setCardType(self::TYPE_UNKNOWN);
        return false;
    }
}
