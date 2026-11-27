<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Tests\Util;

use Kikmedia\GroundingPageBundle\Model\GroundingPageModel;
use Kikmedia\GroundingPageBundle\Tests\ModelMockTrait;
use Kikmedia\GroundingPageBundle\Util\JsonLdBuilder;
use PHPUnit\Framework\TestCase;

class JsonLdBuilderTest extends TestCase
{
    use ModelMockTrait;

    public function testBuildsEntityJsonLd(): void
    {
        $entry = $this->mockModel(GroundingPageModel::class, [
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
        $entry = $this->mockModel(GroundingPageModel::class, [
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
        $entry = $this->mockModel(GroundingPageModel::class, [
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

    public function testContentCannotCloseTheScriptElement(): void
    {
        $entry = $this->mockModel(GroundingPageModel::class, [
            'entityType' => 'Organization',
            'entityTypeCustom' => '',
            'title' => 'A </script><script>alert(1)</script>',
            'definition' => '',
            'lastReviewed' => '',
            'coreFacts' => null,
            'sources' => null,
        ]);

        $entity = JsonLdBuilder::build($entry, 'https://example.com/a');
        $faq = JsonLdBuilder::buildFaqPage([['label' => '</script>', 'value' => '</script>']], 'https://example.com/a');

        self::assertStringNotContainsString('<', $entity);
        self::assertStringNotContainsString('<', $faq);
        self::assertSame('A </script><script>alert(1)</script>', json_decode($entity, true)['name']);
    }

    public function testWritesPlainTextWhenContaoStoredEntities(): void
    {
        $entry = $this->mockModel(GroundingPageModel::class, [
            'entityType' => 'Organization',
            'entityTypeCustom' => '',
            'title' => 'M&#38;M',
            'definition' => '&#60;b&#62;',
            'lastReviewed' => '',
            'coreFacts' => serialize([['label' => 'Sitz &#40;HQ&#41;', 'value' => 'A &#61; B']]),
            'sources' => serialize([['label' => '&#34;Report&#34;', 'value' => 'https://example.com/?a&#61;1']]),
        ]);

        $data = json_decode(JsonLdBuilder::build($entry, 'https://example.com/a', true), true);
        $faq = json_decode(JsonLdBuilder::buildFaqPage([['label' => '&#40;?&#41;', 'value' => '&#35;1']], 'https://example.com/a', true), true);

        self::assertSame('M&M', $data['name']);
        self::assertSame('<b>', $data['description']);
        self::assertSame('Sitz (HQ)', $data['additionalProperty'][0]['name']);
        self::assertSame('A = B', $data['additionalProperty'][0]['value']);
        self::assertSame('"Report"', $data['citation'][0]['name']);
        self::assertSame('https://example.com/?a=1', $data['citation'][0]['url']);
        self::assertSame('(?)', $faq['mainEntity'][0]['name']);
        self::assertSame('#1', $faq['mainEntity'][0]['acceptedAnswer']['text']);
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

    public function testDateModifiedFromTheTimestampThatContaoStores(): void
    {
        $zone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        try {
            $entry = $this->mockModel(GroundingPageModel::class, [
                'entityType' => 'Organization',
                'entityTypeCustom' => '',
                'title' => 'VeloNova',
                'definition' => '',
                'lastReviewed' => '1793660400',
                'coreFacts' => serialize([]),
                'sources' => serialize([]),
            ]);

            $data = json_decode(JsonLdBuilder::build($entry, 'https://example.com/velonova'), true);

            self::assertSame('2026-11-03', $data['dateModified']);
        } finally {
            date_default_timezone_set($zone);
        }
    }
}
