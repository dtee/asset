<?php
namespace Odl\AssetBundle\Controller;


use Odl\AssetBundle\Image\ImageSprite;
use Odl\AssetBundle\Image\Pack\Rectangle;
use Odl\AssetBundle\Image\Pack\Canvas;

use Assetic\Asset\AssetCache;
use Assetic\Asset\AssetInterface;
use Assetic\Cache\FilesystemCache;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AssetController
	extends Controller
{
	/**
	 * @extra:Route("/info")
	 * @Template
	 */
	public function info() {
		return new Response(phpInfo());
	}

	/**
	 * @extra:Route("/test")
	 * @Template
	 */
	public function test() {
		$manager = $this->container->get('asset.asset_manager');
		$sprite = $manager->getSprite('places');

		$image = $sprite->getSprite();

		$response = new Response();
		$response->headers->set('Content-type', 'image/png');
        $response->setContent($image);
		return $response;
	}

	/**
	 * @extra:Route("/sprite/{name}")
	 */
	public function sprite($name) {
		$manager = $this->container->get('asset.asset_manager');
		$sprite = $manager->getSprite($name);
		$image = $sprite->getSprite();

		$response = new Response();
		$response->headers->set('Content-type', 'image/png');
        $response->setContent($image);
		return $response;
	}

	/**
	 * @extra:Route("/{name}",
	 *  requirements={"name" = ".*"}, defaults={"name" = "css_bundle"},
	 *  name="_odl_asset")
	 */
	public function assetAction($name)
	{
		$manger = $this->container->get('asset.asset_manager');
		$asset = $manger->get($name);

        if (!$asset) {
            throw new NotFoundHttpException(sprintf('The "%s" asset could not be found.', $name));
        }

		if ($asset)
		{
			$request = $this->get('request');
			$response = new Response();
			if (isset($asset->is_css))
			{
				$response->headers->set('Content-Type', 'text/css');
			}
			else
			{
				$response->headers->set('Content-Type', 'application/javascript');
			}

			$kernel = $this->get('kernel');
			$isDebug = $kernel->isDebug();

	        // last-modified
	        if (!$isDebug) {
		        if (null !== $lastModified = $asset->getLastModified()) {
		            $date = new \DateTime();
		            $date->setTimestamp($lastModified);
		            $response->setLastModified($date);
		        }

		        // Run though yui when debug is not enabled!
		        if ($response->isNotModified($request)) {
		            return $response;
		        }

		        $cache = null;
		        $isCompress = !$isDebug || $request->get('nocompress');
				$isCompress = true;

		        if ($isCompress && $filter = $this->getYuiFilter($asset))
		        {
		        	$asset->ensureFilter($filter);

		        	// We should cache the result
		        	$cacheDir = $kernel->getCacheDir() . '/asset';
		        	$cache = new FilesystemCache($cacheDir);
		        }

	    		if ($cache)
	    		{
	    			$asset = $this->cachifyAsset($asset, $cache);
	    		}
	        }

	        $response->setContent($asset->dump());
			return $response;
		}
	}

	protected function getYuiFilter($asset)
	{
		$kernel = $this->get('kernel');
		$javaPath = '/usr/bin/java';
		$resourcePath = '@OdlAssetBundle/external/yuicompressor-2.4.6.jar';
		$jarPath = $kernel->locateResource($resourcePath);

		if (!file_exists($javaPath))
			return null;

		if (isset($asset->is_css))
		{
			$yuiFilter = new \Assetic\Filter\Yui\CssCompressorFilter($jarPath, $javaPath);
		}
		else
		{
			$yuiFilter = new \Assetic\Filter\Yui\JsCompressorFilter($jarPath, $javaPath);
		}

		return $yuiFilter;
	}

    protected function cachifyAsset(AssetInterface $asset, $cache)
    {
        return new AssetCache($asset, $cache);
    }
}