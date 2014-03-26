<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Field\FieldType\UuidItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

/**
 * Defines the 'uuid' entity field type.
 *
 * The field uses a newly generated UUID as default value.
 *
 * @FieldType(
 *   id = "uuid",
 *   label = @Translation("UUID"),
 *   description = @Translation("An entity field containing a UUID."),
 *   no_ui = TRUE
 * )
 */
class UuidItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'max_length' => 128,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Default to one field item with a generated UUID.
    $uuid = \Drupal::service('uuid');
    $this->setValue(array('value' => $uuid->generate()), $notify);
    return $this;
  }
}
