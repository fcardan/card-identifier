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
     * @return array{valid: bool, message: string} An array with two keys:
     *         'valid' (bool): True if validation passes, false otherwise.
     *         'message' (string): An error message if validation fails, or an empty string if it passes.
     */
    public function validate(CreditCard $card): array;
}
