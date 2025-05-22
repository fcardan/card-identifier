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
     * 3. The expiration month/year is not in the past. The card is valid until the end of its expiration month.
     *
     * @param CreditCard $card The credit card to validate.
     * @return bool True if the expiration date is valid, false otherwise.
     */
    public function validate(CreditCard $card): bool
    {
        $expMonth = $card->getExpirationMonth();
        $expYear = $card->getExpirationYear();

        // 1. Validate month (1-12)
        if ($expMonth < 1 || $expMonth > 12) {
            return false;
        }

        // Get current year and month
        // Note: Using DateTime for robust date handling, especially for month/year comparisons.
        $currentDate = new DateTime();
        $currentYear = (int)$currentDate->format('Y');
        $currentMonth = (int)$currentDate->format('m');

        // 2. Validate year (not in the past)
        if ($expYear < $currentYear) {
            return false;
        }

        // 3. Validate month/year combination (not in the past)
        // Card is valid until the end of the expiration month.
        // If the expiration year is the current year, the expiration month must be greater than or equal to the current month.
        if ($expYear === $currentYear && $expMonth < $currentMonth) {
            return false;
        }

        // If all checks pass, the expiration date is valid.
        return true;
    }
}
