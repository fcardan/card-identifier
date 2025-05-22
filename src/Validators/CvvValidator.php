<?php

namespace App\Validators;

use App\CreditCard;

/**
 * Validates the CVV (Card Verification Value) of a credit card.
 */
class CvvValidator implements ValidatorInterface
{
    private const CVV_LENGTH_STANDARD = 3;
    // American Express uses 4 digits, but is not part of the requirements for now.
    // private const CVV_LENGTH_AMEX = 4;

    /**
     * Validates the credit card's CVV.
     *
     * The validation rules are:
     * 1. CVV must consist only of digits.
     * 2. For Visa cards (starting with '4'), the CVV must be 3 digits long.
     * 3. For Mastercard cards (starting with '51'-'55' or '2221'-'2720'), the CVV must be 3 digits long.
     * 4. For other card types, the CVV validation is currently lenient and returns true.
     *
     * @param CreditCard $card The credit card to validate.
     * @return bool True if the CVV is valid according to the rules, false otherwise.
     */
    public function validate(CreditCard $card): bool
    {
        $cvv = $card->getCvv();
        $cardNumber = $card->getCardNumber();

        // 1. Validate that CVV consists only of digits.
        if (!preg_match('/^[0-9]+$/', $cvv)) {
            return false;
        }

        $cardNumber = preg_replace('/[^\d]/', '', $cardNumber); // Clean card number

        // 2. Determine card type and expected CVV length.
        $expectedLength = null;

        // Visa: Starts with '4'
        if (strpos($cardNumber, '4') === 0) {
            $expectedLength = self::CVV_LENGTH_STANDARD;
        }
        // Mastercard: Starts with '51'-'55' or '2221'-'2720'
        elseif (preg_match('/^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)/', $cardNumber)) {
            $expectedLength = self::CVV_LENGTH_STANDARD;
        } else {
            // 4. For other card types, consider CVV valid by default for now.
            // This could be changed to `return false;` if strict validation for known types is required.
            return true;
        }

        // 3. Validate the length of the CVV.
        if (strlen($cvv) !== $expectedLength) {
            return false;
        }

        return true;
    }
}
