<?php
namespace Odl\AssetBundle\Asset;

use Assetic\Filter\FilterInterface;
use Odl\AssetBundle\Image\ImageSprite;
use Assetic\Asset\BaseAsset;

class SpriteImageAsset
	extends BaseAsset
{
	protected $sprite;
	public function __construct(ImageSprite $sprite) {
		$this->sprite = $sprite;
		parent::__construct();
	}

	public function getContent() {
		return $this->sprite->getSprite();
	}

	public function getSpriteImage() {
	    return $this->sprite;
	}

    public function getLastModified()
    {
    	$maxTime = 0;
    	foreach ($this->sprite->getFiles() as $file) {
    		$maxTime = max($maxTime, filemtime($file));
    	}

        return $maxTime;
    }

    public function load(FilterInterface $additionalFilter = null)
    {
		// Do nothing
    }
}
