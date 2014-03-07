<?php

/**
 * @file
 * Contains \Drupal\editor\EditorInterface.
 */

namespace Drupal\editor;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a text editor entity.
 */
interface EditorInterface extends ConfigEntityInterface {

  /**
   * Returns the filter format this text editor is associated with.
   *
   * @return \Drupal\filter\FilterFormatInterface
   */
  public function getFilterFormat();

}
