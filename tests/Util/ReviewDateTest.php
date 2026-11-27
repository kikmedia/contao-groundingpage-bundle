<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Tests\Util;

use Kikmedia\GroundingPageBundle\Util\ReviewDate;
use PHPUnit\Framework\TestCase;

class ReviewDateTest extends TestCase
{
    private string $timezone;

    protected function setUp(): void
    {
        $this->timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timezone);
    }

    public function testContaoStoresADateFieldAsAUnixTimestampString(): void
    {
        self::assertSame(1793660400, ReviewDate::timestamp('1793660400'));
        self::assertSame(1793660400, ReviewDate::timestamp(1793660400));
    }

    public function testDateStringsAreStillAccepted(): void
    {
        self::assertSame(strtotime('2026-01-15'), ReviewDate::timestamp('2026-01-15'));
        self::assertSame(strtotime('2026-01-15'), ReviewDate::timestamp('15.01.2026'));
    }

    public function testEmptyAndUnusableValuesGiveNull(): void
    {
        self::assertNull(ReviewDate::timestamp(''));
        self::assertNull(ReviewDate::timestamp(null));
        self::assertNull(ReviewDate::timestamp('0'));
        self::assertNull(ReviewDate::timestamp('not a date'));
    }

    public function testIsoDateIsTheDayInTheTimezoneOfTheSite(): void
    {
        self::assertSame('2026-11-03', ReviewDate::iso('1793660400'));
        self::assertNull(ReviewDate::iso(''));
    }
}
