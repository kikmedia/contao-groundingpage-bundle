<?php

declare(strict_types=1);

namespace Grounding\PageBundle\Util;

use Contao\StringUtil;
use Grounding\PageBundle\Model\GroundingPageModel;

/**
 * Builds the JSON-LD representation of a Grounding-Page entry.
 *
 * Per the Grounding Page Standard (§9 "JSON-LD and mirroring"), the
 * structured data must mirror the visible content 1:1 – no additional
 * marketing claims that are invisible on the page. Every value used here
 * therefore comes directly from a field that is also rendered visibly by
 * the reader template.
 */
final class JsonLdBuilder
{
    public static function build(GroundingPageModel $entry, string $canonicalUrl): string
    {
        $type = 'other' === $entry->entityType && $entry->entityTypeCustom
            ? $entry->entityTypeCustom
            : $entry->entityType;

        $data = [
            '@context'    => 'https://schema.org',
            '@type'       => $type ?: 'Thing',
            'name'        => $entry->title,
            'description' => $entry->definition ?: null,
            'url'         => $canonicalUrl,
        ];

        if ($entry->lastReviewed) {
            $timestamp = strtotime((string) $entry->lastReviewed);

            if (false !== $timestamp) {
                $data['dateModified'] = date('Y-m-d', $timestamp);
            }
        }

        $facts = array_filter((array) StringUtil::deserialize($entry->coreFacts, true));

        if (!empty($facts)) {
            $data['additionalProperty'] = array_values(array_filter(array_map(
                static function (array $fact): array|null {
                    if (empty($fact['label']) || !isset($fact['value']) || '' === $fact['value']) {
                        return null;
                    }

                    return [
                        '@type' => 'PropertyValue',
                        'name'  => $fact['label'],
                        'value' => $fact['value'],
                    ];
                },
                $facts
            )));
        }

        $sources = array_filter((array) StringUtil::deserialize($entry->sources, true));

        if (!empty($sources)) {
            $data['citation'] = array_values(array_filter(array_map(
                static function (array $source): array|null {
                    if (empty($source['value'])) {
                        return null;
                    }

                    return [
                        '@type' => 'CreativeWork',
                        'name'  => $source['label'] ?: $source['value'],
                        'url'   => $source['value'],
                    ];
                },
                $sources
            )));
        }

        // Remove empty/null keys so the JSON-LD never contains data that
        // is not also visible on the page.
        $data = array_filter($data, static fn ($value) => null !== $value && '' !== $value && [] !== $value);

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}';
    }
}
