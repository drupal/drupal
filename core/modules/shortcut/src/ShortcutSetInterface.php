<?php

namespace Drupal\shortcut;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a shortcut set entity.
 */
interface ShortcutSetInterface extends ConfigEntityInterface {

  /**
   * Resets the link weights in a shortcut set to match their current order.
   *
   * This function can be used, for example, when a new shortcut link is added
   * to the set. If the link is added to the end of the array and this function
   * is called, it will force that link to display at the end of the list.
   *
   * @return $this
   *   The shortcut set.
   */
  public function resetLinkWeights();

  /**
   * Returns all the shortcuts from a shortcut set sorted correctly.
   *
   * @return \Drupal\shortcut\ShortcutInterface[]
   *   An array of shortcut entities.
   */
  public function getShortcuts();

}
