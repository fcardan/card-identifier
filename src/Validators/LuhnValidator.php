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
     * @return array{valid: bool, message: string} An array indicating validity and a message.
     *         'valid' is true if the Luhn checksum is correct, false otherwise.
     *         'message' is 'Invalid Luhn checksum.' if invalid, or empty if valid.
     */
    public function validate(CreditCard $card): array
    {
        $cardNumber = $card->getCardNumber();
        $cardNumber = strrev(preg_replace('/[^\d]/', '', $cardNumber)); // 1. Reverse and remove non-digits

        if (empty($cardNumber)) {
            return ['valid' => false, 'message' => 'Invalid Luhn checksum. Card number is empty.'];
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

        if ($sum % 10 === 0) { // 5. If sum is a multiple of 10, it's valid
            return ['valid' => true, 'message' => ''];
        } else {
            return ['valid' => false, 'message' => 'Invalid Luhn checksum.'];
        }
    }
}
