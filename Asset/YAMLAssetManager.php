<?php
namespace Odl\AssetBundle\Asset;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

use Assetic\Filter\Yui\CssCompressorFilter;
use Assetic\Filter\LessphpFilter;
use Assetic\Asset\FileAsset;
use Assetic\Asset\AssetCollection;
use Assetic\AssetManager;
use Assetic\Asset\AssetInterface;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

class YAMLAssetManager
	extends AssetManager
{
	protected $kernel;
	protected $debug = true;

	public function __construct(
		Kernel $kernel,
		$filename,
		$router = null)
	{
		$this->kernel = $kernel;
		if (startsWith($filename, '@'))
		{
			$filename = $kernel->locateResource($yamlFilePath);
		}

		if ($filename && file_exists($filename))
		{
			$config = YAML::load($filename);
			$this->loadFromConfig($config);
		}
		else
		{
			throw new FileNotFoundException($filename);
		}
	}

    public function get($name)
    {
        return $this->assets[$name];
    }

    public function set($name, AssetInterface $asset)
    {
        $this->assets[$name] = $asset;
    }

    /**
     * Takes a resource setting and convert them into absolute paths
     *
     * @param array $paths
     */
    protected function getAbsolutePaths(array $paths)
    {
    	$retVal = array();
    	foreach ($paths as $path)
    	{
    		if (is_array($path) && isset($path['local']))
    		{
    			$path = $path['local'];
    		}

    		if (!$path)
    		{
    			throw new \Exception("path must not be null");
    		}

			$filename = $this->kernel->locateResource($path);
			if (!$filename || !file_exists($filename))
			{
				throw new FileNotFoundException($filename);
				continue;
			}

			$retVal[] = $filename;
    	}

    	return $retVal;
    }

    /**
     * Creates asset collection and asset given a package setting
     *
     * @param array $package
     */
    protected function loadPackage(array $package)
    {
		$lessImportPaths = isset($package['less_import_paths']) ? $package['less_import_paths'] : array();
		$lessImportPaths = $this->getAbsolutePaths($lessImportPaths);
		$lessFilter = new LessphpFilter(null, $lessImportPaths);

		$files = $this->getAbsolutePaths($package['resources']);
    	$assetCollection = new AssetCollection();
		$isCss = false;
		foreach ($files as $filename)
		{
			$asset = new FileAsset($filename);
			$isCss = endsWith($filename, '.css');
			// Create a new asset
			if (endsWith($filename, '.less'))
			{
				$isCss = true;
				$asset->ensureFilter($lessFilter);
			}

			if ($isCss)
			{
				$asset->is_css = true;
			}

			$assetCollection->add($asset);
			if ($this->debug)
			{
				$rootPath = $package['root_path'];
				$pathKey = str_replace($rootPath, '', $filename);
				$pathKey = trim($pathKey, '/');
			}
			else
			{
				$pathKey = md5($filename);
			}

			$asset->setTargetUrl('/asset/' . $pathKey);
			$this->set($pathKey, $asset);
		}

		if ($isCss)
		{
			$assetCollection->is_css = true;
		}

		$assetCollection->setTargetUrl('/asset/' . $package['name']);
		$this->set($package['name'], $assetCollection);
    }

    /**
     * Takes whole config and load individual packages
     *
     * @param unknown_type $config
     * @throws FileNotFoundException
     */
	protected function loadFromConfig(array $config)
	{
		foreach ($config as $key => $package)
		{
			$package['name'] = $key;
			$this->loadPackage($package);
		}
	}
}
