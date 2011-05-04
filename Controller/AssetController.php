<?php
namespace Odl\AssetBundle\Controller;

use Assetic\Asset\AssetCache;
use Assetic\Asset\AssetInterface;
use Assetic\Cache\FilesystemCache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AssetController
	extends Controller
{
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

	        // last-modified
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
			$kernel = $this->get('kernel');
	        $isCompress = !$kernel->isDebug() || $request->get('nocompress');
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