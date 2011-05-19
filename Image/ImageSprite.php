<?php
namespace Odl\AssetBundle\Image;


use Odl\AssetBundle\Image\Pack\ImageRectangle;
use Symfony\Component\Finder\Finder;
use Imagick;
use Odl\AssetBundle\Image\Pack\Canvas;

class ImageSprite {
	protected $path;
	protected $finder;
	protected $images = array();
	protected $gutter;
	protected $sprite;

	public function __construct($path, $gutter = 10) {
		$this->path = $path;
		$this->gutter = $gutter;

		// Set up finder based on path
		$finder = new Finder();
		$finder->files()->name('*.png')->in($path);

		$this->finder = $finder;
	}

	public function getImages() {
		if (!$this->images) {
			foreach ($this->finder as $file) {
				$realPath = $file->getRealPath();
				$image = new Imagick();
				$image->readImage($realPath);

				$this->images[$realPath] = new ImageRectangle($image, $this->gutter);
			}
		}

		return $this->images;
	}

	public function getSprite($width = 480, $height = 400) {
		$canvas = new Canvas($width, $height);
		$sprite = new Imagick();
		$sprite->newImage($width, $height, "none");
		$sprite->setImageFormat('png');

		foreach ($this->getImages() as $path => $image) {
			$image = $canvas->insert($image);

			if (!$image) {
				throw new \Exception("Canvas can't fit {$path}");
			}
			else {
				$sprite->compositeImage(
					$image->getImage(),
					Imagick::COMPOSITE_DEFAULT,
					$image->x,
					$image->y);
			}
		}

		return $sprite;
	}

	public function getLessCSS() {

	}
}
