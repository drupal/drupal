<?php
/**
 * @file
 * Definition of Drupal\layout\Config\UnboundDisplayInterface
 */

namespace Drupal\layout\Config;

use Drupal\layout\Plugin\LayoutInterface;

/**
 * Interface for a Display that is not coupled with any layout.
 *
 * Unbound displays contain references to blocks, but not to any particular
 * layout. Their primary use case is to express a set of relative block
 * placements without necessitating any particular layout be present. This
 * allows upstream (module and distribution) developers to express a visual
 * composition of blocks without knowing anything about the layouts a
 * particular site has available.
 *
 * @see \Drupal\layout\Config\DisplayInterface
 */
interface UnboundDisplayInterface extends DisplayInterface {

  /**
   * Returns a bound display entity by binding a layout to this unbound display.
   *
   * This will DisplayInterface::mapBlocksToLayout() using the provided layout,
   * then create and return a new Display object with the output. This is just
   * a factory - calling code is responsible for saving the returned object.
   *
   * @param \Drupal\layout\Plugin\LayoutInterface $layout
   *   The desired layout.
   *
   * @param string $id
   *   The entity id to assign to the newly created entity.
   *
   * @param string $entity_type
   *   The type of entity to create. The PHP class for this entity type must
   *   implement \Drupal\layout\Config\BoundDisplayInterface.
   *
   * @return \Drupal\layout\Config\BoundDisplayInterface
   *   The newly created entity.
   */
  public function generateDisplay(LayoutInterface $layout, $id, $entity_type = 'display');
}
