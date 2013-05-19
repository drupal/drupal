<?php

/**
 * @file
 * Contains \Drupal\entity\EntityFormDisplayInterface.
 */

namespace Drupal\entity;

use Drupal\entity\EntityDisplayBaseInterface;

/**
 * Provides an interface defining an entity display entity.
 */
interface EntityFormDisplayInterface extends EntityDisplayBaseInterface {

  /**
   * Returns the Widget plugin for a field.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\field\Plugin\Type\Widget\WidgetInterface|null
   *   A Widget plugin or NULL if the field does not exist.
   */
  public function getWidget($field_name);

}
