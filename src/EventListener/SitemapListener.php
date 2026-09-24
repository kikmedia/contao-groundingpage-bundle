<?php

declare(strict_types=1);

namespace Grounding\PageBundle\EventListener;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\SitemapEvent;
use Contao\PageModel;
use Grounding\PageBundle\Model\GroundingPageArchiveModel;
use Grounding\PageBundle\Model\GroundingPageModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(ContaoCoreEvents::SITEMAP)]
class SitemapListener
{
    public function __invoke(SitemapEvent $event): void
    {
        $entries = GroundingPageModel::findAllPublished();

        if (null === $entries) {
            return;
        }

        foreach ($entries as $entry) {
            $archive = GroundingPageArchiveModel::findById($entry->pid);

            if (null === $archive || !$archive->jumpTo) {
                continue;
            }

            $page = PageModel::findPublishedById($archive->jumpTo);

            if (null === $page) {
                continue;
            }

            $url = $page->getAbsoluteUrl('/' . ($entry->alias ?: $entry->id));

            $event->addUrlToDefaultUrlSet($url);
        }
    }
}
