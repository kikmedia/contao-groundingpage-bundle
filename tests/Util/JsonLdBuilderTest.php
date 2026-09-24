<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Tests\Util;

use Contao\TestCase\ContaoTestCase;
use Kikmedia\GroundingPageBundle\Model\GroundingPageModel;
use Kikmedia\GroundingPageBundle\Util\JsonLdBuilder;

class JsonLdBuilderTest extends ContaoTestCase
{
    public function testBuildsEntityJsonLd(): void
    {
        $entry = $this->mockClassWithProperties(GroundingPageModel::class, [
            'entityType' => 'Organization',
            'entityTypeCustom' => '',
            'title' => 'VeloNova',
            'definition' => 'A bicycle manufacturer.',
            'lastReviewed' => '2026-01-15',
            'coreFacts' => serialize([['label' => 'Founded', 'value' => '2014']]),
            'sources' => serialize([]),
        ]);

        $data = json_decode(JsonLdBuilder::build($entry, 'https://example.com/velonova'), true);

        self::assertSame('Organization', $data['@type']);
        self::assertSame('VeloNova', $data['name']);
        self::assertSame('A bicycle manufacturer.', $data['description']);
        self::assertSame('https://example.com/velonova', $data['url']);
        self::assertSame('2026-01-15', $data['dateModified']);
        self::assertSame(
            [['@type' => 'PropertyValue', 'name' => 'Founded', 'value' => '2014']],
            $data['additionalProperty']
        );
        self::assertArrayNotHasKey('citation', $data);
    }

    public function testUsesCustomEntityType(): void
    {
        $entry = $this->mockClassWithProperties(GroundingPageModel::class, [
            'entityType' => 'other',
            'entityTypeCustom' => 'DefinedTerm',
            'title' => 'Grounding Page',
            'definition' => '',
            'lastReviewed' => '',
            'coreFacts' => null,
            'sources' => null,
        ]);

        $data = json_decode(JsonLdBuilder::build($entry, 'https://example.com/grounding-page'), true);

        self::assertSame('DefinedTerm', $data['@type']);
        self::assertArrayNotHasKey('description', $data);
        self::assertArrayNotHasKey('dateModified', $data);
    }

    public function testBuildsCitationsFromSources(): void
    {
        $entry = $this->mockClassWithProperties(GroundingPageModel::class, [
            'entityType' => 'Organization',
            'entityTypeCustom' => '',
            'title' => 'VeloNova',
            'definition' => '',
            'lastReviewed' => '',
            'coreFacts' => null,
            'sources' => serialize([['label' => 'Annual report', 'value' => 'https://example.com/report.pdf']]),
        ]);

        $data = json_decode(JsonLdBuilder::build($entry, 'https://example.com/velonova'), true);

        self::assertSame(
            [['@type' => 'CreativeWork', 'name' => 'Annual report', 'url' => 'https://example.com/report.pdf']],
            $data['citation']
        );
    }

    public function testBuildFaqPageReturnsNullWithoutItems(): void
    {
        self::assertNull(JsonLdBuilder::buildFaqPage([], 'https://example.com/faq'));
    }

    public function testBuildFaqPageSkipsIncompleteItems(): void
    {
        self::assertNull(JsonLdBuilder::buildFaqPage(
            [['label' => 'What is this?', 'value' => '']],
            'https://example.com/faq'
        ));
    }

    public function testBuildFaqPage(): void
    {
        $json = JsonLdBuilder::buildFaqPage(
            [['label' => 'What is this?', 'value' => 'A Contao bundle.']],
            'https://example.com/faq'
        );

        $data = json_decode($json, true);

        self::assertSame('FAQPage', $data['@type']);
        self::assertSame('https://example.com/faq', $data['url']);
        self::assertSame('What is this?', $data['mainEntity'][0]['name']);
        self::assertSame('Question', $data['mainEntity'][0]['@type']);
        self::assertSame('A Contao bundle.', $data['mainEntity'][0]['acceptedAnswer']['text']);
        self::assertSame('Answer', $data['mainEntity'][0]['acceptedAnswer']['@type']);
    }
}
