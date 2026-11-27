<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Review;

enum ReviewStatus: string
{
    case Current = 'current';
    case Stale = 'stale';
    case Rejected = 'rejected';
    case Missing = 'missing';
}
