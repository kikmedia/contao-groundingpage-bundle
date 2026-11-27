<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Tests\Review;

use Kikmedia\GroundingPageBundle\Review\ReviewInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReviewInputTest extends TestCase
{
    #[DataProvider('inputs')]
    public function testErrors(array $changes, array $expected): void
    {
        self::assertSame($expected, $this->input($changes)->errors('2026-11-27', '2026-11-01'));
    }

    public static function inputs(): iterable
    {
        yield 'valid' => [[], []];
        yield 'same day as the last review' => [['date' => '2026-11-01'], []];
        yield 'today' => [['date' => '2026-11-27'], []];
        yield 'empty date' => [['date' => ''], ['date_invalid']];
        yield 'impossible date' => [['date' => '2026-02-30'], ['date_invalid']];
        yield 'local date format' => [['date' => '20.11.2026'], ['date_invalid']];
        yield 'future date' => [['date' => '2026-11-28'], ['date_future']];
        yield 'before the last review' => [['date' => '2026-10-31'], ['date_before_last']];
        yield 'unknown reviewer type' => [['reviewerType' => 'Thing'], ['reviewer_type']];
        yield 'blank name' => [['reviewerName' => '  '], ['reviewer_name']];
        yield 'long name' => [['reviewerName' => str_repeat('ä', 256)], ['reviewer_name']];
        yield 'longest name' => [['reviewerName' => str_repeat('ä', 255)], []];
        yield 'less than' => [['reviewerName' => 'A <b'], ['reviewer_name_chars']];
        yield 'greater than' => [['reviewerName' => 'A> b'], ['reviewer_name_chars']];
        yield 'insert tag opening' => [['reviewerName' => '{{date'], ['reviewer_name_chars']];
        yield 'insert tag closing' => [['reviewerName' => 'date}}'], ['reviewer_name_chars']];
        yield 'named entity' => [['reviewerName' => 'A &amp; B'], ['reviewer_name_chars']];
        yield 'numeric entity' => [['reviewerName' => 'A &#38; B'], ['reviewer_name_chars']];
        yield 'ordinary punctuation' => [['reviewerName' => 'Müller & Söhne "O\'Neil"'], []];
        yield 'no result' => [['approved' => null], ['result']];
        yield 'missing check' => [['checks' => ['accuracy', 'timeliness', 'verifiability', 'identification', 'other']], ['checks_incomplete']];
        yield 'rejection with note' => [['approved' => false, 'checks' => [], 'note' => 'Zahl falsch'], []];
        yield 'rejection without note' => [['approved' => false, 'note' => ' '], ['note_required']];
    }

    public function testNeedsNoEarlierReview(): void
    {
        self::assertSame([], $this->input()->errors('2026-11-27', null));
        self::assertSame([], $this->input()->errors('2026-11-27', '2026-11-20'));
    }

    private function input(array $changes = []): ReviewInput
    {
        return new ReviewInput(...($changes + [
            'date' => '2026-11-20',
            'reviewerType' => 'Person',
            'reviewerName' => 'Erika Muster',
            'checks' => ReviewInput::CHECKS,
            'approved' => true,
            'note' => '',
        ]));
    }
}
