<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\UriItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'uri' entity field type.
 *
 * URIs are not length limited by RFC 2616, but we need to provide a sensible
 * default. There is a de-facto limit of 2000 characters in browsers and other
 * implementors, so we go with 2048.
 *
 * @FieldType(
 *   id = "uri",
 *   label = @Translation("URI"),
 *   description = @Translation("An entity field containing a URI."),
 *   no_ui = TRUE,
 *   default_formatter = "uri_link",
 *   default_widget = "uri",
 * )
 */
class UriItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'max_length' => 2048,
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('uri')
      ->setLabel(t('URI value'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->getValue();
    if (!isset($value['value']) || $value['value'] === '') {
      return TRUE;
    }
    return parent::isEmpty();
  }

}
