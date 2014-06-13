<?php

/**
 * @file
 * Contains \Drupal\options\Type\ListBooleanItem.
 */

namespace Drupal\options\Plugin\Field\FieldType;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\AllowedValuesInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'list_boolean' field type.
 *
 * @FieldType(
 *   id = "list_boolean",
 *   label = @Translation("Boolean"),
 *   description = @Translation("This field stores simple on/off or yes/no options."),
 *   default_widget = "options_buttons",
 *   default_formatter = "list_default",
 * )
 */
class ListBooleanItem extends FieldItemBase implements AllowedValuesInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'allowed_values' => array(),
      'allowed_values_function' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return array_keys($this->getSettableOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return $this->getSettableOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    return array_keys($this->getSettableOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    return options_allowed_values($this->getFieldDefinition(), $this->getEntity());
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('boolean')
      ->setLabel(t('Boolean value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'not null' => FALSE,
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
  public function isEmpty() {
    return empty($this->value) && (string) $this->value !== '0';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, array &$form_state, $has_data) {
    $allowed_values = $this->getSetting('allowed_values');
    $allowed_values_function = $this->getSetting('allowed_values_function');

    $values = $allowed_values;
    $off_value = array_shift($values);
    $on_value = array_shift($values);

    $element['allowed_values'] = array(
      '#type' => 'value',
      '#description' => '',
      '#value_callback' => array(get_class($this), 'optionsBooleanAllowedValues'),
      '#access' => empty($allowed_values_function),
    );
    $element['allowed_values']['on'] = array(
      '#type' => 'textfield',
      '#title' => t('On value'),
      '#default_value' => $on_value,
      '#required' => FALSE,
      '#description' => t('If left empty, "1" will be used.'),
      // Change #parents to make sure the element is not saved into field
      // settings.
      '#parents' => array('on'),
    );
    $element['allowed_values']['off'] = array(
      '#type' => 'textfield',
      '#title' => t('Off value'),
      '#default_value' => $off_value,
      '#required' => FALSE,
      '#description' => t('If left empty, "0" will be used.'),
      // Change #parents to make sure the element is not saved into field
      // settings.
      '#parents' => array('off'),
    );

    // Link the allowed value to the on / off elements to prepare for the rare
    // case of an alter changing #parents.
    $element['allowed_values']['#on_parents'] = &$element['allowed_values']['on']['#parents'];
    $element['allowed_values']['#off_parents'] = &$element['allowed_values']['off']['#parents'];

    $element['allowed_values_function'] = array(
      '#type' => 'item',
      '#title' => t('Allowed values list'),
      '#markup' => t('The value of this field is being determined by the %function function and may not be changed.', array('%function' => $allowed_values_function)),
      '#access' => !empty($allowed_values_function),
      '#value' => $allowed_values_function,
    );

    return $element;
  }

  /**
   * Form element #value_callback: assembles the allowed values for 'boolean'
   * fields.
   */
  public static function optionsBooleanAllowedValues($element, $input, $form_state) {
    $on = NestedArray::getValue($form_state['input'], $element['#on_parents']);
    $off = NestedArray::getValue($form_state['input'], $element['#off_parents']);
    return array($off, $on);
  }

}
