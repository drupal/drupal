<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Exception\FilterException;
use Symfony\Component\Process\ProcessBuilder;

/**
 * UglifyJs filter.
 *
 * @link https://github.com/mishoo/UglifyJS
 * @author André Roaldseth <andre@roaldseth.net>
 */
class UglifyJsFilter implements FilterInterface
{
    private $uglifyJsPath;
    private $nodeJsPath;

    private $noCopyright;
    private $beautify;
    private $unsafe;

    /**
     * @param string $uglifyJsPath Absolute path to the uglifyjs executable
     * @param string $nodeJsPath   Absolute path to the folder containg node.js executable
     */
    public function __construct($uglifyJsPath, $nodeJsPath = null)
    {
        $this->uglifyJsPath = $uglifyJsPath;
        $this->nodeJsPath = $nodeJsPath;
    }

    /**
     * Removes the first block of comments as well
     * @param bool $noCopyright True to enable
     */
    public function setNoCopyright($noCopyright)
    {
        $this->noCopyright = $noCopyright;
    }

    /**
     * Output indented code
     * @param bool $beautify True to enable
     */
    public function setBeautify($beautify)
    {
        $this->beautify = $beautify;
    }

    /**
     * Enable additional optimizations that are known to be unsafe in some situations.
     * @param bool $unsafe True to enable
     */
    public function setUnsafe($unsafe)
    {
        $this->unsafe = $unsafe;
    }

    /**
     * @see Assetic\Filter\FilterInterface::filterLoad()
     */
    public function filterLoad(AssetInterface $asset)
    {
    }

    /**
     * Run the asset through UglifyJs
     *
     * @see Assetic\Filter\FilterInterface::filterDump()
     */
    public function filterDump(AssetInterface $asset)
    {
        $executables = array();

        if ($this->nodeJsPath !== null) {
            $executables[] = $this->nodeJsPath;
        }

        $executables[] = $this->uglifyJsPath;

        $pb = new ProcessBuilder($executables);

        if ($this->noCopyright) {
            $pb->add('--no-copyright');
        }

        if ($this->beautify) {
            $pb->add('--beautify');
        }

        if ($this->unsafe) {
            $pb->add('--unsafe');
        }

        // input and output files
        $input = tempnam(sys_get_temp_dir(), 'input');
        $output = tempnam(sys_get_temp_dir(), 'output');

        file_put_contents($input, $asset->getContent());
        $pb->add('-o')->add($output)->add($input);

        $proc = $pb->getProcess();
        $code = $proc->run();
        unlink($input);

        if (0 < $code) {
            if (file_exists($output)) {
                unlink($output);
            }

            if (127 === $code) {
                throw new \RuntimeException('Path to node executable could not be resolved.');
            }

            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        } elseif (!file_exists($output)) {
            throw new \RuntimeException('Error creating output file.');
        }

        $uglifiedJs = file_get_contents($output);
        unlink($output);

        $asset->setContent($uglifiedJs);
    }
}
