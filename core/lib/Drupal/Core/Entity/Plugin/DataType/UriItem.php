<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\DataType\UriItem.
 */

namespace Drupal\Core\Entity\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'uri_field' entity field item.
 *
 * @DataType(
 *   id = "uri_field",
 *   label = @Translation("URI field item"),
 *   description = @Translation("An entity field containing a URI."),
 *   list_class = "\Drupal\Core\Entity\Field\Field"
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
