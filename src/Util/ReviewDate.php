<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Util;

/**
 * The review date ("Zuletzt geprüft"). Contao stores a date field (rgxp "date") as a Unix timestamp in a string column;
 * older data and fixtures may hold a date string. Both are understood.
 */
final class ReviewDate
{
    /** A run of at least nine digits is a timestamp (an eight digit "20260115" is a date). */
    private const TIMESTAMP = '/^\d{9,}$/';

    public static function timestamp(mixed $value): ?int
    {
        if (null === $value || '' === $value || false === $value) {
            return null;
        }

        $string = trim((string) $value);

        if (1 === preg_match(self::TIMESTAMP, $string)) {
            return (int) $string;
        }

        if ('' === $string || '0' === $string) {
            return null;
        }

        $timestamp = strtotime($string);

        return false === $timestamp ? null : $timestamp;
    }

    /** The day as Y-m-d in the time zone of the site (for <time datetime> and schema.org dateModified). */
    public static function iso(mixed $value): ?string
    {
        $timestamp = self::timestamp($value);

        return null === $timestamp ? null : date('Y-m-d', $timestamp);
    }
}
