<?php

/**
 * @file
 * Definition of Drupal\text\Type\TextItem.
 */

namespace Drupal\text\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'text_item' and 'text_long_item' entity field items.
 */
class TextItem extends FieldItemBase {

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
        'type' => 'string',
        'label' => t('Text value'),
      );
      self::$propertyDefinitions['format'] = array(
        'type' => 'string',
        'label' => t('Text format'),
      );
      self::$propertyDefinitions['processed'] = array(
        'type' => 'string',
        'label' => t('Processed text'),
        'description' => t('The text value with the text format applied.'),
        'computed' => TRUE,
        'class' => '\Drupal\text\TextProcessed',
        'settings' => array(
          'text source' => 'value',
        ),
      );
    }
    return self::$propertyDefinitions;
  }
}
