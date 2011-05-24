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

	protected $width;
	protected $height;

	public function __construct($path, $gutter = 10) {
		$this->path = $path;
		$this->gutter = $gutter;

		// Set up finder based on path
		$finder = new Finder();
		$finder->files()->name('*.png')->in($path);

		$this->finder = $finder;
	}

	public function getImages() {
		$totalWidth = 0;
		$totalHeight = 0;
		$maxWidth = 0;
		if (!$this->images) {
			foreach ($this->finder as $file) {
				$realPath = $file->getRealPath();
				$image = new Imagick();
				$image->readImage($realPath);

				$rectangle = new ImageRectangle($image, $this->gutter);
				$totalWidth += $rectangle->width;
				$totalHeight += $rectangle->height;
				$maxWidth = max($maxWidth, $rectangle->width);

				$this->images[$realPath] = $rectangle;
			}
		}

		$totalImages = count($this->images);
		$idealWidth = (int) (($totalWidth / $totalImages) * sqrt($totalImages));
		$this->width = max($idealWidth, $maxWidth);
		$this->height = $totalHeight;

		return $this->images;
	}

	public function getWidth() {
		return $this->width;
	}

	public function getHeight() {
		return $this->height;
	}

	public function getSprite($width = null, $height = null) {
		$images = $this->getImages();
		if (!$width) {
			$width = $this->getWidth();
		}

		if (!$height) {
			$height = $this->getHeight();
		}

		$canvas = new Canvas($width, $height);
		$sprite = new Imagick();
		$sprite->newImage($width, $height, "none");

		$maxY = 0;
		foreach ($images as $path => $image) {
			$image = $canvas->insert($image);

			$maxY = max(($image->y + $image->height), $maxY);
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

		$sprite->cropImage($width, $maxY, 0, 0);
		$sprite->setImageFormat('png');
		return $sprite;
	}

	/**
	 * Generate Less CSS based on the sprite location
	 */
	public function getLessCSS() {
		if (!$this->less) {
			throw new \Exception('call getSprite() first');
		}

		return $this->less;
	}
}
