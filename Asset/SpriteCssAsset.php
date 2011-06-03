<?php
namespace Odl\AssetBundle\Asset;

use Assetic\Filter\FilterInterface;
use Odl\AssetBundle\Image\ImageSprite;
use Assetic\Asset\BaseAsset;

class SpriteCssAsset
	extends BaseAsset
{
	protected $sprite;
	public function __construct(SpriteImageAsset $spriteImageAsset) {
		$this->sprite = $spriteImageAsset->getSpriteImage();
		if (!$spriteImageUrl = $spriteImageAsset->getTargetPath()) {
            throw new \Exception("TargetPath must be defined.");
		}

		$this->setTargetPath($spriteImageUrl);
		parent::__construct();
	}

	public function getContent() {
		$url = $this->getTargetPath();
		$this->sprite->getSprite();
		$images = $this->sprite->getImages();

		$css = '';
		foreach ($images as $path => $image) {
			$css .= ".{$image->getKey()} {
				display: inline-block;
				width: {$image->getImageWidth()}px;
				height: {$image->getImageHeight()}px;
				background: transparent url($url) -{$image->x}px -{$image->y}px no-repeat;
			}\n";
		}

		return $css;
	}

	public function getLessCSS() {
		$url = $this->getTargetPath();
		$this->sprite->getSprite();
		$images = $this->sprite->getImages();

		$less = '';
		foreach ($images as $path => $image) {
			$less .= "@{$image->getKey()}() {
				display: inline-block;
				width: {$image->getImageWidth()}px;
				height: {$image->getImageHeight()}px;
				background: transparent url($url) -{$image->x}px -{$image->y}px no-repeat;
			}\n";
		}

		return $less;
	}

	public function getSprite() {
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
		// can't take additional filter: problem with clone method
    }
}
