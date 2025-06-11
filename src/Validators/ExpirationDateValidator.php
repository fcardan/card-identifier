<?php

namespace App\Validators;

use App\CreditCard;
use DateTime;

/**
 * Validates the expiration date of a credit card.
 */
class ExpirationDateValidator implements ValidatorInterface
{
    /**
     * Validates the credit card's expiration date.
     *
     * The card is considered valid if:
     * 1. The expiration month is between 1 and 12.
     * 2. The expiration year is not in the past.
     * 3. The expiration month/year is not in the past (card is valid until the end of its expiration month).
     *
     * @param CreditCard $card The credit card to validate.
     * @return array{valid: bool, message: string} An array indicating validity and a message.
     *         'valid' is true if the expiration date is valid.
     *         'message' provides details on failure ('Invalid expiration month.', 'Card has expired.') or is empty on success.
     */
    public function validate(CreditCard $card): array
    {
        $expMonth = $card->getExpirationMonth();
        $expYear = $card->getExpirationYear();

        // 1. Validate month (1-12)
        if ($expMonth < 1 || $expMonth > 12) {
            return ['valid' => false, 'message' => 'Invalid expiration month.'];
        }

        // Get current year and month
        $currentDate = new DateTime();
        $currentYear = (int)$currentDate->format('Y');
        $currentMonth = (int)$currentDate->format('m');

        // 2. Validate year (not in the past)
        if ($expYear < $currentYear) {
            return ['valid' => false, 'message' => 'Card has expired. Expiration year is in the past.'];
        }

        // 3. Validate month/year combination (not in the past)
        // Card is valid until the end of the expiration month.
        // If the expiration year is the current year, the expiration month must be greater than or equal to the current month.
        if ($expYear === $currentYear && $expMonth < $currentMonth) {
            return ['valid' => false, 'message' => 'Card has expired.'];
        }

        // If all checks pass, the expiration date is valid.
        return ['valid' => true, 'message' => ''];
    }
}
