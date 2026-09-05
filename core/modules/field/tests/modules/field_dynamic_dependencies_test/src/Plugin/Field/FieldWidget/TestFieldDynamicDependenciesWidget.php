<?php

declare(strict_types=1);

namespace Drupal\field_dynamic_dependencies_test\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'test_field_dynamic_dependencies' widget.
 */
#[FieldWidget(
  id: 'test_field_dynamic_dependencies',
  label: new TranslatableMarkup('Test widget with dynamic dependencies'),
  field_types: ['test_field_dynamic_dependencies'],
  weight: 20,
)]
class TestFieldDynamicDependenciesWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'dependent_module' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['dependent_module'] = [
      '#title' => $this->t('Module'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('dependent_module'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('@setting: @value', [
      '@setting' => 'dependent_module',
      '@value' => $this->getSetting('dependent_module') ?? '',
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->value ?? '',
    ];
    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $dependencies['module'][] = $this->getSetting('dependent_module');
    return $dependencies;
  }

}
