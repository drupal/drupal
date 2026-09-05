<?php

declare(strict_types=1);

namespace Drupal\field_dynamic_dependencies_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'test_field_dynamic_dependencies' formatter.
 */
#[FieldFormatter(
  id: 'test_field_dynamic_dependencies',
  label: new TranslatableMarkup('Dynamic dependencies test'),
  field_types: ['test_field_dynamic_dependencies'],
  weight: 0,
)]
class TestFieldDynamicDependenciesFormatter extends FormatterBase {

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
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => 'test'];
    }

    return $elements;
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
