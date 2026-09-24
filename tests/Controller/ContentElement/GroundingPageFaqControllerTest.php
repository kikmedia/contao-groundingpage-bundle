<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Tests\Controller\ContentElement;

use Kikmedia\GroundingPageBundle\Controller\ContentElement\GroundingPageFaqController;
use PHPUnit\Framework\TestCase;

class GroundingPageFaqControllerTest extends TestCase
{
    public function testExtractsCompleteItemsOnly(): void
    {
        $raw = serialize([
            ['label' => 'What is this?', 'value' => 'A Contao bundle.'],
            ['label' => '', 'value' => 'Missing question, skipped.'],
            ['label' => 'Missing answer, skipped.', 'value' => ''],
            ['label' => 'Another question?', 'value' => 'Another answer.'],
        ]);

        $items = GroundingPageFaqController::extractItems($raw);

        self::assertSame(
            [
                ['label' => 'What is this?', 'value' => 'A Contao bundle.'],
                ['label' => 'Another question?', 'value' => 'Another answer.'],
            ],
            $items
        );
    }

    public function testReturnsEmptyArrayForNull(): void
    {
        self::assertSame([], GroundingPageFaqController::extractItems(null));
    }
}
