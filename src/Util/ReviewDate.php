<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Util;

final class ReviewDate
{
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

    public static function iso(mixed $value): ?string
    {
        $timestamp = self::timestamp($value);

        return null === $timestamp ? null : date('Y-m-d', $timestamp);
    }
}
