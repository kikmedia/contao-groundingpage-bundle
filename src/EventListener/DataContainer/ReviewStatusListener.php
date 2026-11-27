<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Contao\System;
use Kikmedia\GroundingPageBundle\Check\CheckContextFactory;
use Kikmedia\GroundingPageBundle\Check\CheckLevel;
use Kikmedia\GroundingPageBundle\Check\CheckResult;
use Kikmedia\GroundingPageBundle\Check\ConformityChecks;
use Kikmedia\GroundingPageBundle\Review\ReviewService;
use Twig\Environment;

class ReviewStatusListener
{
    public function __construct(
        private readonly ReviewService $reviews,
        private readonly CheckContextFactory $contexts,
        private readonly Environment $twig,
    ) {
    }

    #[AsCallback(table: 'tl_grounding_page', target: 'list.label.label')]
    public function label(array $row, string $label): string
    {
        System::loadLanguageFile('tl_grounding_page');
        $status = $this->reviews->state((int) $row['id'])->status->value;

        return sprintf(
            '%s <span class="label-info">%s</span>',
            $label,
            StringUtil::specialchars($GLOBALS['TL_LANG']['tl_grounding_page']['reviewStates'][$status])
        );
    }

    #[AsCallback(table: 'tl_grounding_page', target: 'fields.groundingPageChecks.input_field')]
    public function checks(DataContainer $dc): string
    {
        System::loadLanguageFile('tl_grounding_page');
        $labels = $GLOBALS['TL_LANG']['tl_grounding_page'];
        $results = ConformityChecks::run($this->contexts->create((int) $dc->id));
        $visible = array_filter($results, static fn (CheckResult $result): bool => CheckLevel::Hint !== $result->level || !$result->passed);

        $items = array_map(static function (CheckResult $result) use ($labels): array {
            $state = match ($result->level) {
                CheckLevel::Required => $result->passed ? 'passed' : 'open',
                CheckLevel::Hint => 'hint',
                CheckLevel::Info => 'info',
            };

            return [
                'state' => $state,
                'label' => $labels['checkStates'][$state],
                'text'  => vsprintf($labels['checks'][$result->message], $result->parameters),
            ];
        }, array_values($visible));

        return $this->twig->render('@Contao/backend/grounding_page_checks.html.twig', [
            'heading' => $labels['checkHeading'],
            'items'   => $items,
        ]);
    }
}
