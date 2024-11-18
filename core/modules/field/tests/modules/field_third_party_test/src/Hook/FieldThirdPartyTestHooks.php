<?php

declare(strict_types=1);

namespace Drupal\field_third_party_test\Hook;

use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for field_third_party_test.
 */
class FieldThirdPartyTestHooks {

  /**
   * Implements hook_field_widget_third_party_settings_form().
   */
  #[Hook('field_widget_third_party_settings_form')]
  public function fieldWidgetThirdPartySettingsForm(WidgetInterface $plugin, FieldDefinitionInterface $field_definition, $form_mode, $form, FormStateInterface $form_state) {
    $element['field_test_widget_third_party_settings_form'] = [
      '#type' => 'textfield',
      '#title' => t('3rd party widget settings form'),
      '#default_value' => $plugin->getThirdPartySetting('field_third_party_test', 'field_test_widget_third_party_settings_form'),
    ];
    return $element;
  }

  /**
   * Implements hook_field_widget_settings_summary_alter().
   */
  #[Hook('field_widget_settings_summary_alter')]
  public function fieldWidgetSettingsSummaryAlter(&$summary, $context): void {
    $summary[] = 'field_test_field_widget_settings_summary_alter';
  }

  /**
   * Implements hook_field_formatter_third_party_settings_form().
   */
  #[Hook('field_formatter_third_party_settings_form')]
  public function fieldFormatterThirdPartySettingsForm(FormatterInterface $plugin, FieldDefinitionInterface $field_definition, $view_mode, $form, FormStateInterface $form_state) {
    $element['field_test_field_formatter_third_party_settings_form'] = [
      '#type' => 'textfield',
      '#title' => t('3rd party formatter settings form'),
      '#default_value' => $plugin->getThirdPartySetting('field_third_party_test', 'field_test_field_formatter_third_party_settings_form'),
    ];
    return $element;
  }

  /**
   * Implements hook_field_formatter_settings_summary_alter().
   */
  #[Hook('field_formatter_settings_summary_alter')]
  public function fieldFormatterSettingsSummaryAlter(&$summary, $context): void {
    $summary[] = 'field_test_field_formatter_settings_summary_alter';
  }

}
