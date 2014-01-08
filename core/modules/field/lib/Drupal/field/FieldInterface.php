<?php

/**
 * @file
 * Contains \Drupal\field\FieldInterface.
 */

namespace Drupal\field;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Provides an interface defining a field entity.
 */
interface FieldInterface extends ConfigEntityInterface, FieldDefinitionInterface {

  /**
   * Returns the list of bundles where the field has instances.
   *
   * @return array
   *   An array of bundle names.
   */
  public function getBundles();

}
