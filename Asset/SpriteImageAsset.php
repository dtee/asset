<?php
namespace Odl\AssetBundle\Asset;

use Assetic\Filter\FilterInterface;

class SpriteImageAsset
	extends SpriteCssAsset
{
	public function getContent() {
		return $this->sprite->getSprite();
	}

    public function load(FilterInterface $additionalFilter = null)
    {
		// Do nothing
    }
}
