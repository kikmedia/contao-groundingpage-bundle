<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Tests\Check;

use Kikmedia\GroundingPageBundle\Check\CheckContext;
use Kikmedia\GroundingPageBundle\Check\CheckLevel;
use Kikmedia\GroundingPageBundle\Check\CheckResult;
use Kikmedia\GroundingPageBundle\Check\ConformityChecks;
use Kikmedia\GroundingPageBundle\Review\PublicReview;
use Kikmedia\GroundingPageBundle\Review\ReviewState;
use Kikmedia\GroundingPageBundle\Review\ReviewStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConformityChecksTest extends TestCase
{
    private const NOW = '2026-11-27 12:00';

    public function testACompletePagePasses(): void
    {
        $results = ConformityChecks::run($this->context());

        self::assertTrue(ConformityChecks::requiredPassed($results));
        self::assertSame([], array_values(array_filter($results, static fn (CheckResult $r): bool => !$r->passed)));
    }

    public function testReportsEveryRequirementOnce(): void
    {
        $results = ConformityChecks::run($this->context());
        $messages = array_map(static fn (CheckResult $r): string => $r->message, $results);

        self::assertSame(['p2', 'p3', 'p4', 'p5', 'p7', 'p8', 'p6_alt', 'p11', 'p12', 'p15', 'p16', 'p6', 'p9', 'p10'], $messages);
    }

    #[DataProvider('failures')]
    public function testFails(array $overrides, array $failed, bool $requiredPassed): void
    {
        $results = ConformityChecks::run($this->context($overrides));
        $open = array_values(array_map(
            static fn (CheckResult $r): string => $r->requirement,
            array_filter($results, static fn (CheckResult $r): bool => !$r->passed)
        ));

        self::assertSame($failed, $open);
        self::assertSame($requiredPassed, ConformityChecks::requiredPassed($results));
    }

    public static function failures(): iterable
    {
        $h2 = static fn (string $value): array => ['type' => 'headline', 'invisible' => '', 'headline' => serialize(['unit' => 'h2', 'value' => $value])];
        $text = static fn (string $value): array => ['type' => 'text', 'invisible' => '', 'text' => $value];

        yield 'blank definition' => [['entry' => ['definition' => '  ']], ['P-2'], false];
        yield 'headline without name' => [['elements' => [$h2('Fragen')]], ['P-3'], false];
        yield 'h2 in text without name' => [['elements' => [$text('<h2 class="x">Preise</h2>')]], ['P-3'], false];
        yield 'h2 in raw html without name' => [['elements' => [['type' => 'html', 'invisible' => '', 'html' => '<H2>Preise</H2>']]], ['P-3'], false];
        yield 'nested h2 without name' => [['elements' => [['type' => 'element_group', 'invisible' => '', 'children' => [$h2('Preise')]]]], ['P-3'], false];
        yield 'no facts' => [['entry' => ['coreFacts' => null]], ['P-4'], false];
        yield 'fact without label' => [['entry' => ['coreFacts' => serialize([['value' => '2014', 'label' => ' ']])]], ['P-4'], false];
        yield 'fact without value' => [['entry' => ['coreFacts' => serialize([['value' => '', 'label' => 'Gegründet']])]], ['P-4'], false];
        yield 'type thing' => [['entry' => ['entityType' => 'Thing']], ['P-5'], false];
        yield 'custom type webpage' => [['entry' => ['entityType' => 'other', 'entityTypeCustom' => 'webpage']], ['P-5'], false];
        yield 'custom type missing' => [['entry' => ['entityType' => 'other', 'entityTypeCustom' => '']], ['P-5'], false];
        yield 'unpublished entry' => [['entry' => ['published' => '0']], ['P-7'], false];
        yield 'not yet started' => [['entry' => ['start' => (string) strtotime('2026-11-28')]], ['P-7'], false];
        yield 'already stopped' => [['entry' => ['stop' => (string) strtotime('2026-11-26')]], ['P-7'], false];
        yield 'no page' => [['page' => null], ['P-7', 'P-15'], false];
        yield 'unpublished page' => [['page' => ['published' => false]], ['P-7'], false];
        yield 'protected page' => [['page' => ['protected' => true]], ['P-7'], false];
        yield 'noindex page' => [['page' => ['noindex' => true]], ['P-7'], false];
        yield 'stale review' => [['status' => ReviewStatus::Stale], ['P-8'], false];
        yield 'rejected review' => [['status' => ReviewStatus::Rejected, 'date' => null], ['P-8'], false];
        yield 'missing review' => [['status' => ReviewStatus::Missing, 'date' => null], ['P-8'], false];
        yield 'missing alt text' => [['imageAltMissing' => true], ['P-6'], true];
        yield 'no distinction' => [['entry' => ['distinction' => '']], ['P-11'], true];
        yield 'no source' => [['entry' => ['sources' => null]], ['P-12'], true];
        yield 'source without scheme' => [['entry' => ['sources' => serialize([['value' => 'www.example.com', 'label' => 'Bericht']])]], ['P-12'], true];
        yield 'source without address' => [['entry' => ['sources' => serialize([['value' => 'https://example.com', 'label' => 'A'], ['value' => '', 'label' => 'B']])]], ['P-12'], true];
        yield 'archive in no list' => [['archiveListed' => false], ['P-15'], true];
        yield 'review older than six months' => [['date' => '2026-05-01'], ['P-16'], true];
    }

    #[DataProvider('passes')]
    public function testPasses(array $overrides): void
    {
        $results = ConformityChecks::run($this->context($overrides));

        self::assertSame([], array_values(array_filter($results, static fn (CheckResult $r): bool => !$r->passed)));
    }

    public static function passes(): iterable
    {
        $text = static fn (string $value, string $invisible = ''): array => ['type' => 'text', 'invisible' => $invisible, 'text' => $value];

        yield 'name in capitals' => [['elements' => [$text('<h2>ÜBER VELONOVA</h2>')]]];
        yield 'name with markup and spaces' => [['entry' => ['title' => 'Velo Nova'], 'elements' => [$text('<h2>Über <em>Velo</em>&nbsp; Nova</h2>')]]];
        yield 'hidden element' => [['elements' => [$text('<h2>Preise</h2>', '1')]]];
        yield 'third level heading' => [['elements' => [$text('<h3>Preise</h3>')]]];
        yield 'empty heading' => [['elements' => [$text('<h2>&nbsp;</h2>')]]];
        yield 'encoded title' => [['entry' => ['title' => 'M&#38;M'], 'elements' => [$text('<h2>Über M&amp;M</h2>')]]];
        yield 'no elements' => [['elements' => []]];
        yield 'zero as fact value' => [['entry' => ['coreFacts' => serialize([['value' => '0', 'label' => 'Mängel']])]]];
        yield 'review exactly six months old' => [['date' => '2026-05-27']];
        yield 'running period' => [['entry' => ['start' => (string) strtotime('2026-11-26'), 'stop' => (string) strtotime('2026-11-28')]]];
    }

    public function testMarksAReviewOlderThanAYear(): void
    {
        $messages = array_map(
            static fn (CheckResult $r): string => $r->message,
            ConformityChecks::run($this->context(['date' => '2025-11-01']))
        );

        self::assertContains('p16_late', $messages);
        self::assertNotContains('p16', $messages);
    }

    public function testNamesAnOldReviewDate(): void
    {
        $results = ConformityChecks::run($this->context(['entry' => ['lastReviewed' => (string) strtotime('2026-03-05')]]));
        $legacy = array_values(array_filter($results, static fn (CheckResult $r): bool => 'legacy_date' === $r->message));

        self::assertCount(1, $legacy);
        self::assertSame(CheckLevel::Info, $legacy[0]->level);
        self::assertSame(['2026-03-05'], $legacy[0]->parameters);
    }

    public function testStatementUrlFollowsTheLanguage(): void
    {
        self::assertSame('https://groundingpage.com/spec/v1.7/de/', ConformityChecks::statementUrl('de-CH'));
        self::assertSame('https://groundingpage.com/spec/v1.7/de/', ConformityChecks::statementUrl('de'));
        self::assertSame('https://groundingpage.com/spec/v1.7/', ConformityChecks::statementUrl('fr'));
    }

    private function context(array $overrides = []): CheckContext
    {
        $date = \array_key_exists('date', $overrides) ? $overrides['date'] : '2026-11-01';
        $page = ['published' => true, 'protected' => false, 'noindex' => false, 'language' => 'de'];

        if (\array_key_exists('page', $overrides)) {
            $page = null === $overrides['page'] ? null : $overrides['page'] + $page;
        }

        return new CheckContext(
            ($overrides['entry'] ?? []) + [
                'title' => 'VeloNova',
                'entityType' => 'Organization',
                'entityTypeCustom' => '',
                'definition' => 'Ein Hersteller.',
                'distinction' => 'Nicht zu verwechseln.',
                'coreFacts' => serialize([['value' => '2014', 'label' => 'Gegründet']]),
                'sources' => serialize([['value' => 'https://example.com', 'label' => 'Bericht']]),
                'published' => '1',
                'start' => '',
                'stop' => '',
                'lastReviewed' => '',
            ],
            $overrides['elements'] ?? [['type' => 'text', 'invisible' => '', 'text' => '<h2>Über VeloNova</h2>']],
            new ReviewState(
                $overrides['status'] ?? ReviewStatus::Current,
                null === $date ? null : new PublicReview($date, 'Person', 'Erika Muster'),
            ),
            $page,
            $overrides['imageAltMissing'] ?? false,
            $overrides['archiveListed'] ?? true,
            (int) strtotime(self::NOW),
        );
    }
}
