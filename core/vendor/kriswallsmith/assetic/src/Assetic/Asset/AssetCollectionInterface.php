<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Asset;

/**
 * An asset collection.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
interface AssetCollectionInterface extends AssetInterface, \Traversable
{
    /**
     * Returns all child assets.
     *
     * @return array An array of AssetInterface objects
     */
    public function all();

    /**
     * Adds an asset to the current collection.
     *
     * @param AssetInterface $asset An asset
     */
    public function add(AssetInterface $asset);

    /**
     * Removes a leaf.
     *
     * @param AssetInterface $needle The leaf to remove
     *
     * @throws \InvalidArgumentException If the asset cannot be found
     */
    public function removeLeaf(AssetInterface $leaf);

    /**
     * Replaces an existing leaf with a new one.
     *
     * @param AssetInterface $needle      The current asset to replace
     * @param AssetInterface $replacement The new asset
     *
     * @throws InvalidArgumentException If the asset cannot be found
     */
    public function replaceLeaf(AssetInterface $needle, AssetInterface $replacement);
}
