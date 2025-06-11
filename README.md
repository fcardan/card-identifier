# PHP Credit Card Validation Library

## Overview

This project provides a PHP library for validating credit card details. It focuses on several key aspects of validation: the Luhn algorithm for card number integrity, identification of card type (Visa and Mastercard), expiration date checking, and CVV verification. The library is designed with a modular approach, utilizing the Strategy pattern for its validators, making it extensible. It provides detailed reporting on validation results.

Currently supported card types:
*   Visa
*   Mastercard

## Features

*   **Luhn Algorithm Check**: Validates card numbers against the Luhn algorithm (Mod 10).
*   **Card Type Identification**: Identifies and validates card numbers belonging to Visa and Mastercard.
*   **Expiration Date Validation**: Ensures the card's expiration date has not passed and that the month/year are valid. Cards are considered valid through the entire expiration month.
*   **CVV Validation**: Validates the CVV (Card Verification Value). For Visa and Mastercard, a 3-digit CVV is expected. For unknown card types, CVV validation is currently skipped.
*   **Detailed Validation Report**: The `getValidationReport()` method returns a comprehensive report including an overall validity status, results from each individual validator, and a collection of error messages.
*   **Extensible Design**: Uses a Strategy pattern (`ValidatorInterface`) allowing for new validation rules (e.g., for other card types or custom checks) to be added easily. Each validator returns a detailed result array.

## Requirements

*   PHP 8.3 or higher
*   Composer

## Installation

1.  **Clone the repository (if applicable):**
    ```bash
    git clone <repository-url>
    cd <project-directory>
    ```

2.  **Install dependencies using Composer:**
    This will install PHPUnit, which is used for running the tests.
    ```bash
    composer install
    ```

    If you were to publish this as a library to Packagist, you would typically require it in your project via:
    ```bash
    composer require app/project
    ```
    (Assuming `app/project` is the name defined in `composer.json`. For a real library, this would be your vendor/package name).

## Usage

To validate a credit card, create a `CreditCard` instance and then call the `getValidationReport()` method for detailed results.

```php
<?php

require 'vendor/autoload.php'; // If installed via Composer

use App\CreditCard;

// Example details (replace with actual card data for real tests)
$cardNumber = '49927398717'; // Invalid Luhn
$cardHolderName = 'Jane Doe';
$expirationMonth = 1; // January
$expirationYear = (int)date('Y') - 1; // Expired last year
$cvv = '12'; // Invalid CVV for Visa (if it were a Visa)

$card = new CreditCard(
    $cardNumber,
    $cardHolderName,
    $expirationMonth,
    $expirationYear,
    $cvv
);

$report = $card->getValidationReport();

echo "Validation Report:\n";
echo "Overall Valid: " . ($report['overall_valid'] ? 'Yes' : 'No') . "\n\n";

echo "Details:\n";
foreach ($report['details'] as $validatorName => $result) {
    echo "- " . ucfirst(str_replace('_', ' ', $validatorName)) . ": " . ($result['valid'] ? 'Valid' : 'Invalid');
    if (!empty($result['message'])) {
        echo " (Message: " . htmlspecialchars($result['message']) . ")";
    }
    echo "\n";
}
echo "\n";

if (!$report['overall_valid']) {
    echo "Error Messages:\n";
    if (empty($report['messages'])) {
        echo "- No specific error messages (check details for individual validator results).\n";
    } else {
        foreach ($report['messages'] as $message) {
            echo "- " . htmlspecialchars($message) . "\n";
        }
    }
}

/*
Example of the $report structure:

[
    'overall_valid' => false,
    'details' => [
        'luhn' => ['valid' => false, 'message' => 'Invalid Luhn checksum.'],
        'card_type' => ['valid' => true, 'message' => ''], // Assuming card number starts with a valid prefix like '4'
        'expiration_date' => ['valid' => false, 'message' => 'Card has expired. Expiration year is in the past.'],
        'cvv' => ['valid' => false, 'message' => 'Invalid CVV length for Visa/Mastercard. Must be 3 digits.']
    ],
    'messages' => [
        'Invalid Luhn checksum.',
        'Card has expired. Expiration year is in the past.',
        'Invalid CVV length for Visa/Mastercard. Must be 3 digits.'
    ]
]
*/

// For a simple boolean check of overall validity, you can use isValid():
$isValidSimple = $card->isValid(); // This will be false for the example card
echo "\nSimple isValid() check: " . ($isValidSimple ? 'Valid' : 'Invalid') . "\n";

// Example with a valid card
$validCardNumber = '49927398716'; // Valid Visa Luhn
$validCardHolderName = 'John Doe';
$validExpirationMonth = 12; // December
$validExpirationYear = (int)date('Y') + 2; // Current year + 2
$validCvv = '123';

$validCard = new CreditCard(
    $validCardNumber,
    $validCardHolderName,
    $validExpirationMonth,
    $validExpirationYear,
    $validCvv
);

$validReport = $validCard->getValidationReport();
echo "\n--- Valid Card Example ---\n";
echo "Overall Valid: " . ($validReport['overall_valid'] ? 'Yes' : 'No') . "\n";
if (empty($validReport['messages'])) {
    echo "Messages: None\n";
}
// isValid() for the valid card
echo "Simple isValid() check for valid card: " . ($validCard->isValid() ? 'Valid' : 'Invalid') . "\n";

```

## Running Tests

To run the suite of PHPUnit tests included with the library:

```bash
./vendor/bin/phpunit
```

Alternatively, you can add a script to your `composer.json`:
```json
"scripts": {
    "test": "phpunit"
}
```
And then run:
```bash
composer test
```

## Extending the Library

The library is designed to be extensible through the `App\Validators\ValidatorInterface`.

1.  **Create a new Validator**:
    Implement the `App\Validators\ValidatorInterface` and its `validate(CreditCard $card): array` method. The method must return an array with two keys: `'valid'` (boolean) and `'message'` (string). For example, to add support for American Express:
    ```php
    // src/Validators/AmexCardTypeValidator.php
    namespace App\Validators;
    use App\CreditCard;
    class AmexCardTypeValidator implements ValidatorInterface {
        public function validate(CreditCard $card): array {
            $cardNumber = preg_replace('/[^\d]/', '', $card->getCardNumber());
            // AMEX specific prefix and length checks
            if (preg_match('/^3[47][0-9]{13}$/', $cardNumber)) {
                return ['valid' => true, 'message' => ''];
            }
            return ['valid' => false, 'message' => 'Invalid American Express card number.'];
        }
    }
    ```

2.  **Integrate the new Validator**:
    Validators are instantiated within the `CreditCard::getValidationReport()` method. To add your new validator, you would modify this method:
    ```php
    // In App\CreditCard::getValidationReport()
    // ...
    $validators = [
        'luhn' => new LuhnValidator(),
        'card_type' => new CardTypeValidator(), // This currently only knows Visa/MC
        // 'amex_card_type' => new AmexCardTypeValidator(), // Add your new validator
        'expiration_date' => new ExpirationDateValidator(),
        'cvv' => new CvvValidator(),
        // Note: You might need to adjust how CardTypeValidator and your new AmexCardTypeValidator interact,
        // or replace CardTypeValidator with a more sophisticated type dispatcher if multiple card types are checked.
    ];
    // ...
    ```
    For a more flexible approach, the `CreditCard` class could be refactored to accept a list of validators in its constructor or via a setter method. This would allow users of the library to customize the validation chain without modifying the library's core code.

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License

This project is licensed under the MIT License - see the `composer.json` file for details (implicitly, as no separate LICENSE file was generated, but MIT was specified during `composer init`).
```
