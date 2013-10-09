<?php

/**
 * @file
 * Contains \Drupal\field_test\Plugin\field\field_type\HiddenTestItem.
 */

namespace Drupal\field_test\Plugin\field\field_type;

use Drupal\Core\Entity\Annotation\FieldType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the 'hidden_test' entity field item.
 *
 * @FieldType(
 *   id = "hidden_test_field",
 *   label = @Translation("Hidden from UI test field"),
 *   description = @Translation("Dummy hidden field type used for tests."),
 *   no_ui = TRUE,
 *   default_widget = "test_field_widget",
 *   default_formatter = "field_test_default"
 * )
 */
class HiddenTestItem extends TestItem {

  /**
   * Property definitions of the contained properties.
   *
   * @see TestItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = array(
        'type' => 'integer',
        'label' => t('Test integer value'),
      );
    }
    return static::$propertyDefinitions;
  }

}
