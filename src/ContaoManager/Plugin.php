<?php

declare(strict_types=1);

namespace Grounding\PageBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Grounding\PageBundle\GroundingPageBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(GroundingPageBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
