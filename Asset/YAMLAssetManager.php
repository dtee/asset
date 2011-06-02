<?php
namespace Odl\AssetBundle\Asset;

use Assetic\Asset\StringAsset;

use Assetic\Filter\CssRewriteFilter;

use Odl\AssetBundle\Filter\LessphpOptionsFilter;
use Odl\AssetBundle\Image\ImageSprite;

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
		$assetYamlPath)
	{
		$this->kernel = $kernel;
		$this->router = $router;

		$yamlConfig = $this->getConfig($assetYamlPath);
		$this->loadFromConfig($yamlConfig);
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
     * Sprite splits into two assets:
     * 	Image asset and css asset
     */
    protected function loadSprite($package) {
    	$name = $package['name'];
    	if (isset($this->sprites[$name])) {
			throw new Exception("Duplicate sprite resources {$name}");
    	}

    	$path = $package['resources'];
    	$gutter = isset($package['gutter']) ? $package['gutter'] : null;
    	$path = $this->getAbsolutePaths($path, false);
    	$imageSprite = new ImageSprite($path, $gutter);

    	$this->sprites[$name] = $name;

    	$cssAssetKey = $this->getSpriteCssName($name);
		$imageAssetKey = $this->getSpriteImageName($name);

    	// Css: Do we send it through filters?
		$url = $this->router->generate('_odl_asset', array('name' => $imageAssetKey));
		$asset = new SpriteCssAsset($imageSprite, $url);
		$asset->type = 'css';
		$this->set($cssAssetKey, $asset);

		// Image:
		$asset = new SpriteImageAsset($imageSprite, $url);
		$asset->type = 'image';
		$this->set($imageAssetKey, $asset);
    }

    public function getSprites() {
    	return $this->sprites;
    }

    public function getSpriteImageName($name) {
		return "sprite/image/{$name}";
    }

    public function getSpriteCssName($name) {
    	return "sprite/css/{$name}";
    }

    /**
     * Takes a resource setting and convert them into absolute paths
     *
     * @param array $paths
     */
    protected function getAbsolutePaths(array $paths, $isIncludeBundlePath)
    {
    	$retVal = array();
    	foreach ($paths as $pathInfo)
    	{
    		if (is_array($pathInfo) && isset($pathInfo['local'])) {
    			$path = $pathInfo['local'];
    		}
    		else {
    			$path = $pathInfo;
    		}

    		if (!$path)
    		{
    			throw new \Exception("path must not be null");
    		}

    		if (startsWith($path, '@'))
    		{
				$filename = $this->kernel->locateResource($path);
    		}
    		else
    		{
    			$filename = $path;
    		}

			if (!$filename || !file_exists($filename))
			{
			    if ($this->kernel->isDebug())
				    throw new FileNotFoundException($filename);

				continue;
			}

			if ($isIncludeBundlePath)
			{
				$name = substr($path, 1);
				list($bundleName, $path) = explode('/', $name, 2);
				$bundle = $this->kernel->getBundle($bundleName, true);
				$bundlePath = $bundle->getPath();

				$fileInfo = array(
					'root' => str_replace($bundleName, '', $bundlePath),
					'full_path' => $filename
				);

				if (is_array($pathInfo))
				{
					$fileInfo = array_merge($fileInfo, $pathInfo);
				}
			}
			else {
				$fileInfo = $filename;
			}

			$retVal[] = $fileInfo;
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
		$cssRewriteFilter = new CssRewriteFilter();

		$files = $this->getAbsolutePaths($package['resources'], true);
    	$assetCollection = new AssetCollection();
		$isCss = false;
		foreach ($files as $fileInfo)
		{
			$filename = $fileInfo['full_path'];
			$sourceRoot = (isset($fileInfo['source_root'])) ? $fileInfo['source_root'] : null;
			$sourcePath = (isset($fileInfo['source_path'])) ? $fileInfo['source_path'] : null;

			$asset = new FileAsset($filename, array(), $sourceRoot, $sourcePath);
			$asset->type = $package['type'];

			if (endsWith($filename, '.less')) {
				$asset->ensureFilter($lessFilter);
			}

			if ($asset->type == 'css') {
				$asset->ensureFilter($cssRewriteFilter);
			}

			$assetCollection->add($asset);
			if ($this->debug) {
				$rootPath = $fileInfo['root'];
				$pathKey = str_replace($rootPath, '', $filename);
				$pathKey = trim($pathKey, '/');
			}
			else {
				$filename .= $asset->getLastModified();
				$pathKey = md5($filename);
			}

			$url = $this->router->generate('_odl_asset', array(
				'name' => $pathKey,
				'v' => $asset->getLastModified()
			));
			$asset->setTargetPath($url);
			$this->set($pathKey, $asset);
		}

		$assetCollection->type = $package['type'];

		$pathKey = $package['name'];
		$url = $this->router->generate('_odl_asset', array(
			'name' => $pathKey,
			'v' => $assetCollection->getLastModified()
		));

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
			if ($package['type'] == 'sprite')
			{
				$this->loadSprite($package);
			}
			else
			{
				$this->loadPackage($package);
			}
		}
	}
}
