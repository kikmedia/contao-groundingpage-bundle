<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Review;

final readonly class ReviewState
{
    public function __construct(public ReviewStatus $status, public PublicReview|null $review = null, public int $approvedId = 0)
    {
    }

    public function minorAllowed(): bool
    {
        return ReviewStatus::Stale === $this->status && null !== $this->review;
    }

    public function visibleReview(): PublicReview|null
    {
        return ReviewStatus::Current === $this->status ? $this->review : null;
    }
}
