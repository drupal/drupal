<?php

/**
 * @file
 * Contains \Drupal\entity\EntityDisplayInterface.
 */

namespace Drupal\entity;

use Drupal\entity\EntityDisplayBaseInterface;

/**
 * Provides an interface defining an entity display entity.
 */
interface EntityDisplayInterface extends EntityDisplayBaseInterface {

  /**
   * Returns the Formatter plugin for a field.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\field\Plugin\Type\Formatter\FormatterInterface
   *   If the field is not hidden, the Formatter plugin to use for rendering
   *   it.
   */
  public function getFormatter($field_name);

}
