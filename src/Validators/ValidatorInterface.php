<?php

namespace App\Validators;

use App\CreditCard;

/**
 * Interface for credit card validators.
 *
 * This interface defines the contract for different credit card validation strategies.
 */
interface ValidatorInterface
{
    /**
     * Validates a credit card.
     *
     * @param CreditCard $card The credit card to validate.
     * @return bool True if the credit card is valid, false otherwise.
     */
    public function validate(CreditCard $card): bool;
}
