<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'boolean' entity field type.
 *
 * @FieldType(
 *   id = "boolean",
 *   label = @Translation("Boolean"),
 *   description = @Translation("An entity field containing a boolean value."),
 *   default_widget = "boolean_checkbox",
 *   default_formatter = "boolean",
 * )
 */
class BooleanItem extends FieldItemBase implements OptionsProviderInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'on_label' => t('On'),
      'off_label' => t('Off'),
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('boolean')
      ->setLabel(t('Boolean value'))
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
          'type' => 'int',
          'size' => 'tiny',
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array();

    $element['on_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('"On" label'),
      '#default_value' => $this->getSetting('on_label'),
      '#required' => TRUE,
    );
    $element['off_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('"Off" label'),
      '#default_value' => $this->getSetting('off_label'),
      '#required' => TRUE,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return array(0, 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return array(
      0 => $this->getSetting('off_label'),
      1 => $this->getSetting('on_label'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    return array(0, 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    return $this->getPossibleOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = mt_rand(0, 1);
    return $values;
  }
}

