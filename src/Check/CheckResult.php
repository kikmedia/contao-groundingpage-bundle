<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Check;

final readonly class CheckResult
{
    public function __construct(
        public string $requirement,
        public CheckLevel $level,
        public bool $passed,
        public string $message,
        public array $parameters = [],
    ) {
    }
}
