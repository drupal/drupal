<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\field\field_type\UriItem.
 */

namespace Drupal\Core\Entity\Plugin\field\field_type;

use Drupal\Core\Entity\Field\FieldItemBase;

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
      self::$propertyDefinitions['value'] = array(
        'type' => 'uri',
        'label' => t('URI value'),
      );
    }
    return self::$propertyDefinitions;
  }
}
