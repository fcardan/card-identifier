<?php

namespace App\Validators;

use App\CreditCard;

/**
 * Validates a credit card number using the Luhn algorithm.
 */
class LuhnValidator implements ValidatorInterface
{
    /**
     * Validates a credit card using the Luhn algorithm.
     *
     * @param CreditCard $card The credit card to validate.
     * @return bool True if the credit card number is valid according to the Luhn algorithm, false otherwise.
     */
    public function validate(CreditCard $card): bool
    {
        $cardNumber = $card->getCardNumber();
        $cardNumber = strrev(preg_replace('/[^\d]/', '', $cardNumber)); // 1. Reverse and remove non-digits

        if (empty($cardNumber)) {
            return false;
        }

        $sum = 0;
        for ($i = 0, $len = strlen($cardNumber); $i < $len; $i++) {
            $digit = (int)$cardNumber[$i];

            if ($i % 2 !== 0) { // 2. Double every second digit (0-indexed, so odd positions after reverse)
                $digit *= 2;
                if ($digit > 9) { // 3. If doubling results in two digits, subtract 9
                    $digit -= 9;
                }
            }
            $sum += $digit; // 4. Sum all digits
        }

        return ($sum % 10 === 0); // 5. If sum is a multiple of 10, it's valid
    }
}
