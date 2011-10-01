<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2011 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Odl\AssetBundle\Filter;

use Symfony\Component\HttpKernel\Kernel;
use Assetic\Filter\FilterInterface;
use Assetic\Asset\AssetInterface;

/**
 * Loads LESS files using the PHP implementation of less, lessphp.
 *
 * Less files are mostly compatible, but there are slight differences.
 *
 * To use this, you need to clone https://github.com/leafo/lessphp and make
 * sure to either include lessphp.inc.php or tell your autoloader that's where
 * lessc is located.
 *
 */
class LessphpOptionsFilter
    implements FilterInterface
{
    protected $options;
    protected $lc;

    protected $otherContent;
    protected $lastModTime;

    /**
     * Constructor.
     *
     * @param string $baseDir The base web directory
     */
    public function __construct(Kernel $kernel, array $options = array())
    {
        $this->options = $options;
        $this->lc = new \lessc();

        $otherContent = '';
        $lastModTime = 0;
        if (isset($options['files'])) {
            foreach ($options['files'] as $file) {
                $filename = $kernel->locateResource('@' . $file);

                if (!$filename || !file_exists($filename))
                {
                    if ($kernel->isDebug())
                        throw new FileNotFoundException($filename);

                    continue;
                }

                $lastModTime = max($lastModTime, filemtime($filename));
                $otherContent .= file_get_contents($filename) . "\n";
            }
        }

        $this->lastModTime = new \DateTime('@' . $lastModTime);
        $this->otherContent = $otherContent;
    }

    public function filterLoad(AssetInterface $asset)
    {
        if (isset($this->options['importDir']))
        {
            $lc->importDir = $this->options['importDir'];
        }

        if ($content = $asset->getContent())
        {
            $content = $this->otherContent . "\n" . $content;
            $content = $this->lc->parse($content);
            $asset->setContent($content);
        }
    }

    public function getLastModified() {
        return $this->lastModTime;
    }

    public function filterDump(AssetInterface $asset)
    {
    }

    static private function isAbsolutePath($path)
    {
        return '/' == $path[0] || '\\' == $path[0] || (3 < strlen($path) && ctype_alpha($path[0]) && $path[1] == ':' && ('\\' == $path[2] || '/' == $path[2]));
    }
}
