<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\Field\FieldType\TextItemBase.
 */

namespace Drupal\text\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\PrepareCacheInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Base class for 'text' configurable field types.
 */
abstract class TextItemBase extends FieldItemBase implements PrepareCacheInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultInstanceSettings() {
    $settings = parent::defaultInstanceSettings();
    $settings['text_processing'] = 0;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Text value'));

    $properties['format'] = DataDefinition::create('filter_format')
      ->setLabel(t('Text format'));

    $properties['processed'] = DataDefinition::create('string')
      ->setLabel(t('Processed text'))
      ->setDescription(t('The text value with the text format applied.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\text\TextProcessed')
      ->setSetting('text source', 'value');

    return $properties;
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
  public function getCacheData() {
    $data = $this->getValue();
    // Where possible, generate the processed (sanitized) version of each
    // textual property (e.g., 'value', 'summary') within this field item early
    // so that it is cached in the field cache. This avoids the need to look up
    // the sanitized value in the filter cache separately.
    $text_processing = $this->getSetting('text_processing');
    if (!$text_processing || filter_format_allowcache($this->get('format')->getValue())) {
      foreach ($this->definition->getPropertyDefinitions() as $property => $definition) {
        if ($definition->getClass() == '\Drupal\text\TextProcessed') {
          $data[$property] = $this->get($property)->getValue();
        }
      }
    }
    return $data;
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
    foreach ($this->definition->getPropertyDefinitions() as $property => $definition) {
      if ($definition->getClass() == '\Drupal\text\TextProcessed') {
        if ($property_name == 'format' || ($definition->getSetting('text source') == $property_name)) {
          $this->set($property, NULL, FALSE);
        }
      }
    }
  }

}
