<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Check;

use Kikmedia\GroundingPageBundle\Review\ReviewState;

final readonly class CheckContext
{
    public function __construct(
        public array $entry,
        public array $elements,
        public ReviewState $review,
        public array|null $page,
        public bool $imageAltMissing,
        public bool $archiveListed,
        public int $now,
    ) {
    }
}
