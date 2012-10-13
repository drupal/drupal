<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic;

use Assetic\Util\PathUtils;

use Assetic\Asset\AssetInterface;

/**
 * Writes assets to the filesystem.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AssetWriter
{
    private $dir;
    private $varValues;

    /**
     * Constructor.
     *
     * @param string $dir The base web directory
     */
    public function __construct($dir, array $varValues = array())
    {
        foreach ($varValues as $var => $values) {
            foreach ($values as $value) {
                if (!is_string($value)) {
                    throw new \InvalidArgumentException(sprintf('All variable values must be strings, but got %s for variable "%s".', json_encode($value), $var));
                }
            }
        }

        $this->dir = $dir;
        $this->varValues = $varValues;
    }

    public function writeManagerAssets(AssetManager $am)
    {
        foreach ($am->getNames() as $name) {
            $this->writeAsset($am->get($name));
        }
    }

    public function writeAsset(AssetInterface $asset)
    {
        foreach ($this->getCombinations($asset->getVars()) as $combination) {
            $asset->setValues($combination);

            static::write($this->dir.'/'.PathUtils::resolvePath(
                $asset->getTargetPath(), $asset->getVars(), $asset->getValues()),
                $asset->dump());
        }
    }

    private function getCombinations(array $vars)
    {
        if (!$vars) {
            return array(array());
        }

        $combinations = array();
        $nbValues = array();
        foreach ($this->varValues as $var => $values) {
            if (!in_array($var, $vars, true)) {
                continue;
            }

            $nbValues[$var] = count($values);
        }

        for ($i=array_product($nbValues),$c=$i*2; $i<$c; $i++) {
            $k = $i;
            $combination = array();

            foreach ($vars as $var) {
                $combination[$var] = $this->varValues[$var][$k % $nbValues[$var]];
                $k = intval($k/$nbValues[$var]);
            }

            $combinations[] = $combination;
        }

        return $combinations;
    }

    protected static function write($path, $contents)
    {
        if (!is_dir($dir = dirname($path)) && false === @mkdir($dir, 0777, true)) {
            throw new \RuntimeException('Unable to create directory '.$dir);
        }

        if (false === @file_put_contents($path, $contents)) {
            throw new \RuntimeException('Unable to write file '.$path);
        }
    }
}
