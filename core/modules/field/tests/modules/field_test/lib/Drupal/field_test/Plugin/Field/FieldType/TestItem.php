<?php

/**
 * @file
 * Contains \Drupal\field_test\Plugin\Field\FieldType\TestItem.
 */

namespace Drupal\field_test\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\PrepareCacheInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'test_field' entity field item.
 *
 * @FieldType(
 *   id = "test_field",
 *   label = @Translation("Test field"),
 *   description = @Translation("Dummy field type used for tests."),
 *   settings = {
 *     "test_field_setting" = "dummy test string",
 *     "changeable" =  "a changeable field setting",
 *     "unchangeable" = "an unchangeable field setting"
 *   },
 *   instance_settings = {
 *     "test_instance_setting" = "dummy test string",
 *     "test_cached_data" = FALSE
 *   },
 *   default_widget = "test_field_widget",
 *   default_formatter = "field_test_default"
 * )
 */
class TestItem extends FieldItemBase implements PrepareCacheInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Test integer value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'size' => 'medium',
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
  public function settingsForm(array $form, array &$form_state, $has_data) {
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
  public function instanceSettingsForm(array $form, array &$form_state) {
    $form['test_instance_setting'] = array(
      '#type' => 'textfield',
      '#title' => t('Field test field instance setting'),
      '#default_value' => $this->getSetting('test_instance_setting'),
      '#required' => FALSE,
      '#description' => t('A dummy form element to simulate field instance setting.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheData() {
    // To keep the test non-intrusive, only act for instances with the
    // 'test_cached_data' setting explicitly set to TRUE. Also don't add
    // anything on empty values.
    if ($this->getSetting('test_cached_data') && !$this->isEmpty()) {
      // Set the additional value so that getValue() will return it.
      $this->additional_key = 'additional_value';
    }
    return $this->getValue();
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

}
