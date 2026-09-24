<?php

declare(strict_types=1);

namespace Grounding\PageBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\Template;
use Grounding\PageBundle\Model\GroundingPageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Frontend-Modul "grounding_page_list": listet die veröffentlichten Einträge
 * eines oder mehrerer Grounding-Page-Archive auf.
 */
#[AsFrontendModule('grounding_page_list', category: 'grounding_page')]
class GroundingPageListController extends AbstractFrontendModuleController
{
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $archiveIds = StringUtil::deserialize($model->grounding_page_archives, true);

        $entries = GroundingPageModel::findPublishedByPids($archiveIds);
        $items = [];

        if (null !== $entries) {
            foreach ($entries as $entry) {
                $items[] = [
                    'title'      => $entry->title,
                    'entityType' => 'other' === $entry->entityType && $entry->entityTypeCustom
                        ? $entry->entityTypeCustom
                        : $entry->entityType,
                    'definition' => $entry->definition,
                    'href'       => $this->generateEntryUrl($entry),
                ];
            }
        }

        $template->items = $items;
        $template->empty = $GLOBALS['TL_LANG']['MSC']['grounding_page_empty'] ?? 'Keine Einträge vorhanden.';

        return $template->getResponse();
    }

    private function generateEntryUrl(GroundingPageModel $entry): string
    {
        $archive = $entry->getRelated('pid');
        $params  = '/' . ($entry->alias ?: $entry->id);

        if ($archive && $archive->jumpTo) {
            $page = \Contao\PageModel::findPublishedById($archive->jumpTo);

            if (null !== $page) {
                return $page->getFrontendUrl($params);
            }
        }

        return $params;
    }
}
