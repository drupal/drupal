<?php
/**
 * @file
 * Definition of Drupal\layout\Config\BoundDisplayInterface
 */

namespace Drupal\layout\Config;

use Drupal\layout\Plugin\LayoutInterface;

/**
 * Interface for a Display object that is coupled to a specific layout.
 *
 * Bound displays contains references both to block instances and a specific
 * layout, and the blocks are assigned to specific regions in that layout. Bound
 * displays are used to serve real pages at request time.
 *
 * @see \Drupal\layout\Config\DisplayInterface
 */
interface BoundDisplayInterface extends DisplayInterface {

  /**
   * Sets the layout to be used by this display.
   *
   * @param string $layout_id
   *   The id of the desired layout.
   */
  public function setLayout($layout_id);

  /**
   * Returns the blocks in the requested region, ordered by weight.
   *
   * @param string $region
   *   The region from which to return the set of blocks.
   *
   * @return array
   *   The list of blocks, ordered by their weight within this display. Each
   *   value in the list is the configuration object name of the block.
   */
  public function getSortedBlocksByRegion($region);

  /**
   * Returns this display's blocks, organized by region and ordered by weight.
   *
   * @return array
   *   An array keyed by region name. For each region, the value is the same as
   *   what is returned by getSortedBlocksByRegion().
   *
   * @see getSortedBlocksByRegion()
   */
  public function getAllSortedBlocks();

  /**
   * Returns the instantiated layout object to be used by this display.
   *
   * @return \Drupal\layout\Plugin\LayoutInterface
   */
  public function getLayoutInstance();

  /**
   * Adjusts this display's block placement to work with the provided layout.
   *
   * Essentially a shortcut that calls DisplayInterface::mapBlocksToLayout(),
   * saves the result in the appropriate object property, and finally calls
   * BoundDisplayInterface::setLayout().
   *
   * @param \Drupal\layout\Plugin\LayoutInterface $layout
   *   The new layout to which blocks should be remapped.
   *
   * @see \Drupal\layout\Config\DisplayInterface::mapBlocksToLayout()
   */
  public function remapToLayout(LayoutInterface $layout);

  /**
   * Returns an entity with the non-layout-specific configuration of this one.
   *
   * @param string $id
   *   The entity id to assign to the newly created entity.
   *
   * @param string $entity_type
   *   The type of entity to create. The PHP class for this entity type must
   *   implement \Drupal\layout\Config\UnboundDisplayInterface.
   *
   * @return \Drupal\layout\Config\UnboundDisplayInterface
   *   The newly-created unbound display.
   */
  public function generateUnboundDisplay($id, $entity_type = 'unbound_display');
}
