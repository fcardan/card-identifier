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
     * 4. For other card types, CVV validation is currently skipped (considered valid).
     *
     * @param CreditCard $card The credit card to validate.
     * @return array{valid: bool, message: string} An array indicating validity and a message.
     *         'valid' is true if the CVV is valid or skipped, false otherwise.
     *         'message' provides details on failure or if skipped.
     */
    public function validate(CreditCard $card): array
    {
        $cvv = $card->getCvv();
        $cardNumber = $card->getCardNumber();

        // 1. Validate that CVV consists only of digits.
        if (!preg_match('/^[0-9]+$/', $cvv)) {
            // Check if CVV is empty, which can also be caught by this regex.
            // An empty CVV is also non-numeric in this context.
            if (empty($cvv)) {
                 return ['valid' => false, 'message' => 'CVV is required and must be numeric.'];
            }
            return ['valid' => false, 'message' => 'CVV must be numeric.'];
        }

        $cardNumber = preg_replace('/[^\d]/', '', $cardNumber); // Clean card number

        // 2. Determine card type and expected CVV length.
        $expectedLength = null;
        $isKnownType = false;
        $mastercardPattern = '/^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)/';

        // Visa: Starts with '4'
        if (strpos($cardNumber, '4') === 0) {
            $expectedLength = self::CVV_LENGTH_STANDARD;
            $isKnownType = true;
        } elseif (preg_match($mastercardPattern, $cardNumber)) {
            $expectedLength = self::CVV_LENGTH_STANDARD;
            $isKnownType = true;
        }

        if (!$isKnownType) {
            // 4. For other card types, CVV validation is skipped.
            return ['valid' => true, 'message' => 'CVV validation skipped for unknown card type.'];
        }

        // 3. Validate the length of the CVV for known types.
        if (strlen($cvv) !== $expectedLength) {
            return ['valid' => false, 'message' => 'Invalid CVV length for Visa/Mastercard. Must be 3 digits.'];
        }

        return ['valid' => true, 'message' => ''];
    }
}
