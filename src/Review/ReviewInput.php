<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Review;

final readonly class ReviewInput
{
    public const CHECKS = ['accuracy', 'timeliness', 'verifiability', 'identification', 'consistency'];

    public const REVIEWER_TYPES = ['Person', 'Organization'];

    private const MARKUP = '~<|>|\{\{|\}\}|&(?:#\d+|#x[0-9a-f]+|[a-z][a-z0-9]*);~i';

    public function __construct(
        public string $date,
        public string $reviewerType,
        public string $reviewerName,
        public array $checks,
        public bool|null $approved,
        public string $note,
    ) {
    }

    public function errors(string $today, string|null $lastDate): array
    {
        $errors = [];

        if (!self::isDate($this->date)) {
            $errors[] = 'date_invalid';
        } elseif ($this->date > $today) {
            $errors[] = 'date_future';
        } elseif (null !== $lastDate && $this->date < $lastDate) {
            $errors[] = 'date_before_last';
        }

        if (!\in_array($this->reviewerType, self::REVIEWER_TYPES, true)) {
            $errors[] = 'reviewer_type';
        }

        $name = trim($this->reviewerName);

        if ('' === $name || mb_strlen($name) > 255) {
            $errors[] = 'reviewer_name';
        } elseif (1 === preg_match(self::MARKUP, $name)) {
            $errors[] = 'reviewer_name_chars';
        }

        if (null === $this->approved) {
            $errors[] = 'result';
        } elseif ($this->approved && [] !== array_diff(self::CHECKS, $this->checks)) {
            $errors[] = 'checks_incomplete';
        } elseif (!$this->approved && '' === trim($this->note)) {
            $errors[] = 'note_required';
        }

        return $errors;
    }

    private static function isDate(string $value): bool
    {
        return 1 === preg_match('~^(\d{4})-(\d{2})-(\d{2})$~', $value, $parts)
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }
}
