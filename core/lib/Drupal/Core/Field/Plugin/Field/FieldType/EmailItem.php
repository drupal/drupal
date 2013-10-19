<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Field\FieldType\EmailItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'email' entity field type.
 *
 * @FieldType(
 *   id = "email",
 *   label = @Translation("E-mail"),
 *   description = @Translation("An entity field containing an e-mail value."),
 *   configurable = FALSE
 * )
 */
class EmailItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see EmailItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = array(
        'type' => 'email',
        'label' => t('E-mail value'),
      );
    }
    return static::$propertyDefinitions;
  }


  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->value === NULL || $this->value === '';
  }

}
