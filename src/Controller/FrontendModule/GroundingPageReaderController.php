<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Controller\FrontendModule;

use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Date;
use Contao\Input;
use Contao\ModuleModel;
use Contao\StringUtil;
use Kikmedia\GroundingPageBundle\Controller\ContentElement\GroundingPageFaqController;
use Kikmedia\GroundingPageBundle\Model\GroundingPageArchiveModel;
use Kikmedia\GroundingPageBundle\Model\GroundingPageModel;
use Kikmedia\GroundingPageBundle\Routing\EntryUrl;
use Kikmedia\GroundingPageBundle\Util\JsonLdBuilder;
use Kikmedia\GroundingPageBundle\Util\PlainText;
use Kikmedia\GroundingPageBundle\Util\ReviewDate;
use Spatie\SchemaOrg\WebPage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsFrontendModule('grounding_page_reader', category: 'grounding_page')]
class GroundingPageReaderController extends AbstractFrontendModuleController
{
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['contao.image.studio'] = Studio::class;
        $services['contao.routing.response_context_accessor'] = ResponseContextAccessor::class;

        return $services;
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $idOrAlias = Input::get('auto_item');

        if (null === $idOrAlias) {
            throw new NotFoundHttpException('Missing auto_item parameter.');
        }

        $archiveIds = StringUtil::deserialize($model->grounding_page_archives, true);
        $entry = GroundingPageModel::findPublishedByIdOrAlias((string) $idOrAlias, $archiveIds);

        if (null === $entry) {
            throw new NotFoundHttpException('Grounding page entry not found.');
        }

        $template->archiveClass = (string) GroundingPageArchiveModel::findById($entry->pid)?->cssClass;
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

        $reviewed = ReviewDate::timestamp($entry->lastReviewed);
        $template->lastReviewed = null === $reviewed ? '' : Date::parse(Config::get('dateFormat'), $reviewed);
        $template->lastReviewedIso = ReviewDate::iso($entry->lastReviewed) ?? '';

        $elements = ContentModel::findPublishedByPidAndTable($entry->id, 'tl_grounding_page');
        $template->content = $this->renderContentElements($elements);

        $modified = $this->lastModified($entry, $elements);
        $template->lastModified = $modified ? Date::parse(Config::get('dateFormat'), $modified) : '';
        $template->lastModifiedIso = $modified ? date('Y-m-d', $modified) : '';

        $encoded = PlainText::isInputEncoded();
        $this->describePage(JsonLdBuilder::pageProperties($entry, $modified, $encoded));
        $template->jsonLd = JsonLdBuilder::build($entry, $request->getUri(), $encoded);
        $template->jsonLdFaq = JsonLdBuilder::buildFaqPage($this->collectFaqItems($elements), $request->getUri(), $encoded);

        $template->figure = $entry->addImage && $entry->singleSRC
            ? $this->container->get('contao.image.studio')
                ->createFigureBuilder()
                ->fromUuid($entry->singleSRC)
                ->setSize(StringUtil::deserialize($entry->size) ?: null)
                ->buildIfResourceExists()
            : null;

        return $template->getResponse();
    }

    private function describePage(array $properties): void
    {
        $context = $this->container->get('contao.routing.response_context_accessor')->getResponseContext();

        if (!$context?->has(JsonLdManager::class)) {
            return;
        }

        $page = $context->get(JsonLdManager::class)->getGraphForSchema(JsonLdManager::SCHEMA_ORG)->getOrCreate(WebPage::class);

        foreach ($properties as $name => $value) {
            $page->setProperty($name, $value);
        }
    }

    private function lastModified(GroundingPageModel $entry, \Contao\Model\Collection|null $elements): int
    {
        $timestamps = [(int) $entry->tstamp];

        foreach ($elements ?? [] as $element) {
            $timestamps[] = (int) $element->tstamp;
        }

        return max($timestamps);
    }

    private function renderContentElements(\Contao\Model\Collection|null $elements): string
    {
        $buffer = '';

        if (null !== $elements) {
            foreach ($elements as $element) {
                $buffer .= Controller::getContentElement($element);
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
                array_push($items, ...GroundingPageFaqController::extractItems($element->groundingPageItems));
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

        $related = GroundingPageModel::findPublishedMultipleByIds($ids);

        if (null === $related) {
            return [];
        }

        $items = [];

        foreach ($related as $page) {
            $href = EntryUrl::path($page);

            if (null !== $href) {
                $items[] = [
                    'title' => $page->title,
                    'href'  => $href,
                ];
            }
        }

        return $items;
    }
}
