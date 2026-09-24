<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Controller\FrontendModule;

use Contao\ContentElement as ContaoContentElement;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Input;
use Contao\ModuleModel;
use Contao\StringUtil;
use Kikmedia\GroundingPageBundle\Controller\ContentElement\GroundingPageFaqController;
use Kikmedia\GroundingPageBundle\Model\GroundingPageModel;
use Kikmedia\GroundingPageBundle\Util\JsonLdBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PageNotFoundHttpException;

#[AsFrontendModule('grounding_page_reader', category: 'grounding_page')]
class GroundingPageReaderController extends AbstractFrontendModuleController
{
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
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

        $elements = ContentModel::findPublishedByPidAndTable($entry->id, 'tl_grounding_page');
        $template->content = $this->renderContentElements($elements);

        $template->jsonLd = JsonLdBuilder::build($entry, $request->getUri());
        $template->jsonLdFaq = JsonLdBuilder::buildFaqPage($this->collectFaqItems($elements), $request->getUri());

        if ($entry->addImage && $entry->singleSRC) {
            $file = \Contao\FilesModel::findByUuid($entry->singleSRC);

            if (null !== $file && is_file(\Contao\System::getContainer()->getParameter('kernel.project_dir') . '/' . $file->path)) {
                $template->addImage = true;
                $template->image = \Contao\Image::getHtml($file->path, '', 'loading="lazy"');
            }
        }

        return $template->getResponse();
    }

    private function renderContentElements(\Contao\Model\Collection|null $elements): string
    {
        $buffer = '';

        if (null !== $elements) {
            foreach ($elements as $element) {
                $buffer .= (new ContaoContentElement($element))->generate();
            }
        }

        return $buffer;
    }

    private function collectFaqItems(\Contao\Model\Collection|null $elements): array
    {
        if (null === $elements) {
            return [];
        }

        $items = [];

        foreach ($elements as $element) {
            if ('grounding_page_faq' === $element->type) {
                array_push($items, ...GroundingPageFaqController::extractItems($element->faqItems));
            }
        }

        return $items;
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
