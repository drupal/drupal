<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2013 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Factory\Worker;

use Assetic\Asset\AssetInterface;

/**
 * Adds cache busting code
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class CacheBustingWorker implements WorkerInterface
{
    const STRATEGY_CONTENT = 1;
    const STRATEGY_MODIFICATION = 2;

    private $strategy;

    public function __construct($strategy = self::STRATEGY_CONTENT)
    {
        $this->strategy = $strategy;
    }

    public function process(AssetInterface $asset)
    {
        $hash = hash_init('sha1');

        switch($this->strategy) {
            case self::STRATEGY_MODIFICATION:
                hash_update($hash, $asset->getLastModified());
                break;
            case self::STRATEGY_CONTENT:
                hash_update($hash, $asset->dump());
                break;
        }

        foreach ($asset as $i => $leaf) {
            if ($sourcePath = $leaf->getSourcePath()) {
                hash_update($hash, $sourcePath);
            } else {
                hash_update($hash, $i);
            }
        }

        $hash = substr(hash_final($hash), 0, 7);
        $url = $asset->getTargetPath();

        $oldExt = pathinfo($url, PATHINFO_EXTENSION);
        $newExt = '-'.$hash.'.'.$oldExt;

        if (!$oldExt || 0 < preg_match('/'.preg_quote($newExt, '/').'$/', $url)) {
            return;
        }

        $asset->setTargetPath(substr($url, 0, (strlen($oldExt) + 1) * -1).$newExt);
    }

    public function getStrategy()
    {
        return $this->strategy;
    }

    public function setStrategy($strategy)
    {
        $this->strategy = $strategy;

        return $this;
    }
}
