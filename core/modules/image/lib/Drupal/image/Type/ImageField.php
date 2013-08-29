<?php

/**
 * @file
 * Contains \Drupal\image\Type\ImageField.
 */

namespace Drupal\image\Type;

use Drupal\field\Plugin\field\field_type\LegacyConfigField;

/**
 * Represents a configurable entity image field.
 */
class ImageField extends LegacyConfigField {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, array &$form_state) { }

}
