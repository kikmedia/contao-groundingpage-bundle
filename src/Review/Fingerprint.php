<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Review;

final class Fingerprint
{
    private const VERSION = '1';

    private const ENTRY_FIELDS = [
        'title', 'entityType', 'entityTypeCustom', 'definition', 'placement', 'distinction', 'coreFacts', 'sources',
        'relatedPages', 'addImage', 'singleSRC',
    ];

    private const IGNORED_ELEMENT_FIELDS = [
        'id', 'pid', 'ptable', 'sorting', 'tstamp', 'invisible', 'start', 'stop', 'protected', 'groups', 'guests',
        'cssID', 'space', 'customTpl', 'size', 'floating', 'fullsize', 'imagemargin', 'perRow', 'perPage',
        'galleryTpl', 'playerSize', 'playerAspect', 'playerColor', 'sliderDelay', 'sliderSpeed', 'sliderStartSlide',
        'sliderContinuous', 'com_order', 'com_perPage', 'com_moderate', 'com_bbcode', 'com_requireLogin',
        'com_disableCaptcha', 'com_template', 'children',
    ];

    private const CONTAO_MARKERS = [
        '[{]' => '{{',
        '[}]' => '}}',
        '[&]' => '&',
        '[lt]' => '<',
        '[gt]' => '>',
        '[nbsp]' => '&nbsp;',
        '[-]' => '&shy;',
    ];

    public static function of(array $entry, array $elements): string
    {
        $values = [];

        foreach (self::ENTRY_FIELDS as $field) {
            $values[$field] = self::normalise($entry[$field] ?? null);
        }

        $values = self::withoutEmpty($values);

        if (\is_array($values['relatedPages'] ?? null)) {
            sort($values['relatedPages'], \SORT_STRING);
        }

        $data = ['entry' => $values, 'elements' => self::elements($elements)];

        return self::VERSION . ':' . hash('sha256', json_encode($data, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE));
    }

    private static function elements(array $elements): array
    {
        $nodes = [];

        foreach ($elements as $row) {
            if (!\in_array((string) ($row['invisible'] ?? ''), ['', '0'], true)) {
                continue;
            }

            $nodes[] = [
                'fields'   => self::fields($row),
                'children' => self::elements($row['children'] ?? []),
            ];
        }

        return $nodes;
    }

    private static function fields(array $row): array
    {
        $virtual = json_decode((string) ($row['jsonData'] ?? ''), true);
        unset($row['jsonData']);

        if (\is_array($virtual) && !array_is_list($virtual)) {
            $row = array_merge($row, $virtual);
        }

        $row = array_diff_key($row, array_flip(self::IGNORED_ELEMENT_FIELDS));
        $fields = self::withoutEmpty(array_map(self::normalise(...), $row));
        ksort($fields);

        return $fields;
    }

    private static function withoutEmpty(array $values): array
    {
        return array_filter($values, static fn (mixed $value): bool => !\in_array($value, [null, '', '0', []], true));
    }

    private static function normalise(mixed $value): mixed
    {
        if (null === $value) {
            return null;
        }

        if (\is_array($value)) {
            $items = array_filter(
                array_map(self::normalise(...), $value),
                static fn (mixed $item): bool => !\in_array($item, [null, '', []], true)
            );

            if (array_is_list($value)) {
                return array_values($items);
            }

            ksort($items);

            return $items;
        }

        $value = (string) $value;

        if (1 === preg_match('~^a:\d+:\{~', $value)) {
            $list = @unserialize($value, ['allowed_classes' => false]);

            if (\is_array($list)) {
                return self::normalise($list);
            }
        }

        return self::plain($value);
    }

    private static function plain(string $value): string
    {
        if (!mb_check_encoding($value, 'UTF-8')) {
            return 'hex:' . bin2hex($value);
        }

        $value = strtr($value, self::CONTAO_MARKERS);

        do {
            $previous = $value;
            $value = html_entity_decode($value, \ENT_QUOTES | \ENT_SUBSTITUTE | \ENT_HTML5, 'UTF-8');
        } while ($value !== $previous);

        return $value;
    }
}
