<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\StringUtil;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement('grounding_page_faq', category: 'grounding_page')]
class GroundingPageFaqController extends AbstractContentElementController
{
    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        $template->items = self::extractItems($model->faqItems);

        return $template->getResponse();
    }

    public static function extractItems(mixed $rawFaqItems): array
    {
        $items = (array) StringUtil::deserialize($rawFaqItems, true);

        return array_values(array_filter(
            $items,
            static fn (mixed $item): bool => \is_array($item) && !empty($item['label']) && isset($item['value']) && '' !== $item['value']
        ));
    }
}
