<?php
namespace Odl\AssetBundle\Image\Pack;
use Imagick;

class ImageRectangle
	extends Rectangle
{
	protected $image;

	public function __construct(Imagick $image, $gutter = 5) {
		$this->image = $image;

		$width = $image->getImageWidth() + $gutter;
		$height = $image->getImageHeight() + $gutter;

		parent::__construct($width, $height);
	}

	public function getImage() {
		return $this->image;
	}
}