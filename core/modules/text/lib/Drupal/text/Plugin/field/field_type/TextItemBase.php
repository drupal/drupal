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
        'type' => 'string',
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
  public function getConstraints() {
    $constraint_manager = \Drupal::typedData()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    if (!empty($this->getInstance()->getField()->settings['max_length'])) {
      $max_length = $this->getInstance()->getField()->settings['max_length'];
      $constraints[] = $constraint_manager->create('ComplexData', array(
        'value' => array(
          'Length' => array(
            'max' => $max_length,
            'maxMessage' => t('%name: the text may not be longer than @max characters.', array('%name' => $this->getInstance()->label, '@max' => $max_length)),
          )
        ),
      ));
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareCache() {
    // Where possible, generate the sanitized version of each field early so
    // that it is cached in the field cache. This avoids the need to look up the
    // field in the filter cache separately.
    if (!$this->getInstance()->settings['text_processing'] || filter_format_allowcache($this->get('format')->getValue())) {
      $itemBC = $this->getValue();
      $langcode = $this->getParent()->getParent()->language()->langcode;
      $this->set('safe_value', text_sanitize($this->getInstance()->settings['text_processing'], $langcode, $itemBC, 'value'));
      if ($this->getType() == 'field_item:text_with_summary') {
        $this->set('safe_summary', text_sanitize($this->getInstance()->settings['text_processing'], $langcode, $itemBC, 'summary'));
      }
    }
  }

}
