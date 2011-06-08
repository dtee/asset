<?php
namespace Odl\AssetBundle\Twig\Extension;

use Symfony\Component\Templating\Asset\PackageInterface;
use Odl\AssetBundle\Asset\YAMLAssetManager;

use Symfony\Component\DependencyInjection\Container;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\AssetCollection;

class AssetExtension
	extends \Twig_Extension
{
	private $assetManager;
	private $assetsHelper;
    private $debug;

    public function __construct(
        YAMLAssetManager $assetManager,
        PackageInterface $assetsHelper,
        $debug)
    {
        $this->assetsHelper = $assetsHelper;
        $this->assetManager = $assetManager;
        $this->debug = $debug;
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

    public function getJumbo($names)
    {
    	if (!is_array($names))
    	{
    		$names = array($names);
    	}

    	$retVal = '';
    	foreach ($names as $name)
    	{
	    	if ($this->assetManager->has($name))
	    	{
	    		$asset = $this->assetManager->get($name);
	    		$retVal .= $this->getAssetHTML($asset);
	    	}
	    	else
	    	{
	    		throw new \Exception("Asset '{$name}' not found in {$managerName}");
	    	}
    	}

    	return $retVal;
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
			$path = $asset->getTargetPath();
			$url = $this->assetsHelper->getUrl($path);
			if (isset($asset->type) && $asset->type == 'css')
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
