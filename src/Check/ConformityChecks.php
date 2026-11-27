<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Check;

use Contao\StringUtil;
use Kikmedia\GroundingPageBundle\Review\ReviewStatus;
use Kikmedia\GroundingPageBundle\Util\ReviewDate;

final class ConformityChecks
{
    private const URL = 'https://groundingpage.com/spec/v1.7/';

    private const HTML_FIELDS = ['text', 'html', 'unfilteredHtml'];

    public static function run(CheckContext $context): array
    {
        $entry = $context->entry;

        $results = [
            self::required('P-2', 'p2', '' !== trim((string) ($entry['definition'] ?? ''))),
            self::required('P-3', 'p3', self::headingsNameTheEntity((string) ($entry['title'] ?? ''), $context->elements)),
            self::required('P-4', 'p4', self::hasFact($entry)),
            self::required('P-5', 'p5', self::hasSpecificType($entry)),
            self::required('P-7', 'p7', self::isVisible($entry, $context->now) && self::isIndexable($context->page)),
            self::required('P-8', 'p8', ReviewStatus::Current === $context->review->status),
            self::hint('P-6', 'p6_alt', !$context->imageAltMissing),
            self::hint('P-11', 'p11', '' !== trim((string) ($entry['distinction'] ?? ''))),
            self::hint('P-12', 'p12', self::hasWebSources($entry)),
            self::hint('P-15', 'p15', null !== $context->page && $context->archiveListed),
            self::age($context->review->review?->date, $context->now),
            self::info('P-6', 'p6'),
            self::info('P-9', 'p9'),
            self::info('P-10', 'p10'),
        ];

        $legacy = ReviewDate::iso($entry['lastReviewed'] ?? null);

        if (null !== $legacy) {
            $results[] = self::info('', 'legacy_date', [$legacy]);
        }

        return $results;
    }

    public static function requiredPassed(array $results): bool
    {
        foreach ($results as $result) {
            if (CheckLevel::Required === $result->level && !$result->passed) {
                return false;
            }
        }

        return true;
    }

    public static function statementUrl(string $language): string
    {
        return str_starts_with(strtolower($language), 'de') ? self::URL . 'de/' : self::URL;
    }

    private static function required(string $requirement, string $message, bool $passed): CheckResult
    {
        return new CheckResult($requirement, CheckLevel::Required, $passed, $message);
    }

    private static function hint(string $requirement, string $message, bool $passed): CheckResult
    {
        return new CheckResult($requirement, CheckLevel::Hint, $passed, $message);
    }

    private static function info(string $requirement, string $message, array $parameters = []): CheckResult
    {
        return new CheckResult($requirement, CheckLevel::Info, true, $message, $parameters);
    }

    private static function age(string|null $date, int $now): CheckResult
    {
        $olderThan = static fn (string $age): bool => null !== $date && $date < date('Y-m-d', (int) strtotime($age, $now));

        return self::hint('P-16', $olderThan('-12 months') ? 'p16_late' : 'p16', !$olderThan('-6 months'));
    }

    private static function headingsNameTheEntity(string $title, array $elements): bool
    {
        $name = self::plain($title);

        foreach (self::headings($elements) as $heading) {
            $heading = self::plain(strip_tags($heading));

            if ('' !== $heading && false === mb_stripos($heading, $name)) {
                return false;
            }
        }

        return true;
    }

    private static function headings(array $elements): array
    {
        $headings = [];

        foreach ($elements as $element) {
            if (!\in_array((string) ($element['invisible'] ?? ''), ['', '0'], true)) {
                continue;
            }

            $headline = StringUtil::deserialize($element['headline'] ?? null, true);

            if ('h2' === ($headline['unit'] ?? null)) {
                $headings[] = (string) ($headline['value'] ?? '');
            }

            foreach (self::HTML_FIELDS as $field) {
                preg_match_all('~<h2\b[^>]*>(.*?)</h2>~is', (string) ($element[$field] ?? ''), $matches);
                array_push($headings, ...$matches[1]);
            }

            array_push($headings, ...self::headings($element['children'] ?? []));
        }

        return $headings;
    }

    private static function plain(string $value): string
    {
        $value = str_replace(['[nbsp]', '[-]', '[&]', '[lt]', '[gt]'], [' ', '', '&', '<', '>'], $value);
        $value = html_entity_decode($value, \ENT_QUOTES | \ENT_SUBSTITUTE | \ENT_HTML5, 'UTF-8');
        $value = str_replace("\u{00AD}", '', $value);

        return trim((string) preg_replace('~[\s\x{00A0}]+~u', ' ', $value));
    }

    private static function rows(mixed $value): array
    {
        return array_filter(StringUtil::deserialize($value, true), \is_array(...));
    }

    private static function hasFact(array $entry): bool
    {
        foreach (self::rows($entry['coreFacts'] ?? null) as $fact) {
            if ('' !== trim((string) ($fact['label'] ?? '')) && '' !== trim((string) ($fact['value'] ?? ''))) {
                return true;
            }
        }

        return false;
    }

    private static function hasSpecificType(array $entry): bool
    {
        $type = 'other' === ($entry['entityType'] ?? '') ? $entry['entityTypeCustom'] ?? '' : $entry['entityType'] ?? '';

        return !\in_array(strtolower(trim((string) $type)), ['', 'thing', 'webpage'], true);
    }

    private static function hasWebSources(array $entry): bool
    {
        $sources = array_filter(
            self::rows($entry['sources'] ?? null),
            static fn (array $source): bool => '' !== trim(($source['label'] ?? '') . ($source['value'] ?? ''))
        );

        foreach ($sources as $source) {
            if (1 !== preg_match('~^https?://~i', trim((string) ($source['value'] ?? '')))) {
                return false;
            }
        }

        return [] !== $sources;
    }

    private static function isVisible(array $entry, int $now): bool
    {
        $start = (string) ($entry['start'] ?? '');
        $stop = (string) ($entry['stop'] ?? '');

        return !empty($entry['published'])
            && ('' === $start || (int) $start <= $now)
            && ('' === $stop || (int) $stop > $now);
    }

    private static function isIndexable(array|null $page): bool
    {
        return null !== $page && $page['published'] && !$page['protected'] && !$page['noindex'];
    }
}
