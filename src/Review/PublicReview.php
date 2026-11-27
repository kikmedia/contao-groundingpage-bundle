<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Review;

final readonly class PublicReview
{
    public function __construct(public string $date, public string $reviewerType, public string $reviewerName)
    {
    }
}
