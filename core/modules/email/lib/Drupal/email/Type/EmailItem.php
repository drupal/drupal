<?php

/**
 * @file
 * Contains \Drupal\email\Type\EmailItem.
 */

namespace Drupal\email\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'email_field' entity field item.
 */
class EmailItem extends FieldItemBase {

  /**
   * Property definitions of the contained properties.
   *
   * @see self::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {

    if (!isset(self::$propertyDefinitions)) {
      self::$propertyDefinitions['value'] = array(
        'type' => 'email',
        'label' => t('E-mail value'),
      );
    }
    return self::$propertyDefinitions;
  }

}
