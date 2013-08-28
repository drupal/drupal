<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\DataType\EmailItem.
 */

namespace Drupal\Core\Entity\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldItemBase;
use Drupal\field\Plugin\field\field_type\LegacyConfigFieldItem;

/**
 * Defines the 'email_field' entity field item.
 *
 * @DataType(
 *   id = "email_field",
 *   label = @Translation("E-mail field item"),
 *   description = @Translation("An entity field containing an e-mail value."),
 *   list_class = "\Drupal\Core\Entity\Field\Field"
 * )
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
    return $this->value === NULL || $this->value === '';
  }
}
