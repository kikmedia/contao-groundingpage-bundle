<?php

declare(strict_types=1);

namespace Grounding\PageBundle\Controller\FrontendModule;

use Contao\ContentElement as ContaoContentElement;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\Input;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\Template;
use Grounding\PageBundle\Model\GroundingPageModel;
use Grounding\PageBundle\Util\JsonLdBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PageNotFoundHttpException;

#[AsFrontendModule('grounding_page_reader', category: 'grounding_page')]
class GroundingPageReaderController extends AbstractFrontendModuleController
{
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $idOrAlias = Input::get('auto_item');

        if (null === $idOrAlias) {
            throw new PageNotFoundHttpException('Missing auto_item parameter.');
        }

        $entry = GroundingPageModel::findPublishedByIdOrAlias((string) $idOrAlias);

        if (null === $entry) {
            throw new PageNotFoundHttpException('Grounding page entry not found.');
        }

        $template->title = $entry->title;
        $template->entityType = 'other' === $entry->entityType && $entry->entityTypeCustom
            ? $entry->entityTypeCustom
            : $entry->entityType;

        $template->definition = $entry->definition;
        $template->placement = $entry->placement;
        $template->distinction = $entry->distinction;

        $template->coreFacts = array_filter((array) StringUtil::deserialize($entry->coreFacts, true));
        $template->sources = array_filter((array) StringUtil::deserialize($entry->sources, true));
        $template->relatedPages = $this->getRelatedPages($entry);

        $template->lastReviewed = $entry->lastReviewed;
        $template->content = $this->renderContentElements($entry->id);

        $template->jsonLd = JsonLdBuilder::build($entry, $request->getUri());

        if ($entry->addImage && $entry->singleSRC) {
            $file = \Contao\FilesModel::findByUuid($entry->singleSRC);

            if (null !== $file && is_file(\Contao\System::getContainer()->getParameter('kernel.project_dir') . '/' . $file->path)) {
                $template->addImage = true;
                $template->image = \Contao\Image::getHtml($file->path, '', 'loading="lazy"');
            }
        }

        return $template->getResponse();
    }

    private function renderContentElements(int $entryId): string
    {
        $elements = ContentModel::findPublishedByPidAndTable($entryId, 'tl_grounding_page');
        $buffer = '';

        if (null !== $elements) {
            foreach ($elements as $element) {
                $buffer .= (new ContaoContentElement($element))->generate();
            }
        }

        return $buffer;
    }

    private function getRelatedPages(GroundingPageModel $entry): array
    {
        $ids = StringUtil::deserialize($entry->relatedPages, true);

        if (empty($ids)) {
            return [];
        }

        $related = GroundingPageModel::findMultipleByIds($ids);

        if (null === $related) {
            return [];
        }

        $items = [];

        foreach ($related as $page) {
            $items[] = [
                'title' => $page->title,
                'href'  => '/' . ($page->alias ?: $page->id),
            ];
        }

        return $items;
    }
}
