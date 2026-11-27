<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\EventListener;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\SitemapEvent;
use Kikmedia\GroundingPageBundle\Model\GroundingPageModel;
use Kikmedia\GroundingPageBundle\Routing\EntryUrl;
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
            $page = EntryUrl::readerPage($entry);

            if (null === $page) {
                continue;
            }

            $page->loadDetails();

            if (!\in_array((int) $page->rootId, $event->getRootPageIds(), true)) {
                continue;
            }

            $url = $page->getAbsoluteUrl(EntryUrl::parameters($entry));

            $event->addUrlToDefaultUrlSet($url);
        }
    }
}
