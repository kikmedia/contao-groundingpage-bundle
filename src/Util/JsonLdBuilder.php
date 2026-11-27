<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Util;

use Contao\StringUtil;
use Kikmedia\GroundingPageBundle\Model\GroundingPageModel;

final class JsonLdBuilder
{
    private const FLAGS = \JSON_HEX_TAG | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT;

    public static function build(GroundingPageModel $entry, string $canonicalUrl, bool $inputEncoded = false): string
    {
        $text = static fn (mixed $value): string => PlainText::decode($value, $inputEncoded);

        $type = 'other' === $entry->entityType && $entry->entityTypeCustom
            ? $entry->entityTypeCustom
            : $entry->entityType;

        $data = [
            '@context'    => 'https://schema.org',
            '@type'       => $text($type) ?: 'Thing',
            'name'        => $text($entry->title),
            'description' => $text($entry->definition) ?: null,
            'url'         => $canonicalUrl,
        ];

        $reviewed = ReviewDate::iso($entry->lastReviewed);

        if (null !== $reviewed) {
            $data['dateModified'] = $reviewed;
        }

        $facts = array_filter((array) StringUtil::deserialize($entry->coreFacts, true));

        if (!empty($facts)) {
            $data['additionalProperty'] = array_values(array_filter(array_map(
                static function (array $fact) use ($text): array|null {
                    if (empty($fact['label']) || !isset($fact['value']) || '' === $fact['value']) {
                        return null;
                    }

                    return [
                        '@type' => 'PropertyValue',
                        'name'  => $text($fact['label']),
                        'value' => $text($fact['value']),
                    ];
                },
                $facts
            )));
        }

        $sources = array_filter((array) StringUtil::deserialize($entry->sources, true));

        if (!empty($sources)) {
            $data['citation'] = array_values(array_filter(array_map(
                static function (array $source) use ($text): array|null {
                    if (empty($source['value'])) {
                        return null;
                    }

                    return [
                        '@type' => 'CreativeWork',
                        'name'  => $text($source['label'] ?: $source['value']),
                        'url'   => $text($source['value']),
                    ];
                },
                $sources
            )));
        }

        $data = array_filter($data, static fn ($value) => null !== $value && '' !== $value && [] !== $value);

        return json_encode($data, self::FLAGS) ?: '{}';
    }

    public static function buildFaqPage(array $items, string $canonicalUrl, bool $inputEncoded = false): string|null
    {
        $text = static fn (mixed $value): string => PlainText::decode($value, $inputEncoded);

        $questions = array_values(array_filter(array_map(
            static function (array $item) use ($text): array|null {
                if (empty($item['label']) || !isset($item['value']) || '' === $item['value']) {
                    return null;
                }

                return [
                    '@type' => 'Question',
                    'name'  => $text($item['label']),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => $text($item['value']),
                    ],
                ];
            },
            $items
        )));

        if (empty($questions)) {
            return null;
        }

        $data = [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'url'        => $canonicalUrl,
            'mainEntity' => $questions,
        ];

        return json_encode($data, self::FLAGS) ?: null;
    }
}
