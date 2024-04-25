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
 * Plugin implementation of the 'list_string' field type.
 */
#[FieldType(
  id: "list_string",
  label: new TranslatableMarkup("List (text)"),
  description: [
    new TranslatableMarkup("Values stored are text values"),
    new TranslatableMarkup("For example, 'US States': IL => Illinois, IA => Iowa, IN => Indiana"),
  ],
  category: "selection_list",
  weight: -50,
  default_widget: "options_select",
  default_formatter: "list_default",
)]
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
    $description = '<p>' . $this->t('The name will be used in displayed options and edit forms.');
    $description .= '<br/>' . $this->t('The value is automatically generated machine name of the name provided and will be the stored value.');
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

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);

    // Improve user experience by using an automatically generated machine name.
    foreach (Element::children($element['allowed_values']['table']) as $delta => $row) {
      $element['allowed_values']['table'][$delta]['item']['key']['#type'] = 'machine_name';
      $element['allowed_values']['table'][$delta]['item']['key']['#machine_name'] = [
        'exists' => [static::class, 'exists'],
      ];
      $element['allowed_values']['table'][$delta]['item']['key']['#process'] = array_merge(
        [[static::class, 'processAllowedValuesKey']],
        // Workaround for https://drupal.org/i/1300290#comment-12873635.
        \Drupal::service('plugin.manager.element_info')->getInfoProperty('machine_name', '#process', []),
      );
      // Remove #element_validate from the machine name so that any value can be
      // used as a key, while keeping the widget's behavior for generating
      // defaults the same.
      $element['allowed_values']['table'][$delta]['item']['key']['#element_validate'] = [];
    }

    return $element;
  }

  /**
   * Sets the machine name source to be the label.
   */
  public static function processAllowedValuesKey(array &$element): array {
    $parents = $element['#parents'];
    array_pop($parents);
    $parents[] = 'label';
    $element['#machine_name']['source'] = $parents;

    // Override the default description which is not applicable to this use of
    // the machine name element given that it allows users to manually enter
    // characters usually not allowed in machine names.
    if (!isset($element['#description'])) {
      $element['#description'] = '';
    }

    return $element;
  }

  /**
   * Checks for existing keys for allowed values.
   */
  public static function exists(): bool {
    // Without access to the current form state, we cannot know if a given key
    // is in use. Return FALSE in all cases.
    return FALSE;
  }

}
