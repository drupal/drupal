<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\field\field_type\TextItemBase.
 */

namespace Drupal\text\Plugin\field\field_type;

use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemBase;
use Drupal\Core\Entity\Field\PrepareCacheInterface;

/**
 * Base class for 'text' configurable field types.
 */
abstract class TextItemBase extends ConfigFieldItemBase implements PrepareCacheInterface {

  /**
   * Definitions of the contained properties.
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = array(
        'type' => 'string',
        'label' => t('Text value'),
      );
      static::$propertyDefinitions['format'] = array(
        'type' => 'filter_format',
        'label' => t('Text format'),
      );
      static::$propertyDefinitions['processed'] = array(
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
    return static::$propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Default to a simple check_plain().
    // @todo: Add in the filter default format here.
    $this->setValue(array('format' => NULL), $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function prepareCache() {
    // Where possible, generate the processed (sanitized) version of each
    // textual property (e.g., 'value', 'summary') within this field item early
    // so that it is cached in the field cache. This avoids the need to look up
    // the sanitized value in the filter cache separately.
    $text_processing = $this->getFieldSetting('text_processing');
    if (!$text_processing || filter_format_allowcache($this->get('format')->getValue())) {
      foreach ($this->getPropertyDefinitions() as $property => $definition) {
        if (isset($definition['class']) && ($definition['class'] == '\Drupal\text\TextProcessed')) {
          $this->get($property)->getValue();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name) {
    // Notify the parent of changes.
    if (isset($this->parent)) {
      $this->parent->onChange($this->name);
    }

    // Unset processed properties that are affected by the change.
    foreach ($this->getPropertyDefinitions() as $property => $definition) {
      if (isset($definition['class']) && ($definition['class'] == '\Drupal\text\TextProcessed')) {
        if ($property_name == 'format' || (isset($definition['settings']['text source']) && $definition['settings']['text source'] == $property_name)) {
          $this->set($property, NULL, FALSE);
        }
      }
    }
  }

}
