<?php
namespace Odl\AssetBundle\Controller;

use Assetic\Asset\AssetCache;
use Assetic\Asset\AssetInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AssetController
	extends Controller
{
    protected $cache;

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

	        if ($response->isNotModified($request)) {
	            return $response;
	        }

    		if ($this->cache)
    		{
    			$asset = $this->cachifyAsset($asset);
    		}

	        $response->setContent($asset->dump());
			return $response;
		}
	}

    protected function cachifyAsset(AssetInterface $asset)
    {
        return new AssetCache($asset, $this->cache);
    }
}