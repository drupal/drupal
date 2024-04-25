<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'uri' entity field type.
 *
 * URIs are not length limited by RFC 2616, but we need to provide a sensible
 * default. There is a de-facto limit of 2000 characters in browsers and other
 * implementors, so we go with 2048.
 */
#[FieldType(
  id: "uri",
  label: new TranslatableMarkup("URI"),
  description: new TranslatableMarkup("An entity field containing a URI."),
  default_widget: "uri",
  default_formatter: "uri_link",
  no_ui: TRUE,
)]
class UriItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $storage_settings = parent::defaultStorageSettings();
    // is_ascii doesn't make sense for URIs.
    unset($storage_settings['is_ascii']);
    $storage_settings['max_length'] = 2048;
    return $storage_settings;
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
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ],
      ],
    ];
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

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();

    $max_length = $field_definition->getSetting('max_length');
    $min_length = min(10, $max_length);

    // The random value is generated multiple times to create a slight
    // preference towards values that are closer to the minimum length of the
    // string.
    $length = mt_rand($min_length, mt_rand($min_length, mt_rand($min_length, $max_length)));
    $values['value'] = $random->word($length);

    $suffix_length = $max_length - 7;
    foreach ($values as $key => $value) {
      $values[$key] = 'http://' . mb_substr($value, 0, $suffix_length);
    }
    return $values;
  }

}
