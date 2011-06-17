<?php
namespace Odl\AssetBundle\Asset;

use Assetic\Filter\FilterInterface;
use Assetic\Asset\BaseAsset;

class RouteAsset
	extends BaseAsset
{
	public function __construct($routeName, array $params = array()) {
		$this->routeName = $routeName;
		$this->params = $params;
	}

	public function load(FilterInterface $additionalFilter = null)
    {

    }

	public function getLastModified() {
		//
	}
}