<?php

/**
 * @file
 * Contains \Drupal\options\Type\ListFloatItem.
 */

namespace Drupal\options\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'list_float' field type.
 *
 * @FieldType(
 *   id = "list_float",
 *   label = @Translation("List (float)"),
 *   description = @Translation("This field stores float values from a list of allowed 'value => label' pairs, i.e. 'Fraction': 0 => 0, .25 => 1/4, .75 => 3/4, 1 => 1."),
 *   default_widget = "options_select",
 *   default_formatter = "list_default",
 * )
 */
class ListFloatItem extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('float')
      ->setLabel(t('Float value'))
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
          'type' => 'float',
        ),
      ),
      'indexes' => array(
        'value' => array('value'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function allowedValuesDescription() {
    $description = '<p>' . t('The possible values this field can contain. Enter one value per line, in the format key|label.');
    $description .= '<br/>' . t('The key is the stored value, and must be numeric. The label will be used in displayed values and edit forms.');
    $description .= '<br/>' . t('The label is optional: if a line contains a single number, it will be used as key and label.');
    $description .= '<br/>' . t('Lists of labels are also accepted (one label per line), only if the field does not hold any values yet. Numeric keys will be automatically generated from the positions in the list.');
    $description .= '</p>';
    $description .= '<p>' . t('Allowed HTML tags in labels: @tags', array('@tags' => $this->displayAllowedTags())) . '</p>';
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  protected static function extractAllowedValues($string, $has_data) {
    $values = parent::extractAllowedValues($string, $has_data);
    if ($values) {
      $keys = array_keys($values);
      $labels = array_values($values);
      $keys = array_map(function ($key) {
        // Float keys are represented as strings and need to be disambiguated
        // ('.5' is '0.5').
        return is_numeric($key) ? (string) (float) $key : $key;
      }, $keys);

      return array_combine($keys, $labels);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function validateAllowedValue($option) {
    if (!is_numeric($option)) {
      return t('Allowed values list: each key must be a valid integer or decimal.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function simplifyAllowedValues(array $structured_values) {
    $values = array();
    foreach ($structured_values as $item) {
      // Nested elements are embedded in the label.
      if (is_array($item['label'])) {
        $item['label'] = static::simplifyAllowedValues($item['label']);
      }
      // Cast the value to a float first so that .5 and 0.5 are the same value
      // and then cast to a string so that values like 0.5 can be used as array
      // keys.
      // @see http://php.net/manual/en/language.types.array.php
      $values[(string) (float) $item['value']] = $item['label'];
    }
    return $values;
  }

}
