<?php

/**
 * @file
 * Contains \Drupal\field_test\Plugin\Field\FieldWidget\TestFieldWidgetMultiple.
 */

namespace Drupal\field_test\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'test_field_widget_multiple' widget.
 *
 * The 'field_types' entry is left empty, and is populated through
 * hook_field_widget_info_alter().
 *
 * @see field_test_field_widget_info_alter()
 *
 * @FieldWidget(
 *   id = "test_field_widget_multiple",
 *   label = @Translation("Test widget - multiple"),
 *   multiple_values = TRUE,
 *   weight = 10
 * )
 */
class TestFieldWidgetMultiple extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'test_widget_setting_multiple' => 'dummy test string',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['test_widget_setting_multiple'] = array(
      '#type' => 'textfield',
      '#title' => t('Field test field widget setting'),
      '#description' => t('A dummy form element to simulate field widget setting.'),
      '#default_value' => $this->getSetting('test_widget_setting_multiple'),
      '#required' => FALSE,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('@setting: @value', array('@setting' => 'test_widget_setting_multiple', '@value' => $this->getSetting('test_widget_setting_multiple')));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $values = array();
    foreach ($items as $item) {
      $values[] = $item->value;
    }
    $element += array(
      '#type' => 'textfield',
      '#default_value' => implode(', ', $values),
      '#element_validate' => array(array(get_class($this), 'multipleValidate')),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element;
  }

  /**
   * Element validation helper.
   */
  public static function multipleValidate($element, FormStateInterface $form_state) {
    $values = array_map('trim', explode(',', $element['#value']));
    $items = array();
    foreach ($values as $value) {
      $items[] = array('value' => $value);
    }
    $form_state->setValueForElement($element, $items);
  }

  /**
   * {@inheritdoc}
   * Used in \Drupal\field\Tests\EntityReference\EntityReferenceAdminTest::testAvailableFormatters().
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Returns FALSE if machine name of the field equals field_onewidgetfield.
    return $field_definition->getName() != "field_onewidgetfield";
  }

}
