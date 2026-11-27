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

    private const REPLACEMENTS = [
        '&#123;&#123;' => '[{]',
        '&#125;&#125;' => '[}]',
        '[&]'          => '&',
        '[lt]'         => '&lt;',
        '[gt]'         => '&gt;',
        '[nbsp]'       => '&nbsp;',
        '[-]'          => '&shy;',
    ];

    private const ENTITY = '~&(?:#\d+|#x[0-9a-f]+|[a-z][a-z0-9]*);~i';

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

    public static function hasHiddenElements(array $elements): bool
    {
        foreach ($elements as $row) {
            if (self::isHidden($row) || self::hasHiddenElements($row['children'] ?? [])) {
                return true;
            }
        }

        return false;
    }

    private static function isHidden(array $row): bool
    {
        return !\in_array((string) ($row['invisible'] ?? ''), ['', '0'], true);
    }

    private static function elements(array $elements): array
    {
        $nodes = [];

        foreach ($elements as $row) {
            if (self::isHidden($row)) {
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

        $fields = [];

        foreach (array_diff_key($row, array_flip(self::IGNORED_ELEMENT_FIELDS)) as $name => $value) {
            $fields[$name] = self::normalise($value);
        }

        $fields = self::withoutEmpty($fields);
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
            $items = [];

            foreach ($value as $key => $item) {
                $item = self::normalise($item);

                if (!\in_array($item, [null, '', []], true)) {
                    $items[$key] = $item;
                }
            }

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

        do {
            $previous = $value;
            $value = (string) preg_replace_callback(self::ENTITY, self::decodeEntity(...), strtr($value, self::REPLACEMENTS));
        } while ($value !== $previous);

        return $value;
    }

    private static function decodeEntity(array $match): string
    {
        $decoded = html_entity_decode($match[0], \ENT_QUOTES | \ENT_SUBSTITUTE | \ENT_HTML5, 'UTF-8');
        $literal = '{' === $decoded || '}' === $decoded || \in_array(strtolower($match[0]), ['&lt;', '&gt;'], true);

        return $literal ? $match[0] : $decoded;
    }
}
