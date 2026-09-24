<?php

declare(strict_types=1);

namespace Grounding\PageBundle\EventListener;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\SitemapEvent;
use Contao\PageModel;
use Grounding\PageBundle\Model\GroundingPageArchiveModel;
use Grounding\PageBundle\Model\GroundingPageModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Adds all published Grounding-Page entries to sitemap.xml, so they are
 * discoverable exactly like regular pages (§11 "Internal linking" of the
 * Grounding Page Standard explicitly requires pages to be reachable
 * through the normal site, not only via a card overview).
 *
 * Simplification: this adds every published entry to every generated
 * sitemap, without checking which website root/domain it belongs to. On a
 * single-root/single-domain installation that is exactly right. On a
 * multi-root Contao installation, add a root-page check here (e.g. via
 * $archive->jumpTo's root page) before enabling this on production.
 */
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
