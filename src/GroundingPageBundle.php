<?php

declare(strict_types=1);

namespace Grounding\PageBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Extending AbstractBundle (instead of the plain Symfony Bundle class) is
 * the structure recommended by the official Contao documentation
 * (docs.contao.org/5.x/dev/getting-started/extension/): it enforces the
 * standard bundle layout and lets us load config/services.yaml directly
 * via loadExtension(), without a separate DependencyInjection\*Extension
 * class.
 */
class GroundingPageBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void
    {
        $containerConfigurator->import('../config/services.yaml');
    }
}
