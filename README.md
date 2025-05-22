# PHP Credit Card Validation Library

## Overview

This project provides a PHP library for validating credit card details. It focuses on several key aspects of validation: the Luhn algorithm for card number integrity, identification of card type (Visa and Mastercard), expiration date checking, and CVV verification. The library is designed with a modular approach, utilizing the Strategy pattern for its validators, making it extensible.

Currently supported card types:
*   Visa
*   Mastercard

## Features

*   **Luhn Algorithm Check**: Validates card numbers against the Luhn algorithm (Mod 10).
*   **Card Type Identification**: Identifies and validates card numbers belonging to Visa and Mastercard.
*   **Expiration Date Validation**: Ensures the card's expiration date has not passed and that the month/year are valid. Cards are considered valid through the entire expiration month.
*   **CVV Validation**: Validates the CVV (Card Verification Value). For Visa and Mastercard, a 3-digit CVV is expected.
*   **Extensible Design**: Uses a Strategy pattern (`ValidatorInterface`) allowing for new validation rules (e.g., for other card types or custom checks) to be added easily.

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

Here's a basic example of how to use the library to validate a credit card:

```php
<?php

require 'vendor/autoload.php'; // If installed via Composer

use App\CreditCard;

// Example details (replace with actual card data for real tests)
// Note: Using a known valid Visa Luhn number for demonstration.
$cardNumber = '49927398716'; // Valid Visa Luhn
$cardHolderName = 'John Doe';
$expirationMonth = 12; // December
$expirationYear = (int)date('Y') + 2; // Current year + 2
$cvv = '123';

$card = new CreditCard(
    $cardNumber,
    $cardHolderName,
    $expirationMonth,
    $expirationYear,
    $cvv
);

if ($card->isValid()) {
    echo "Credit card is valid.\n";
} else {
    echo "Credit card is invalid.\n";
    // You might want more detailed error reporting in a real application
    // by checking each validator or having isValid() return an array of errors.
}

// Example of an invalid card
$invalidCard = new CreditCard(
    '49927398717', // Invalid Luhn
    'Jane Doe',
    1, // January
    (int)date('Y') - 1, // Expired last year
    '12' // Invalid CVV
);

if ($invalidCard->isValid()) {
    echo "Invalid card somehow passed validation (this should not happen).\n";
} else {
    echo "Invalid card correctly reported as invalid.\n";
}
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
    Implement the `App\Validators\ValidatorInterface` and its `validate(CreditCard $card): bool` method. For example, to add support for American Express:
    ```php
    // src/Validators/AmexCardTypeValidator.php
    namespace App\Validators;
    use App\CreditCard;
    class AmexCardTypeValidator implements ValidatorInterface {
        public function validate(CreditCard $card): bool {
            // AMEX specific prefix and length checks
            $cardNumber = preg_replace('/[^\d]/', '', $card->getCardNumber());
            if (preg_match('/^3[47][0-9]{13}$/', $cardNumber)) {
                return true;
            }
            return false;
        }
    }
    ```

2.  **Integrate the new Validator**:
    Currently, validators are hardcoded in the `CreditCard::isValid()` method. To add your new validator, you would modify this method:
    ```php
    // In App\CreditCard::isValid()
    // ...
    $validators = [
        new LuhnValidator(),
        new CardTypeValidator(), // This currently only knows Visa/MC
        // new AmexCardTypeValidator(), // Add your new validator
        new ExpirationDateValidator(),
        new CvvValidator(), // This may also need adjustment for Amex (4-digit CVV)
    ];
    // ...
    ```
    For a more flexible approach, the `CreditCard` class could be refactored to accept a list of validators in its constructor or via a setter method. This would allow users of the library to customize the validation chain without modifying the library's core code. For example, `CardTypeValidator` could be made to take a list of supported card type patterns.

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License

This project is licensed under the MIT License - see the `composer.json` file for details (implicitly, as no separate LICENSE file was generated, but MIT was specified during `composer init`).
```
