<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\Type\EmailItem.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\field\Plugin\field\field_type\LegacyConfigFieldItem;

/**
 * Defines the 'email_field' entity field item.
 */
class EmailItem extends LegacyConfigFieldItem {

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
    return !isset($this->values['value']) || $this->values['value'] === '';
  }
}
