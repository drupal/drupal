<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Field\FieldType\UriItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'uri' entity field type.
 *
 * @FieldType(
 *   id = "uri",
 *   label = @Translation("URI"),
 *   description = @Translation("An entity field containing a URI."),
 *   configurable = FALSE
 * )
 */
class UriItem extends FieldItemBase {

  /**
   * Field definitions of the contained properties.
   *
   * @see self::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    if (!isset(self::$propertyDefinitions)) {
      self::$propertyDefinitions['value'] = DataDefinition::create('uri')
        ->setLabel(t('URI value'));
    }
    return self::$propertyDefinitions;
  }

}
