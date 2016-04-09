<?php

namespace Drupal\user;

use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;

/**
 * Defines a custom field item class for the 'name' user entity field.
 */
class UserNameItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();

    // Take into account that the name of the anonymous user is an empty string.
    if ($this->getEntity()->isAnonymous()) {
      return $value === NULL;
    }

    return $value === NULL || $value === '';
  }

}
