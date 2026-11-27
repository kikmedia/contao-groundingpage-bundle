<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Review;

final class ReviewEvaluator
{
    public static function evaluate(array $records, string $fingerprint): ReviewState
    {
        $byId = [];

        foreach ($records as $record) {
            $byId[(int) $record['id']] = $record;
        }

        if ([] === $byId) {
            return new ReviewState(ReviewStatus::Missing);
        }

        $latest = $byId[max(array_keys($byId))];
        $minor = 'minor' === $latest['kind'];
        $approval = $minor ? $byId[(int) $latest['reference']] ?? null : $latest;

        if (null === $approval || 'review' !== $approval['kind'] || !$approval['approved']) {
            return new ReviewState($minor ? ReviewStatus::Stale : ReviewStatus::Rejected);
        }

        return new ReviewState(
            $latest['fingerprint'] === $fingerprint ? ReviewStatus::Current : ReviewStatus::Stale,
            new PublicReview((string) $approval['reviewDate'], (string) $approval['reviewerType'], (string) $approval['reviewerName']),
            (int) $approval['id'],
        );
    }
}
