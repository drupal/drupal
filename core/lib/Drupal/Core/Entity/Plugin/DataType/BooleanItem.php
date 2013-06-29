<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\DataType\BooleanItem.
 */

namespace Drupal\Core\Entity\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'boolean_field' entity field item.
 *
 * @DataType(
 *   id = "boolean_field",
 *   label = @Translation("Boolean field item"),
 *   description = @Translation("An entity field containing a boolean value."),
 *   list_class = "\Drupal\Core\Entity\Field\Field"
 * )
 */
class BooleanItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see BooleanItem::getPropertyDefinitions()
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
        'type' => 'boolean',
        'label' => t('Boolean value'),
      );
    }
    return static::$propertyDefinitions;
  }
}
