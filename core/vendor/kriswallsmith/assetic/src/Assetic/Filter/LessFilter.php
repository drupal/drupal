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
 * Loads LESS files.
 *
 * @link http://lesscss.org/
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class LessFilter implements FilterInterface
{
    private $nodeBin;
    private $nodePaths;
    private $compress;

    /**
     * Constructor.
     *
     * @param string $nodeBin   The path to the node binary
     * @param array  $nodePaths An array of node paths
     */
    public function __construct($nodeBin = '/usr/bin/node', array $nodePaths = array())
    {
        $this->nodeBin = $nodeBin;
        $this->nodePaths = $nodePaths;
    }

    public function setCompress($compress)
    {
        $this->compress = $compress;
    }

    public function filterLoad(AssetInterface $asset)
    {
        static $format = <<<'EOF'
var less = require('less');
var sys  = require(process.binding('natives').util ? 'util' : 'sys');

new(less.Parser)(%s).parse(%s, function(e, tree) {
    if (e) {
        less.writeError(e);
        process.exit(2);
    }

    try {
        sys.print(tree.toCSS(%s));
    } catch (e) {
        less.writeError(e);
        process.exit(3);
    }
});

EOF;

        $root = $asset->getSourceRoot();
        $path = $asset->getSourcePath();

        // parser options
        $parserOptions = array();
        if ($root && $path) {
            $parserOptions['paths'] = array(dirname($root.'/'.$path));
            $parserOptions['filename'] = basename($path);
        }

        // tree options
        $treeOptions = array();
        if (null !== $this->compress) {
            $treeOptions['compress'] = $this->compress;
        }

        $pb = new ProcessBuilder();
        $pb->inheritEnvironmentVariables();

        // node.js configuration
        if (0 < count($this->nodePaths)) {
            $pb->setEnv('NODE_PATH', implode(':', $this->nodePaths));
        }

        $pb->add($this->nodeBin)->add($input = tempnam(sys_get_temp_dir(), 'assetic_less'));
        file_put_contents($input, sprintf($format,
            json_encode($parserOptions),
            json_encode($asset->getContent()),
            json_encode($treeOptions)
        ));

        $proc = $pb->getProcess();
        $code = $proc->run();
        unlink($input);

        if (0 < $code) {
            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }

        $asset->setContent($proc->getOutput());
    }

    public function filterDump(AssetInterface $asset)
    {
    }
}
