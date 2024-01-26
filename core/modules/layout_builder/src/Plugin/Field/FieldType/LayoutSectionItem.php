<?php

namespace Drupal\layout_builder\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\layout_builder\Section;

/**
 * Plugin implementation of the 'layout_section' field type.
 *
 * @internal
 *   Plugin classes are internal.
 *
 * @FieldType(
 *   id = "layout_section",
 *   label = @Translation("Layout Section"),
 *   description = @Translation("Layout Section"),
 *   list_class = "\Drupal\layout_builder\Field\LayoutSectionItemList",
 *   no_ui = TRUE,
 *   cardinality = \Drupal\Core\Field\FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
 * )
 *
 * @property \Drupal\layout_builder\Section $section
 */
class LayoutSectionItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['section'] = DataDefinition::create('layout_section')
      ->setLabel(new TranslatableMarkup('Layout Section'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    // @todo \Drupal\Core\Field\FieldItemBase::__get() does not return default
    //   values for un-instantiated properties. This will forcibly instantiate
    //   all properties with the side-effect of a performance hit, resolve
    //   properly in https://www.drupal.org/node/2413471.
    $this->getProperties();

    return parent::__get($name);
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'section';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'section' => [
          'type' => 'blob',
          'size' => 'normal',
          'serialize' => TRUE,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // @todo Expand this in https://www.drupal.org/node/2912331.
    $values['section'] = new Section('layout_onecol');
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->section);
  }

}
