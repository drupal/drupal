<?php

/**
 * @file
 * Contains \Drupal\file\Type\FileField.
 */

namespace Drupal\file\Type;

use Drupal\field\Plugin\field\field_type\LegacyConfigField;

/**
 * Represents a configurable entity file field.
 */
class FileField extends LegacyConfigField {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, array &$form_state) { }

}
