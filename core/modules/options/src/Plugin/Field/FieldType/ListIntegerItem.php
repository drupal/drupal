<?php

namespace Drupal\options\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'list_integer' field type.
 */
#[FieldType(
  id: "list_integer",
  label: new TranslatableMarkup("List (integer)"),
  description: [
    new TranslatableMarkup("Values stored are numbers without decimals"),
    new TranslatableMarkup("For example, 'Lifetime in days': 1 => 1 day, 7 => 1 week, 31 => 1 month"),
  ],
  category: "selection_list",
  weight: -30,
  default_widget: "options_select",
  default_formatter: "list_default",
)]
class ListIntegerItem extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Integer value'))
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
          'type' => 'int',
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
  protected static function validateAllowedValue($option) {
    if (!preg_match('/^-?\d+$/', $option)) {
      return new TranslatableMarkup('Allowed values list: keys must be integers.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function castAllowedValue($value) {
    return (int) $value;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);

    foreach (Element::children($element['allowed_values']['table']) as $delta => $row) {
      // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/number
      // @see \Drupal\Core\Field\Plugin\Field\FieldWidget\NumberWidget::formElement()
      $element['allowed_values']['table'][$delta]['item']['key']['#type'] = 'number';
    }

    return $element;
  }

}
