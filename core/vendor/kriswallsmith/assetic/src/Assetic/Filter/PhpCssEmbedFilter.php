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

use CssEmbed\CssEmbed;
use Assetic\Asset\AssetInterface;

/**
 * A filter that embed url directly into css
 *
 * @author Pierre Tachoire <pierre.tachoire@gmail.com>
 * @link https://github.com/krichprollsch/phpCssEmbed
 */
class PhpCssEmbedFilter implements FilterInterface
{
    private $presets = array();

    public function setPresets(array $presets)
    {
        $this->presets = $presets;
    }

    public function filterLoad(AssetInterface $asset)
    {
        $root = $asset->getSourceRoot();
        $path = $asset->getSourcePath();

        $pce = new CssEmbed();
        if ($root && $path) {
            $pce->setRootDir(dirname($root.'/'.$path));
        }

        $asset->setContent($pce->embedString($asset->getContent()));
    }

    public function filterDump(AssetInterface $asset)
    {
    }
}
