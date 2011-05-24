<?php
namespace Odl\AssetBundle\Asset;

use Odl\AssetBundle\Filter\LessphpOptionsFilter;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

use Assetic\Filter\Yui\CssCompressorFilter;
use Assetic\Filter\LessphpFilter;
use Assetic\Asset\FileAsset;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;

class YAMLAssetManager
{
    protected $assets = array();
	protected $kernel;
	protected $debug = true;
	protected $router;

	public function __construct(
		Kernel $kernel,
		Router $router,
		$assetYamlPath,
		$spriteYamlPath)
	{
		$this->kernel = $kernel;
		$this->router = $router;

		$yamlConfig = $this->getConfig($assetYamlPath);
		$spriteConfig = $this->getConfig($spriteYamlPath);

		ve($spriteConfig);
		$this->loadFromConfig($config);
	}

	protected function getConfig($filename) {
		if (startsWith($filename, '@'))
		{
			$filename = $kernel->locateResource($yamlFilePath);
		}

		if ($filename && file_exists($filename))
		{
			$config = YAML::load($filename);
			return $config;
		}
		else
		{
			throw new FileNotFoundException($filename);
		}
	}

    public function has($name)
    {
        return isset($this->assets[$name]);
    }

    public function get($name)
    {
        return $this->assets[$name];
    }

    public function set($name, AssetInterface $asset)
    {
        $this->assets[$name] = $asset;
    }

    public function getNames()
    {
        return array_keys($this->assets);
    }

    /**
     * Takes a resource setting and convert them into absolute paths
     *
     * @param array $paths
     */
    protected function getAbsolutePaths(array $paths, $isIncludeBundlePath)
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

			$name = substr($path, 1);
			list($bundleName, $path) = explode('/', $name, 2);
			$bundle = $this->kernel->getBundle($bundleName, true);
			$bundlePath = $bundle->getPath();

			if ($isIncludeBundlePath)
			{
				$retVal[] = array(
					'root' => str_replace($bundleName, '', $bundlePath),
					'full_path' => $filename
				);
			}
			else {
				$retVal[] = $filename;
			}
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
		$lessImportPaths = $this->getAbsolutePaths($lessImportPaths, false);

		$options = array('importDir' => $lessImportPaths);
		$lessFilter = new LessphpOptionsFilter(null, $options);

		$files = $this->getAbsolutePaths($package['resources'], true);
    	$assetCollection = new AssetCollection();
		$isCss = false;
		foreach ($files as $fileInfo)
		{
			$filename = $fileInfo['full_path'];
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
				$rootPath = $fileInfo['root'];
				$pathKey = str_replace($rootPath, '', $filename);
				$pathKey = trim($pathKey, '/');
			}
			else
			{
				$filename .= $asset->getLastModified();
				$pathKey = md5($filename);
			}

			$url = $this->router->generate('_odl_asset', array('name' => $pathKey));
			$asset->setTargetPath($url);
			$this->set($pathKey, $asset);
		}

		if ($isCss)
		{
			$assetCollection->is_css = true;
		}

		$pathKey = $package['name'];
		$url = $this->router->generate('_odl_asset', array('name' => $pathKey));
		$assetCollection->setTargetPath($url);

		$this->set($pathKey, $assetCollection);
    }

    /**
     * Takes whole config and load individual packages
     *
     * @param array $config
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
