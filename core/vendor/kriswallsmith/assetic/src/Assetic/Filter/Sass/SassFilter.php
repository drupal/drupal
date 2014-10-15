<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2013 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Filter\Sass;

use Assetic\Asset\AssetInterface;
use Assetic\Exception\FilterException;
use Assetic\Factory\AssetFactory;
use Assetic\Filter\BaseProcessFilter;
use Assetic\Filter\DependencyExtractorInterface;
use Assetic\Util\CssUtils;

/**
 * Loads SASS files.
 *
 * @link http://sass-lang.com/
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class SassFilter extends BaseProcessFilter implements DependencyExtractorInterface
{
    const STYLE_NESTED     = 'nested';
    const STYLE_EXPANDED   = 'expanded';
    const STYLE_COMPACT    = 'compact';
    const STYLE_COMPRESSED = 'compressed';

    private $sassPath;
    private $rubyPath;
    private $unixNewlines;
    private $scss;
    private $style;
    private $quiet;
    private $debugInfo;
    private $lineNumbers;
    private $loadPaths = array();
    private $cacheLocation;
    private $noCache;
    private $compass;

    public function __construct($sassPath = '/usr/bin/sass', $rubyPath = null)
    {
        $this->sassPath = $sassPath;
        $this->rubyPath = $rubyPath;
        $this->cacheLocation = realpath(sys_get_temp_dir());
    }

    public function setUnixNewlines($unixNewlines)
    {
        $this->unixNewlines = $unixNewlines;
    }

    public function setScss($scss)
    {
        $this->scss = $scss;
    }

    public function setStyle($style)
    {
        $this->style = $style;
    }

    public function setQuiet($quiet)
    {
        $this->quiet = $quiet;
    }

    public function setDebugInfo($debugInfo)
    {
        $this->debugInfo = $debugInfo;
    }

    public function setLineNumbers($lineNumbers)
    {
        $this->lineNumbers = $lineNumbers;
    }

    public function setLoadPaths(array $loadPaths)
    {
        $this->loadPaths = $loadPaths;
    }

    public function addLoadPath($loadPath)
    {
        $this->loadPaths[] = $loadPath;
    }

    public function setCacheLocation($cacheLocation)
    {
        $this->cacheLocation = $cacheLocation;
    }

    public function setNoCache($noCache)
    {
        $this->noCache = $noCache;
    }

    public function setCompass($compass)
    {
        $this->compass = $compass;
    }

    public function filterLoad(AssetInterface $asset)
    {
        $sassProcessArgs = array($this->sassPath);
        if (null !== $this->rubyPath) {
            $sassProcessArgs = array_merge(explode(' ', $this->rubyPath), $sassProcessArgs);
        }

        $pb = $this->createProcessBuilder($sassProcessArgs);

        $root = $asset->getSourceRoot();
        $path = $asset->getSourcePath();

        if ($root && $path) {
            $pb->add('--load-path')->add(dirname($root.'/'.$path));
        }

        if ($this->unixNewlines) {
            $pb->add('--unix-newlines');
        }

        if (true === $this->scss || (null === $this->scss && 'scss' == pathinfo($path, PATHINFO_EXTENSION))) {
            $pb->add('--scss');
        }

        if ($this->style) {
            $pb->add('--style')->add($this->style);
        }

        if ($this->quiet) {
            $pb->add('--quiet');
        }

        if ($this->debugInfo) {
            $pb->add('--debug-info');
        }

        if ($this->lineNumbers) {
            $pb->add('--line-numbers');
        }

        foreach ($this->loadPaths as $loadPath) {
            $pb->add('--load-path')->add($loadPath);
        }

        if ($this->cacheLocation) {
            $pb->add('--cache-location')->add($this->cacheLocation);
        }

        if ($this->noCache) {
            $pb->add('--no-cache');
        }

        if ($this->compass) {
            $pb->add('--compass');
        }

        // input
        $pb->add($input = tempnam(sys_get_temp_dir(), 'assetic_sass'));
        file_put_contents($input, $asset->getContent());

        $proc = $pb->getProcess();
        $code = $proc->run();
        unlink($input);

        if (0 !== $code) {
            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }

        $asset->setContent($proc->getOutput());
    }

    public function filterDump(AssetInterface $asset)
    {
    }

    public function getChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        $loadPaths = $this->loadPaths;
        if ($loadPath) {
            array_unshift($loadPaths, $loadPath);
        }

        if (!$loadPaths) {
            return array();
        }

        $children = array();
        foreach (CssUtils::extractImports($content) as $reference) {
            if ('.css' === substr($reference, -4)) {
                // skip normal css imports
                // todo: skip imports with media queries
                continue;
            }

            // the reference may or may not have an extension or be a partial
            if (pathinfo($reference, PATHINFO_EXTENSION)) {
                $needles = array(
                    $reference,
                    self::partialize($reference),
                );
            } else {
                $needles = array(
                    $reference.'.scss',
                    $reference.'.sass',
                    self::partialize($reference).'.scss',
                    self::partialize($reference).'.sass',
                );
            }

            foreach ($loadPaths as $loadPath) {
                foreach ($needles as $needle) {
                    if (file_exists($file = $loadPath.'/'.$needle)) {
                        $coll = $factory->createAsset($file, array(), array('root' => $loadPath));
                        foreach ($coll as $leaf) {
                            $leaf->ensureFilter($this);
                            $children[] = $leaf;
                            goto next_reference;
                        }
                    }
                }
            }

            next_reference:
        }

        return $children;
    }

    private static function partialize($reference)
    {
        $parts = pathinfo($reference);

        if ('.' === $parts['dirname']) {
            $partial = '_'.$parts['filename'];
        } else {
            $partial = $parts['dirname'].DIRECTORY_SEPARATOR.'_'.$parts['filename'];
        }

        if (isset($parts['extension'])) {
            $partial .= '.'.$parts['extension'];
        }

        return $partial;
    }
}
