<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutPathValue.
 */

namespace Drupal\shortcut;

use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for the string value of the path field.
 */
class ShortcutPathValue extends TypedData {

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if (!isset($this->value)) {
      if (!isset($this->parent)) {
        throw new \InvalidArgumentException('Computed properties require context for computation.');
      }

      $entity = $this->parent->getEntity();
      if ($route_name = $entity->getRouteName()) {
        $path = \Drupal::urlGenerator()->getPathFromRoute($route_name, $entity->getRouteParams());
        $this->value = trim($path, '/');
      }
      else {
        $this->value = NULL;
      }
    }
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    // Normalize the path in case it is an alias.
    $value = \Drupal::service('path.alias_manager')->getSystemPath($value);
    if (empty($value)) {
      $value = '<front>';
    }

    parent::setValue($value, $notify);
  }

}
