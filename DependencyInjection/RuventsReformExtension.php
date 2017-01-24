<?php

namespace Ruvents\ReformBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class RuventsReformExtension extends ConfigurableExtension
{
    /**
     * {@inheritdoc}
     */
    public function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        if ($mergedConfig['upload']['enabled']) {
            $loader->load('upload.yml');

            $container->findDefinition('ruvents_reform.upload_type')
                ->replaceArgument(0, $mergedConfig['upload']['default_tmp_dir']);
        }
    }
}
