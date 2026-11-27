<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Tests\Util;

use Kikmedia\GroundingPageBundle\Model\GroundingPageModel;
use Kikmedia\GroundingPageBundle\Review\PublicReview;
use Kikmedia\GroundingPageBundle\Tests\ModelMockTrait;
use Kikmedia\GroundingPageBundle\Util\JsonLdBuilder;
use PHPUnit\Framework\TestCase;

class JsonLdBuilderTest extends TestCase
{
    use ModelMockTrait;

    public function testBuildsEntityJsonLd(): void
    {
        $data = json_decode(JsonLdBuilder::build($this->entry(), 'https://example.com/velonova'), true);

        self::assertSame('Organization', $data['@type']);
        self::assertSame('VeloNova', $data['name']);
        self::assertSame('A bicycle manufacturer.', $data['description']);
        self::assertSame('https://example.com/velonova', $data['url']);
    }

    public function testLeavesOutWhatSchemaOrgDoesNotAllowOnAnOrganization(): void
    {
        $data = json_decode(JsonLdBuilder::build($this->entry(), 'https://example.com/velonova'), true);

        self::assertSame(['@context', '@type', 'name', 'description', 'url'], array_keys($data));
    }

    public function testMirrorsFactsOnAProduct(): void
    {
        $data = json_decode(JsonLdBuilder::build($this->entry(['entityType' => 'Product']), 'https://example.com/velonova'), true);

        self::assertSame(
            [['@type' => 'PropertyValue', 'name' => 'Founded', 'value' => '2014']],
            $data['additionalProperty']
        );
    }

    public function testMirrorsFactsOnACustomPlaceType(): void
    {
        $entry = $this->entry(['entityType' => 'other', 'entityTypeCustom' => 'LocalBusiness']);

        $data = json_decode(JsonLdBuilder::build($entry, 'https://example.com/velonova'), true);

        self::assertArrayHasKey('additionalProperty', $data);
    }

    public function testDescribesThePageWithSourcesAndLastChange(): void
    {
        $zone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        try {
            $properties = JsonLdBuilder::pageProperties($this->entry(), 1793660400);
        } finally {
            date_default_timezone_set($zone);
        }

        self::assertSame('2026-11-03', $properties['dateModified']);
        self::assertSame(
            [['@type' => 'CreativeWork', 'name' => 'Annual report', 'url' => 'https://example.com/report.pdf']],
            $properties['citation']
        );
        self::assertArrayNotHasKey('lastReviewed', $properties);
    }

    public function testDescribesNothingForAPageWithoutSourcesAndDate(): void
    {
        self::assertSame([], JsonLdBuilder::pageProperties($this->entry(['sources' => null]), null));
    }

    public function testDescribesADocumentedReview(): void
    {
        self::assertSame(
            ['lastReviewed' => '2026-11-20', 'reviewedBy' => ['@type' => 'Organization', 'name' => 'Müller & Söhne']],
            JsonLdBuilder::reviewProperties(new PublicReview('2026-11-20', 'Organization', 'Müller & Söhne'))
        );
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
            'entityType' => 'Product',
            'entityTypeCustom' => '',
            'title' => 'M&#38;M',
            'definition' => '&#60;b&#62;',
            'lastReviewed' => '',
            'coreFacts' => serialize([['label' => 'Sitz &#40;HQ&#41;', 'value' => 'A &#61; B']]),
            'sources' => serialize([['label' => '&#34;Report&#34;', 'value' => 'https://example.com/?a&#61;1']]),
        ]);

        $data = json_decode(JsonLdBuilder::build($entry, 'https://example.com/a', true), true);
        $properties = JsonLdBuilder::pageProperties($entry, null, true);
        $faq = json_decode(JsonLdBuilder::buildFaqPage([['label' => '&#40;?&#41;', 'value' => '&#35;1']], 'https://example.com/a', true), true);

        self::assertSame('M&M', $data['name']);
        self::assertSame('<b>', $data['description']);
        self::assertSame('Sitz (HQ)', $data['additionalProperty'][0]['name']);
        self::assertSame('A = B', $data['additionalProperty'][0]['value']);
        self::assertSame('"Report"', $properties['citation'][0]['name']);
        self::assertSame('https://example.com/?a=1', $properties['citation'][0]['url']);
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

    private function entry(array $overrides = []): GroundingPageModel
    {
        return $this->mockModel(GroundingPageModel::class, $overrides + [
            'entityType' => 'Organization',
            'entityTypeCustom' => '',
            'title' => 'VeloNova',
            'definition' => 'A bicycle manufacturer.',
            'lastReviewed' => '2026-01-15',
            'coreFacts' => serialize([['label' => 'Founded', 'value' => '2014']]),
            'sources' => serialize([['label' => 'Annual report', 'value' => 'https://example.com/report.pdf']]),
        ]);
    }
}
