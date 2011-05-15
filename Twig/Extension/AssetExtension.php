<?php
namespace Odl\AssetBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\Container;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\AssetCollection;

class AssetExtension
	extends \Twig_Extension
{
	private $container;
	private $debug;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->debug = $container->get('kernel')->isDebug();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
        	'jumbo' => new \Twig_Filter_Method(
        		$this, 'getJumbo', array('is_safe' => array('html'))),
        );
    }

    public function getJumbo($name)
    {
    	$managerName = 'asset.asset_manager';
    	$assetManager = $this->container->get($managerName);
    	if ($assetManager->has($name))
    	{
    		$asset = $assetManager->get($name);
    		return $this->getAssetHTML($asset);
    	}
    	else
    	{
    		throw new \Exception("Asset '{$name}' not found in {$managerName}");
    	}
    }

    protected function getAssetHTML(AssetInterface $asset)
    {
    	$retVal = '';
    	if ($this->debug && $asset instanceof AssetCollection)
    	{
			foreach ($asset->all() as $a)
			{
				$retVal .= $this->getAssetHTML($a);
			}
    	}
		else
		{
			$url = $asset->getTargetPath();
			if (isset($asset->is_css))
			{
				$retVal = "<link rel=\"stylesheet\" href=\"{$url}\" type=\"text/css\" media=\"all\" />\n";
			}
			else
			{
				$retVal = "<script type=\"text/javascript\" src=\"{$url}\"></script>\n";
			}
		}

		return $retVal;
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'asset';
    }
}
