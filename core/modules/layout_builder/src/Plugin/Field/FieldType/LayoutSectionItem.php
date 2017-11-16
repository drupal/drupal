<?php

namespace Drupal\layout_builder\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\layout_builder\Field\LayoutSectionItemInterface;
use Drupal\layout_builder\Section;

/**
 * Plugin implementation of the 'layout_section' field type.
 *
 * @internal
 *
 * @FieldType(
 *   id = "layout_section",
 *   label = @Translation("Layout Section"),
 *   description = @Translation("Layout Section"),
 *   default_formatter = "layout_section",
 *   list_class = "\Drupal\layout_builder\Field\LayoutSectionItemList",
 *   no_ui = TRUE,
 *   cardinality = \Drupal\Core\Field\FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
 * )
 */
class LayoutSectionItem extends FieldItemBase implements LayoutSectionItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['layout'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Layout'))
      ->setSetting('case_sensitive', FALSE)
      ->setRequired(TRUE);
    $properties['layout_settings'] = MapDataDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Layout Settings'))
      ->setRequired(FALSE);
    $properties['section'] = MapDataDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Layout Section'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    // @todo \Drupal\Core\Field\FieldItemBase::__get() does not return default
    //   values for uninstantiated properties. This will forcibly instantiate
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
        'layout' => [
          'type' => 'varchar',
          'length' => '255',
          'binary' => FALSE,
        ],
        'layout_settings' => [
          'type' => 'blob',
          'size' => 'normal',
          // @todo Address in https://www.drupal.org/node/2914503.
          'serialize' => TRUE,
        ],
        'section' => [
          'type' => 'blob',
          'size' => 'normal',
          // @todo Address in https://www.drupal.org/node/2914503.
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
    $values['layout'] = 'layout_onecol';
    $values['layout_settings'] = [];
    // @todo Expand this in https://www.drupal.org/node/2912331.
    $values['section'] = [];
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->layout);
  }

  /**
   * {@inheritdoc}
   */
  public function getSection() {
    return new Section($this->section);
  }

  /**
   * {@inheritdoc}
   */
  public function updateFromSection(Section $section) {
    $this->section = $section->getValue();
    return $this;
  }

}
