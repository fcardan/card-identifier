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
    private array $validResult = ['valid' => true, 'message' => ''];

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
        } else { // If December, use Jan of next year
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
            // This case will have expiration year in the past
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
     * @covers \App\Validators\ExpirationDateValidator::validate
     */
    public function testValidExpirationDates(int $month, int $year): void
    {
        $card = new CreditCard('1234567890123456', 'Test Holder', $month, $year, '123');
        $this->assertSame($this->validResult, $this->validator->validate($card), "Failed for valid date: {$month}/{$year}");
    }

    /**
     * @dataProvider pastDatesProvider
     * @param int $expMonth Expiration month.
     * @param int $expYear Expiration year.
     * @covers \App\Validators\ExpirationDateValidator::validate
     */
    public function testInvalidExpirationDatesPast(int $expMonth, int $expYear): void
    {
        $card = new CreditCard('1234567890123456', 'Test Holder', $expMonth, $expYear, '123');
        $result = $this->validator->validate($card);

        $this->assertFalse($result['valid']);

        $currentDate = new DateTime();
        $currentYear = (int)$currentDate->format('Y');
        $currentMonth = (int)$currentDate->format('m');

        if ($expYear < $currentYear) {
            $this->assertEquals('Card has expired. Expiration year is in the past.', $result['message'], "Incorrect message for past year: {$expMonth}/{$expYear}");
        } elseif ($expYear === $currentYear && $expMonth < $currentMonth) {
            $this->assertEquals('Card has expired.', $result['message'], "Incorrect message for past month, current year: {$expMonth}/{$expYear}");
        } else {
            // This case should ideally not be hit if data provider and validator logic are aligned.
            // However, it's a fallback to ensure the test provides some detail if a case is missed.
            $this->markTestIncomplete("Date {$expMonth}/{$expYear} resulted in unexpected valid=false state or message: '{$result['message']}'. Check provider and validator logic.");
        }
    }

    /**
     * @dataProvider invalidMonthsProvider
     * @param int $month Invalid expiration month.
     * @param int $year Expiration year.
     * @covers \App\Validators\ExpirationDateValidator::validate
     */
    public function testInvalidExpirationMonth(int $month, int $year): void
    {
        $card = new CreditCard('1234567890123456', 'Test Holder', $month, $year, '123');
        $expected = ['valid' => false, 'message' => 'Invalid expiration month.'];
        $this->assertSame($expected, $this->validator->validate($card), "Passed for invalid month: {$month}/{$year}");
    }

    /**
     * Tests current month, future year.
     * @covers \App\Validators\ExpirationDateValidator::validate
     */
    public function testCurrentMonthFutureYearIsValid(): void
    {
        $currentMonth = (int)(new DateTime())->format('m');
        $futureYear = (int)(new DateTime())->format('Y') + 1;
        $card = new CreditCard('1234567890123456', 'Test Holder', $currentMonth, $futureYear, '123');
        $this->assertSame($this->validResult, $this->validator->validate($card), "Failed for current month {$currentMonth}, future year {$futureYear}");
    }

    /**
     * Tests current month, current year.
     * @covers \App\Validators\ExpirationDateValidator::validate
     */
    public function testCurrentMonthAndYearIsValid(): void
    {
        $currentMonth = (int)(new DateTime())->format('m');
        $currentYear = (int)(new DateTime())->format('Y');
        $card = new CreditCard('1234567890123456', 'Test Holder', $currentMonth, $currentYear, '123');
        $this->assertSame($this->validResult, $this->validator->validate($card), "Failed for current month {$currentMonth}, current year {$currentYear}");
    }

    /**
     * Tests previous month, current year.
     * @covers \App\Validators\ExpirationDateValidator::validate
     */
    public function testLastMonthCurrentYearIsInvalid(): void
    {
        $currentDate = new DateTime();
        $currentYear = (int)$currentDate->format('Y');
        $lastMonth = (int)$currentDate->format('m') - 1;
        $yearForTest = $currentYear;

        if ($lastMonth < 1) { // Handles January, making previous month December of previous year
            $lastMonth = 12;
            $yearForTest = $currentYear - 1;
            $expectedMessage = 'Card has expired. Expiration year is in the past.';
        } else {
            $expectedMessage = 'Card has expired.';
        }

        $card = new CreditCard('1234567890123456', 'Test Holder', $lastMonth, $yearForTest, '123');
        $expected = ['valid' => false, 'message' => $expectedMessage];
        $this->assertSame($expected, $this->validator->validate($card), "Passed for last month {$lastMonth}, year {$yearForTest}");
    }

    /**
     * Tests that a card expiring in the current month and year is valid,
     * as it's valid for the entire expiration month.
     * @covers \App\Validators\ExpirationDateValidator::validate
     */
    public function testValidExpirationAtEndOfMonth(): void
    {
        $currentMonth = (int)(new DateTime())->format('m');
        $currentYear = (int)(new DateTime())->format('Y');

        $card = new CreditCard('1234567890123456', 'Test Holder', $currentMonth, $currentYear, '123');
        $this->assertSame(
            $this->validResult,
            $this->validator->validate($card),
            "Failed for card expiring current month/year: {$currentMonth}/{$currentYear}. Card should be valid through the end of the month."
        );
    }
}
