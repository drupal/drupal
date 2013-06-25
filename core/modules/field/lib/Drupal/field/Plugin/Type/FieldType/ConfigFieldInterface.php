<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\Type\FieldType\ConfigFieldInterface.
 */

namespace Drupal\field\Plugin\Type\FieldType;

use Drupal\Core\Entity\Field\FieldInterface;

/**
 * Interface definition for "configurable fields".
 */
interface ConfigFieldInterface extends FieldInterface {

  /**
   * Returns the field instance definition.
   *
   * @var \Drupal\field\Plugin\Core\Entity\FieldInstance
   */
  public function getInstance();

}
