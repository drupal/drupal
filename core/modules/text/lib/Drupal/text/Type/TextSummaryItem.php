<?php

/**
 * @file
 * Definition of Drupal\text\Type\TextSummaryItem.
 */

namespace Drupal\text\Type;

/**
 * Defines the 'text_with_summary_field' entity field item.
 */
class TextSummaryItem extends TextItem {

  /**
   * Definitions of the contained properties.
   *
   * @see TextSummaryItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {

      static::$propertyDefinitions = parent::getPropertyDefinitions();

      static::$propertyDefinitions['summary'] = array(
        'type' => 'string',
        'label' => t('Summary text value'),
      );
      static::$propertyDefinitions['summary_processed'] = array(
        'type' => 'string',
        'label' => t('Processed summary text'),
        'description' => t('The summary text value with the text format applied.'),
        'computed' => TRUE,
        'class' => '\Drupal\text\TextProcessed',
        'settings' => array(
          'text source' => 'summary',
        ),
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * Overrides \Drupal\text\Type\TextItem::isEmpty().
   */
  public function isEmpty() {
    $value = $this->get('summary')->getValue();
    return parent::isEmpty() && ($value === NULL || $value === '');
  }
}
