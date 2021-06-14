<?php

namespace Drupal\user;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\user\Entity\User;

/**
 * Defines a custom field item class for the 'timezone' user entity field.
 */
class TimeZoneItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $timezones = User::getAllowedTimezones();
    // We need to vary the selected timezones since we're generating a sample.
    $key = rand(0, count($timezones) - 1);
    return $timezones[$key];
  }

}
