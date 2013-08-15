<?php
/**
 * @file
 * Definition of Drupal\layout\Config\DisplayInterface
 */

namespace Drupal\layout\Config;

use Drupal\layout\Plugin\LayoutInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface describing a Display configuration object.
 *
 * Displays are configuration that describe the placement of block instances
 * in regions. Drupal includes two types of Display objects:
 * - Bound displays include a reference to a specific layout, and each block is
 *   specified to display in a specific region of that layout. Bound displays
 *   are used to serve real pages at request time.
 * - Unbound displays do not include a reference to any layout, and each block
 *   is assigned a region type, but not a specific region. Developers including
 *   default displays with their modules or distributions are encouraged to use
 *   unbound displays in order to minimize dependencies on specific layouts and
 *   allow site-specific configuration to dictate the layout.
 *
 * This interface defines what is common to all displays, whether bound or
 * unbound.
 *
 * @see \Drupal\layout\Config\BoundDisplayInterface
 * @see \Drupal\layout\Config\UnboundDisplayInterface
 */
interface DisplayInterface extends ConfigEntityInterface {

  /**
   * Returns the display-specific configuration of all blocks in this display.
   *
   * For each block that exists in Drupal (e.g., the "Who's Online" block),
   * multiple "configured instances" can be created (e.g., a "Who's been online
   * in the last 5 minutes" instance and a "Who's been online in the last 60
   * minutes" instance). Each configured instance can be referenced by multiple
   * displays (e.g., by a "regular" page, by an administrative page, and within
   * one or more dashboards). This function returns the block instances that
   * have been added to this display. Each key of the returned array is the
   * block instance's configuration object name, and \Drupal::config() may be called on
   * it in order to retrieve the full configuration that is shared across all
   * displays. For each key, the value is an array of display-specific
   * configuration, primarily the 'region' and 'weight', and anything else that
   * affects the placement of the block within the layout rather than only the
   * contents of the block.
   *
   * @return array
   *   An array keyed on each block's configuration object name. Each value is
   *   an array of information that determines the placement of the block within
   *   a layout, including:
   *   - region: The region in which to display the block (for bound displays
   *     only).
   *   - region-type: The type of region that is most appropriate for the block.
   *     Usually one of 'header', 'footer', 'nav', 'content', 'aside', or
   *     'system', though custom region types are also allowed. This is
   *     primarily specified by unbound displays, where specifying a specific
   *     region name is impossible, because different layouts come with
   *     different regions.
   *   - weight: Within a region, blocks are rendered from low to high weight.
   */
  public function getAllBlockInfo();

  /**
   * Maps the contained block info to the provided layout.
   *
   * @param \Drupal\layout\Plugin\LayoutInterface $layout
   *
   * @return array
   *   An array containing block configuration info, identical to that which
   *   is returned by DisplayInterface::getAllBlockInfo().
   */
  public function mapBlocksToLayout(LayoutInterface $layout);

  /**
   * Returns the names of all region types to which blocks are assigned.
   *
   * @return array
   *   An indexed array of unique region type names, or an empty array if no
   *   region types were assigned.
   */
  public function getAllRegionTypes();
}
