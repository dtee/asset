<?php
namespace Odl\AssetBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
	 * @Route("/info")
	 * @Template()
	 */
	public function info() {
		return new Response(phpInfo());
	}

	/**
	 * @Route("/test")
	 * @Template()
	 */
	public function testAction() {
		$name = 'places';
		$manager = $this->container->get('asset.asset_manager');

		$imageName = $manager->getSpriteImageName($name);
		$cssName = $manager->getSpriteCssName($name);

		$spriteAsset = $manager->get($cssName);
		$sprite = $spriteAsset->getSprite();

		$sprite->getSprite();
		$images = $sprite->getImages();

		$router = $this->get('router');
		$imageUrl = $router->generate('_odl_asset', array('name' => $imageName));

		$hash = array();
		foreach ($images as $image) {
			$key = $image->getKey();
			$hash[$key] = $image->toArray();
		}

		return array(
			'imageUrl' => $imageUrl,
			'cssUrl' => $router->generate('_odl_asset', array('name' => $cssName)),
			'hash' => $hash,
			'sprite' => $sprite
		);
	}

	/**
	 * @Route("/{name}",
	 *  requirements={"name" = ".*"}, defaults={"name" = "css_bundle"},
	 *  name="_odl_asset")
	 */
	public function assetAction($name)
	{
		$manger = $this->container->get('asset.asset_manager');

        if (!$manger->has($name)) {
            throw new NotFoundHttpException(sprintf('The "%s" asset could not be found.', $name));
        }
        else {
        	// See if file exists in resource directory??
        }

		$asset = $manger->get($name);
        $typeHash = array(
        	'css' => 'text/css',
        	'image' => 'image/png',
        	'javascript' => 'application/javascript'
        );

		if ($asset)
		{
			$request = $this->get('request');
			$response = new Response();
			if (isset($typeHash[$asset->type]))
			{
				$response->headers->set('Content-Type', $typeHash[$asset->type]);
			}

			$kernel = $this->get('kernel');
			$isDebug = $kernel->isDebug();

	        // last-modified
	        if (!$isDebug) {
		        if (null !== $lastModified = $asset->getLastModified()) {
		            $date = new \DateTime();
		            $date->setTimestamp($lastModified);
		            $response->setLastModified($date);

		            $date = new \DateTime();
		            $year = $date->format("Y") + 3;
		            $date->setDate($year, 1, 1);
		            $response->setExpires($date);
		        }

		        // Run though yui when debug is not enabled!
		        if ($response->isNotModified($request)) {
		            return $response;
		        }

		        $cache = null;
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

		if (isset($asset->type) && $asset->type == 'css')
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