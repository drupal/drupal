<?php

namespace Drupal\options\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'list_float' field type.
 *
 * @FieldType(
 *   id = "list_float",
 *   label = @Translation("List (float)"),
 *   description = {
 *     @Translation("Values stored are floating-point numbers"),
 *     @Translation("For example, 'Fraction': 0 => 0, .25 => 1/4, .75 => 3/4, 1 => 1"),
 *   },
 *   category = "selection_list",
 *   weight = -10,
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
      ->setLabel(new TranslatableMarkup('Float value'))
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
          'type' => 'float',
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
    $description = '<p>' . $this->t('The name will be used in displayed options and edit forms. The value is the stored value, and must be numeric.') . '</p>';
    $description .= '<p>' . $this->t('Allowed HTML tags in labels: @tags', ['@tags' => FieldFilteredMarkup::displayAllowedTags()]) . '</p>';
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
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected static function validateAllowedValue($option) {
    if (!is_numeric($option)) {
      return new TranslatableMarkup('Allowed values list: each key must be a valid integer or decimal.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function simplifyAllowedValues(array $structured_values) {
    $values = [];
    foreach ($structured_values as $item) {
      // Nested elements are embedded in the label.
      if (is_array($item['label'])) {
        $item['label'] = static::simplifyAllowedValues($item['label']);
      }
      // Cast the value to a float first so that .5 and 0.5 are the same value
      // and then cast to a string so that values like 0.5 can be used as array
      // keys.
      // @see http://php.net/manual/language.types.array.php
      $values[(string) (float) $item['value']] = $item['label'];
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected static function castAllowedValue($value) {
    return (float) $value;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);

    foreach (Element::children($element['allowed_values']['table']) as $delta => $row) {
      // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/number
      // @see \Drupal\Core\Field\Plugin\Field\FieldWidget\NumberWidget::formElement()
      $element['allowed_values']['table'][$delta]['item']['key']['#step'] = 'any';
      $element['allowed_values']['table'][$delta]['item']['key']['#type'] = 'number';
    }

    return $element;
  }

}
