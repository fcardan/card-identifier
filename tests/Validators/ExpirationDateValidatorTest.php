<?php

namespace App\Tests\Validators;

use App\CreditCard;
use App\Validators\ExpirationDateValidator;
use PHPUnit\Framework\TestCase;
use DateTime;

/**
 * Tests for the ExpirationDateValidator class.
 */
class ExpirationDateValidatorTest extends TestCase
{
    private ExpirationDateValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ExpirationDateValidator();
    }

    // --- Data Providers ---

    /**
     * Provides valid (future) expiration dates.
     * @return array<array{int, int}>
     */
    public function validFutureDatesProvider(): array
    {
        $currentYear = (int)(new DateTime())->format('Y');
        $currentMonth = (int)(new DateTime())->format('m');

        $dates = [
            [$currentMonth, $currentYear + 1], // Current month, next year
            [1, $currentYear + 2],             // Jan, two years from now
            [12, $currentYear + 5],            // Dec, five years from now
        ];

        // Add next month, current year if not December
        if ($currentMonth < 12) {
            $dates[] = [$currentMonth + 1, $currentYear];
        } else { // If December, use Jan of next year (already covered, but good for clarity)
            $dates[] = [1, $currentYear + 1];
        }
        // Add current month, current year (should be valid)
        $dates[] = [$currentMonth, $currentYear];

        return $dates;
    }

    /**
     * Provides invalid (past) expiration dates.
     * @return array<array{int, int}>
     */
    public function pastDatesProvider(): array
    {
        $currentYear = (int)(new DateTime())->format('Y');
        $currentMonth = (int)(new DateTime())->format('m');

        $dates = [
            [$currentMonth, $currentYear - 1], // Current month, last year
            [1, $currentYear - 2],             // Jan, two years ago
            [12, $currentYear - 5],            // Dec, five years ago
        ];

        // Add previous month, current year if not January
        if ($currentMonth > 1) {
            $dates[] = [$currentMonth - 1, $currentYear];
        } else { // If January, use Dec of previous year
            $dates[] = [12, $currentYear - 1];
        }
        return $dates;
    }

    /**
     * Provides invalid expiration months.
     * @return array<array{int, int}>
     */
    public function invalidMonthsProvider(): array
    {
        $currentYear = (int)(new DateTime())->format('Y');
        return [
            [0, $currentYear + 1],  // Month 0
            [13, $currentYear + 1], // Month 13
            [-1, $currentYear + 2], // Negative month
        ];
    }

    // --- Test Methods ---

    /**
     * @dataProvider validFutureDatesProvider
     * @param int $month Expiration month.
     * @param int $year Expiration year.
     */
    public function testValidExpirationDates(int $month, int $year): void
    {
        $card = new CreditCard('1234567890123456', 'Test Holder', $month, $year, '123');
        $this->assertTrue($this->validator->validate($card), "Failed for valid date: {$month}/{$year}");
    }

    /**
     * @dataProvider pastDatesProvider
     * @param int $month Expiration month.
     * @param int $year Expiration year.
     */
    public function testInvalidExpirationDatesPast(int $month, int $year): void
    {
        $card = new CreditCard('1234567890123456', 'Test Holder', $month, $year, '123');
        $this->assertFalse($this->validator->validate($card), "Passed for past date: {$month}/{$year}");
    }

    /**
     * @dataProvider invalidMonthsProvider
     * @param int $month Invalid expiration month.
     * @param int $year Expiration year.
     */
    public function testInvalidExpirationMonth(int $month, int $year): void
    {
        $card = new CreditCard('1234567890123456', 'Test Holder', $month, $year, '123');
        $this->assertFalse($this->validator->validate($card), "Passed for invalid month: {$month}/{$year}");
    }

    /**
     * Tests current month, future year.
     */
    public function testCurrentMonthFutureYearIsValid(): void
    {
        $currentMonth = (int)(new DateTime())->format('m');
        $futureYear = (int)(new DateTime())->format('Y') + 1;
        $card = new CreditCard('1234567890123456', 'Test Holder', $currentMonth, $futureYear, '123');
        $this->assertTrue($this->validator->validate($card), "Failed for current month {$currentMonth}, future year {$futureYear}");
    }

    /**
     * Tests current month, current year.
     */
    public function testCurrentMonthAndYearIsValid(): void
    {
        $currentMonth = (int)(new DateTime())->format('m');
        $currentYear = (int)(new DateTime())->format('Y');
        $card = new CreditCard('1234567890123456', 'Test Holder', $currentMonth, $currentYear, '123');
        $this->assertTrue($this->validator->validate($card), "Failed for current month {$currentMonth}, current year {$currentYear}");
    }

    /**
     * Tests previous month, current year.
     */
    public function testLastMonthCurrentYearIsInvalid(): void
    {
        $currentDate = new DateTime();
        $currentYear = (int)$currentDate->format('Y');
        
        // Handle January case: previous month is December of the previous year
        if ((int)$currentDate->format('m') === 1) {
            $lastMonth = 12;
            $yearForTest = $currentYear - 1;
        } else {
            $lastMonth = (int)$currentDate->format('m') - 1;
            $yearForTest = $currentYear;
        }

        $card = new CreditCard('1234567890123456', 'Test Holder', $lastMonth, $yearForTest, '123');
        $this->assertFalse($this->validator->validate($card), "Passed for last month {$lastMonth}, year {$yearForTest}");
    }
    
    /**
     * Tests that a card expiring in the current month and year is valid,
     * as it's valid for the entire expiration month.
     */
    public function testValidExpirationAtEndOfMonth(): void
    {
        // Assuming today is any day of the month.
        // A card expiring this month/year should be valid.
        $currentMonth = (int)(new DateTime())->format('m');
        $currentYear = (int)(new DateTime())->format('Y');

        $card = new CreditCard('1234567890123456', 'Test Holder', $currentMonth, $currentYear, '123');
        $this->assertTrue(
            $this->validator->validate($card),
            "Failed for card expiring current month/year: {$currentMonth}/{$currentYear}. Card should be valid through the end of the month."
        );

        // Test for a specific scenario: If today is Nov 15, 2023, an expiry of 11/2023 should be valid.
        // This is covered by the general case above but can be explicitly stated if desired.
        // For the purpose of this test, the dynamic currentMonth/currentYear is sufficient.
    }
}
