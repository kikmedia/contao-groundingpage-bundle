<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Tests\Review;

use Kikmedia\GroundingPageBundle\Review\ReviewEvaluator;
use Kikmedia\GroundingPageBundle\Review\ReviewStatus;
use PHPUnit\Framework\TestCase;

class ReviewEvaluatorTest extends TestCase
{
    public function testWithoutRecordsTheReviewIsMissing(): void
    {
        $state = ReviewEvaluator::evaluate([], '1:a');

        self::assertSame(ReviewStatus::Missing, $state->status);
        self::assertNull($state->review);
    }

    public function testMatchingApprovalIsCurrent(): void
    {
        $state = ReviewEvaluator::evaluate([$this->record(1)], '1:a');

        self::assertSame(ReviewStatus::Current, $state->status);
        self::assertSame('2026-11-01', $state->visibleReview()?->date);
        self::assertSame('Person', $state->visibleReview()?->reviewerType);
        self::assertSame('Erika Muster', $state->visibleReview()?->reviewerName);
        self::assertSame(1, $state->approvedId);
    }

    public function testChangedContentIsStale(): void
    {
        $state = ReviewEvaluator::evaluate([$this->record(1)], '1:b');

        self::assertSame(ReviewStatus::Stale, $state->status);
        self::assertTrue($state->minorAllowed());
        self::assertNull($state->visibleReview());
    }

    public function testRevertedContentIsCurrentAgain(): void
    {
        $records = [$this->record(1)];

        self::assertSame(ReviewStatus::Stale, ReviewEvaluator::evaluate($records, '1:b')->status);
        self::assertSame(ReviewStatus::Current, ReviewEvaluator::evaluate($records, '1:a')->status);
    }

    public function testTheLatestRecordDecides(): void
    {
        $state = ReviewEvaluator::evaluate([$this->record(2, ['approved' => '0']), $this->record(1)], '1:a');

        self::assertSame(ReviewStatus::Rejected, $state->status);
        self::assertFalse($state->minorAllowed());
        self::assertNull($state->review);
    }

    public function testOrderOfTheListDoesNotMatter(): void
    {
        $records = [$this->record(1), $this->record(2, ['approved' => '0'])];

        self::assertSame(ReviewStatus::Rejected, ReviewEvaluator::evaluate($records, '1:a')->status);
    }

    public function testMinorConfirmationKeepsOriginalReviewerAndDate(): void
    {
        $state = ReviewEvaluator::evaluate([$this->record(1), $this->minor(2, 1)], '1:b');

        self::assertSame(ReviewStatus::Current, $state->status);
        self::assertSame('2026-11-01', $state->visibleReview()?->date);
        self::assertSame('Erika Muster', $state->visibleReview()?->reviewerName);
        self::assertSame(1, $state->approvedId);
    }

    public function testMinorConfirmationBecomesStaleOnTheNextChange(): void
    {
        $state = ReviewEvaluator::evaluate([$this->record(1), $this->minor(2, 1)], '1:c');

        self::assertSame(ReviewStatus::Stale, $state->status);
        self::assertTrue($state->minorAllowed());
    }

    public function testOnlyTheLatestFingerprintCounts(): void
    {
        $state = ReviewEvaluator::evaluate([$this->record(1), $this->minor(2, 1)], '1:a');

        self::assertSame(ReviewStatus::Stale, $state->status);
    }

    public function testMinorWithoutItsApprovalIsStale(): void
    {
        $state = ReviewEvaluator::evaluate([$this->minor(2, 1)], '1:b');

        self::assertSame(ReviewStatus::Stale, $state->status);
        self::assertNull($state->review);
        self::assertFalse($state->minorAllowed());
    }

    public function testMinorPointingToARejectionIsStale(): void
    {
        $state = ReviewEvaluator::evaluate([$this->record(1, ['approved' => '0']), $this->minor(2, 1)], '1:b');

        self::assertSame(ReviewStatus::Stale, $state->status);
        self::assertNull($state->review);
    }

    private function record(int $id, array $overrides = []): array
    {
        return $overrides + [
            'id' => $id,
            'kind' => 'review',
            'reference' => 0,
            'reviewDate' => '2026-11-01',
            'reviewerType' => 'Person',
            'reviewerName' => 'Erika Muster',
            'approved' => '1',
            'fingerprint' => '1:a',
        ];
    }

    private function minor(int $id, int $reference): array
    {
        return $this->record($id, [
            'kind' => 'minor',
            'reference' => $reference,
            'reviewDate' => '',
            'reviewerType' => '',
            'reviewerName' => '',
            'approved' => '0',
            'fingerprint' => '1:b',
        ]);
    }
}
