<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Tests\Review;

use Kikmedia\GroundingPageBundle\Review\Fingerprint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FingerprintTest extends TestCase
{
    public function testHasAVersionedFormat(): void
    {
        self::assertMatchesRegularExpression('~^1:[0-9a-f]{64}$~', Fingerprint::of($this->entry(), []));
    }

    #[DataProvider('reviewedEntryFields')]
    public function testChangesWithReviewedEntryFields(string $field, mixed $value): void
    {
        self::assertNotSame(Fingerprint::of($this->entry(), []), Fingerprint::of($this->entry([$field => $value]), []));
    }

    public static function reviewedEntryFields(): iterable
    {
        yield ['title', 'Anders'];
        yield ['entityType', 'Product'];
        yield ['entityTypeCustom', 'Brand'];
        yield ['definition', 'Neu.'];
        yield ['placement', 'Neu.'];
        yield ['distinction', 'Neu.'];
        yield ['coreFacts', serialize([['value' => '2015', 'label' => 'Gegründet']])];
        yield ['sources', serialize([['value' => 'https://example.com/neu', 'label' => 'Bericht']])];
        yield ['relatedPages', serialize(['2', '4'])];
        yield ['addImage', '1'];
        yield ['singleSRC', str_repeat("\x01\xff", 8)];
    }

    public function testIgnoresPublicationAndTechnicalEntryFields(): void
    {
        $changed = $this->entry(['id' => 99, 'alias' => 'x', 'author' => 7, 'published' => 0, 'start' => '1', 'stop' => '2', 'tstamp' => 5, 'sorting' => 9, 'size' => serialize(['1', '', '']), 'claimConformity' => '1', 'lastReviewed' => '1']);

        self::assertSame(Fingerprint::of($this->entry(), []), Fingerprint::of($changed, []));
    }

    public function testRelatedEntriesCountRegardlessOfOrder(): void
    {
        self::assertSame(
            Fingerprint::of($this->entry(['relatedPages' => serialize(['2', '3'])]), []),
            Fingerprint::of($this->entry(['relatedPages' => serialize(['3', '2'])]), [])
        );
    }

    public function testFactOrderCounts(): void
    {
        $facts = [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B']];

        self::assertNotSame(
            Fingerprint::of($this->entry(['coreFacts' => serialize($facts)]), []),
            Fingerprint::of($this->entry(['coreFacts' => serialize(array_reverse($facts))]), [])
        );
    }

    public function testZeroAsAFactValueIsContent(): void
    {
        self::assertNotSame(
            Fingerprint::of($this->entry(['coreFacts' => serialize([['value' => '', 'label' => 'Mängel']])]), []),
            Fingerprint::of($this->entry(['coreFacts' => serialize([['value' => '0', 'label' => 'Mängel']])]), [])
        );
    }

    public function testEmptyValuesAndEmptyRowsDoNotCount(): void
    {
        $empty = $this->entry(['placement' => null, 'coreFacts' => null]);
        $blank = $this->entry(['placement' => '', 'coreFacts' => serialize([['value' => '', 'label' => '']])]);

        self::assertSame(Fingerprint::of($empty, []), Fingerprint::of($blank, []));
    }

    public function testContaoFiveAndSixStorageGiveTheSameResult(): void
    {
        $five = Fingerprint::of(
            $this->entry(['title' => 'M&#38;M', 'definition' => '&#60;b&#62; &#123;&#123;x&#125;&#125;']),
            [$this->element(['text' => '<p>A &#40;B&#41;</p>', 'playerSize' => serialize(['1', '2']), 'newColumn' => ''])]
        );
        $six = Fingerprint::of(
            $this->entry(['title' => 'M&M', 'definition' => '<b> [{]x[}]']),
            [$this->element(['text' => '<p>A (B)</p>', 'jsonData' => json_encode(['playerSize' => serialize(['1', '2'])]), 'otherColumn' => 0])]
        );

        self::assertSame($five, $six);
    }

    public function testValuesMovedIntoJsonDataStillCount(): void
    {
        self::assertSame(
            Fingerprint::of($this->entry(), [$this->element(['playerCaption' => 'Film'])]),
            Fingerprint::of($this->entry(), [$this->element(['jsonData' => '{"playerCaption":"Film"}'])])
        );
    }

    public function testBinaryFileReferencesInElements(): void
    {
        $one = $this->element(['multiSRC' => serialize([str_repeat("\x00\xfe", 8)])]);
        $two = $this->element(['multiSRC' => serialize([str_repeat("\x00\xfd", 8)])]);

        self::assertNotSame(Fingerprint::of($this->entry(), [$one]), Fingerprint::of($this->entry(), [$two]));
    }

    public function testElementContentNestedContentAndOrderCount(): void
    {
        $a = $this->element(['id' => 1, 'text' => 'a']);
        $b = $this->element(['id' => 2, 'text' => 'b']);
        $base = Fingerprint::of($this->entry(), [$a, $b]);

        self::assertNotSame($base, Fingerprint::of($this->entry(), [$b, $a]));
        self::assertNotSame($base, Fingerprint::of($this->entry(), [$a]));
        self::assertNotSame($base, Fingerprint::of($this->entry(), [$a, $this->element(['text' => 'c'])]));
        self::assertNotSame($base, Fingerprint::of($this->entry(), [$a, $b + ['children' => [$this->element(['text' => 'innen'])]]]));
        self::assertNotSame($base, Fingerprint::of($this->entry(), [$a, $this->element(['text' => 'b', 'foreignExtensionField' => 'x'])]));
    }

    public function testHiddenElementsAndTheirChildrenDoNotCount(): void
    {
        $hidden = $this->element(['invisible' => '1', 'children' => [$this->element(['text' => 'innen'])]]);

        self::assertSame(Fingerprint::of($this->entry(), []), Fingerprint::of($this->entry(), [$hidden]));
    }

    public function testTechnicalAndLayoutElementFieldsDoNotCount(): void
    {
        $changed = $this->element(['id' => 8, 'pid' => 9, 'sorting' => 7, 'tstamp' => 6, 'start' => '1', 'stop' => '2', 'cssID' => serialize(['a', 'b']), 'customTpl' => 'x', 'protected' => '1', 'groups' => serialize(['1']), 'size' => serialize(['1', '', '']), 'floating' => 'left', 'fullsize' => '1']);

        self::assertSame(Fingerprint::of($this->entry(), [$this->element()]), Fingerprint::of($this->entry(), [$changed]));
    }

    private function entry(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'VeloNova',
            'entityType' => 'Organization',
            'entityTypeCustom' => '',
            'definition' => 'Ein Hersteller.',
            'placement' => '',
            'distinction' => '',
            'coreFacts' => serialize([['value' => '2014', 'label' => 'Gegründet']]),
            'sources' => serialize([['value' => 'https://example.com', 'label' => 'Bericht']]),
            'relatedPages' => serialize(['2', '3']),
            'addImage' => '',
            'singleSRC' => null,
        ];
    }

    private function element(array $overrides = []): array
    {
        return $overrides + ['id' => 1, 'type' => 'text', 'invisible' => '', 'headline' => serialize(['unit' => 'h2', 'value' => '']), 'text' => '<p>Text</p>'];
    }
}
