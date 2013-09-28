<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\DataType\FloatItem.
 */

namespace Drupal\Core\Entity\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'float_field' entity field item.
 *
 * @DataType(
 *   id = "float_field",
 *   label = @Translation("Float field item"),
 *   description = @Translation("An entity field containing an float value."),
 *   list_class = "\Drupal\Core\Entity\Field\Field"
 * )
 */
class FloatItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see IntegerItem::getPropertyDefinitions()
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
        'type' => 'float',
        'label' => t('Float value'),
      );
    }
    return static::$propertyDefinitions;
  }
}
