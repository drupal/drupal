<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\DataType\IntegerItem.
 */

namespace Drupal\Core\Entity\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'integer_field' entity field item.
 *
 * @DataType(
 *   id = "integer_field",
 *   label = @Translation("Integer field item"),
 *   description = @Translation("An entity field containing an integer value."),
 *   list_class = "\Drupal\Core\Entity\Field\Field"
 * )
 */
class IntegerItem extends FieldItemBase {

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
        'type' => 'integer',
        'label' => t('Integer value'),
      );
    }
    return static::$propertyDefinitions;
  }
}
