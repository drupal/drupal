<?php

namespace Drupal\user;

use Drupal\Core\Field\FieldDefinitionInterface;
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

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = parent::generateSampleValue($field_definition);
    // User names larger than 60 characters won't pass validation.
    $values['value'] = substr($values['value'], 0, UserInterface::USERNAME_MAX_LENGTH);
    return $values;
  }

}
