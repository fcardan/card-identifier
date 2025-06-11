<?php

namespace App;

use App\Validators\CardTypeValidator;
use App\Validators\CvvValidator;
use App\Validators\ExpirationDateValidator;
use App\Validators\LuhnValidator;
use App\Validators\ValidatorInterface; // Added for type hinting the array

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
     * Validates the credit card and returns a detailed report.
     *
     * The report includes an overall validity status and individual results from each validator.
     *
     * @return array{
     *     overall_valid: bool,
     *     details: array{
     *         luhn: array{valid: bool, message: string},
     *         card_type: array{valid: bool, message: string},
     *         expiration_date: array{valid: bool, message: string},
     *         cvv: array{valid: bool, message: string}
     *     },
     *     messages: string[]
     * } The validation report.
     */
    public function getValidationReport(): array
    {
        $validators = [
            'luhn' => new LuhnValidator(),
            'card_type' => new CardTypeValidator(),
            'expiration_date' => new ExpirationDateValidator(),
            'cvv' => new CvvValidator(),
        ];

        $reportDetails = [];
        $errorMessages = [];
        $overallValid = true;

        /** @var ValidatorInterface $validator */
        foreach ($validators as $key => $validator) {
            $result = $validator->validate($this);
            $reportDetails[$key] = $result;
            if (!$result['valid']) {
                $overallValid = false;
                if (!empty($result['message'])) {
                    $errorMessages[] = $result['message'];
                }
            }
        }

        return [
            'overall_valid' => $overallValid,
            'details' => $reportDetails,
            'messages' => $errorMessages,
        ];
    }

    /**
     * Checks if the credit card is valid based on all configured validators.
     *
     * This method now utilizes getValidationReport() to determine overall validity.
     *
     * @return bool True if all validation checks pass, false otherwise.
     */
    public function isValid(): bool
    {
        $report = $this->getValidationReport();
        return $report['overall_valid'];
    }
}
