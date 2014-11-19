<?php

/**
 * @file
 * Contains \Drupal\block\BlockRepositoryInterface.
 */

namespace Drupal\block;

interface BlockRepositoryInterface {

  /**
   * Returns an array of regions and their block entities.
   *
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts to set on the blocks.
   *
   * @return array
   *   The array is first keyed by region machine name, with the values
   *   containing an array keyed by block ID, with block entities as the values.
   */
  public function getVisibleBlocksPerRegion(array $contexts);

}
