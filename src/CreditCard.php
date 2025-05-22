<?php

namespace App;

use App\Validators\CardTypeValidator;
use App\Validators\CvvValidator;
use App\Validators\ExpirationDateValidator;
use App\Validators\LuhnValidator;

/**
 * Represents a credit card.
 */
class CreditCard
{
    /**
     * @var string The credit card number.
     */
    private string $cardNumber;

    /**
     * @var string The name of the cardholder.
     */
    private string $holderName;

    /**
     * @var int The expiration month of the credit card.
     */
    private int $expirationMonth;

    /**
     * @var int The expiration year of the credit card.
     */
    private int $expirationYear;

    /**
     * @var string The CVV code of the credit card.
     */
    private string $cvv;

    /**
     * CreditCard constructor.
     *
     * @param string $cardNumber The credit card number.
     * @param string $holderName The name of the cardholder.
     * @param int    $expirationMonth The expiration month of the credit card.
     * @param int    $expirationYear The expiration year of the credit card.
     * @param string $cvv The CVV code of the credit card.
     */
    public function __construct(
        string $cardNumber,
        string $holderName,
        int $expirationMonth,
        int $expirationYear,
        string $cvv
    ) {
        $this->cardNumber = $cardNumber;
        $this->holderName = $holderName;
        $this->expirationMonth = $expirationMonth;
        $this->expirationYear = $expirationYear;
        $this->cvv = $cvv;
    }

    /**
     * Gets the credit card number.
     *
     * @return string The credit card number.
     */
    public function getCardNumber(): string
    {
        return $this->cardNumber;
    }

    /**
     * Gets the expiration month of the credit card.
     *
     * @return int The expiration month.
     */
    public function getExpirationMonth(): int
    {
        return $this->expirationMonth;
    }

    /**
     * Gets the expiration year of the credit card.
     *
     * @return int The expiration year.
     */
    public function getExpirationYear(): int
    {
        return $this->expirationYear;
    }

    /**
     * Gets the CVV code of the credit card.
     *
     * @return string The CVV code.
     */
    public function getCvv(): string
    {
        return $this->cvv;
    }

    /**
     * Validates the credit card against a series of validators.
     *
     * This method checks the card's validity using Luhn algorithm,
     * card type, expiration date, and CVV.
     *
     * @return bool True if all validation checks pass, false otherwise.
     */
    public function isValid(): bool
    {
        $validators = [
            new LuhnValidator(),
            new CardTypeValidator(),
            new ExpirationDateValidator(),
            new CvvValidator(),
        ];

        foreach ($validators as $validator) {
            if (!$validator->validate($this)) {
                return false;
            }
        }

        return true;
    }
}
