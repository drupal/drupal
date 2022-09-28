<?php

namespace Drupal\options\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'list_string' field type.
 *
 * @FieldType(
 *   id = "list_string",
 *   label = @Translation("List (text)"),
 *   description = @Translation("This field stores text values from a list of allowed 'value => label' pairs, i.e. 'US States': IL => Illinois, IA => Iowa, IN => Indiana."),
 *   category = @Translation("Text"),
 *   default_widget = "options_select",
 *   default_formatter = "list_default",
 * )
 */
class ListStringItem extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Text value'))
      ->addConstraint('Length', ['max' => 255])
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
          'length' => 255,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function allowedValuesDescription() {
    $description = '<p>' . $this->t('The possible values this field can contain. Enter one value per line, in the format key|label.');
    $description .= '<br/>' . $this->t('The key is the stored value. The label will be used in displayed values and edit forms.');
    $description .= '<br/>' . $this->t('The label is optional: if a line contains a single string, it will be used as key and label.');
    $description .= '</p>';
    $description .= '<p>' . $this->t('Allowed HTML tags in labels: @tags', ['@tags' => FieldFilteredMarkup::displayAllowedTags()]) . '</p>';
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  protected static function validateAllowedValue($option) {
    if (mb_strlen($option) > 255) {
      return new TranslatableMarkup('Allowed values list: each key must be a string at most 255 characters long.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function castAllowedValue($value) {
    return (string) $value;
  }

}
