<?php

/**
 * @file
 * Contains \Drupal\field_test\Plugin\Field\FieldType\TestItem.
 */

namespace Drupal\field_test\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'test_field' entity field item.
 *
 * @FieldType(
 *   id = "test_field",
 *   label = @Translation("Test field"),
 *   description = @Translation("Dummy field type used for tests."),
 *   default_widget = "test_field_widget",
 *   default_formatter = "field_test_default"
 * )
 */
class TestItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'test_field_storage_setting' => 'dummy test string',
      'changeable' => 'a changeable field storage setting',
      'unchangeable' => 'an unchangeable field storage setting',
      'translatable_storage_setting' => 'a translatable field storage setting',
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'test_field_setting' => 'dummy test string',
      'translatable_field_setting' => 'a translatable field setting',
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Test integer value'))
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
          'size' => 'medium',
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
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $form['test_field_storage_setting'] = array(
      '#type' => 'textfield',
      '#title' => t('Field test field storage setting'),
      '#default_value' => $this->getSetting('test_field_storage_setting'),
      '#required' => FALSE,
      '#description' => t('A dummy form element to simulate field storage setting.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form['test_field_setting'] = array(
      '#type' => 'textfield',
      '#title' => t('Field test field setting'),
      '#default_value' => $this->getSetting('test_field_setting'),
      '#required' => FALSE,
      '#description' => t('A dummy form element to simulate field setting.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Reports that delete() method is executed for testing purposes.
    field_test_memorize('field_test_field_delete', array($this->getEntity()));
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    $constraints[] = $constraint_manager->create('ComplexData', array(
      'value' => array(
        'TestField' => array(
          'value' => -1,
          'message' => t('%name does not accept the value @value.', array('%name' => $this->getFieldDefinition()->getLabel(), '@value' => -1)),
        )
      ),
    ));

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->value);
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsToConfigData(array $settings) {
    $settings['config_data_from_storage_setting'] = 'TRUE';
    unset($settings['storage_setting_from_config_data']);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsFromConfigData(array $settings) {
    $settings['storage_setting_from_config_data'] = 'TRUE';
    unset($settings['config_data_from_storage_setting']);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function fieldSettingsToConfigData(array $settings) {
    $settings['config_data_from_field_setting'] = 'TRUE';
    unset($settings['field_setting_from_config_data']);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function fieldSettingsFromConfigData(array $settings) {
    $settings['field_setting_from_config_data'] = 'TRUE';
    unset($settings['config_data_from_field_setting']);
    return $settings;
  }

}
