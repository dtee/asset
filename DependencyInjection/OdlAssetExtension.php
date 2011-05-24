<?php
namespace Odl\AssetBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;

class OdlAssetExtension
	extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('asset_services.xml');

        $assetYamlPath = $config['asset_resource'];
        $spriteYamlPath = $config['sprite_resource'];

        $container->setParameter('asset.asset_manager.assets', $assetYamlPath);
        $container->setParameter('asset.asset_manager.sprites', $spriteYamlPath);

        // Lets set up
    }

    public function getAlias()
    {
        return 'odl_asset';
    }
}
