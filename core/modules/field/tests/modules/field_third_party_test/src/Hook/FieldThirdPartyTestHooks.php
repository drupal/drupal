<?php

declare(strict_types=1);

namespace Drupal\field_third_party_test\Hook;

use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element\Number;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for field_third_party_test.
 */
class FieldThirdPartyTestHooks {

  use StringTranslationTrait;

  public function __construct(protected ElementInfoManagerInterface $elementInfoManager) {}

  /**
   * Implements hook_field_widget_third_party_settings_form().
   */
  #[Hook('field_widget_third_party_settings_form')]
  public function fieldWidgetThirdPartySettingsForm(WidgetInterface $plugin, FieldDefinitionInterface $field_definition, $form_mode, $form, FormStateInterface $form_state): array {
    $textfield = $this->elementInfoManager->fromClass(Textfield::class);
    $textfield->title = $this->t('3rd party widget settings form');
    $textfield->default_value = $plugin->getThirdPartySetting('field_third_party_test', 'field_test_widget_third_party_settings_form');
    return $textfield->toRenderable('field_test_widget_third_party_settings_form');
  }

  /**
   * Implements hook_field_widget_third_party_settings_form().
   */
  #[Hook('field_widget_third_party_settings_form')]
  public function fieldWidgetThirdPartySettingsFormAdditionalImplementation(WidgetInterface $plugin, FieldDefinitionInterface $field_definition, $form_mode, $form, FormStateInterface $form_state): array {
    $number = $this->elementInfoManager->fromClass(Number::class);
    $number->title = $this->t('Second 3rd party widget settings form');
    $number->default_value = $plugin->getThirdPartySetting('field_third_party_test', 'second_field_widget_third_party_settings_form');
    return $number->toRenderable('second_field_widget_third_party_settings_form');
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
  public function fieldFormatterThirdPartySettingsForm(FormatterInterface $plugin, FieldDefinitionInterface $field_definition, $view_mode, $form, FormStateInterface $form_state): array {
    $textfield = $this->elementInfoManager->fromClass(Textfield::class);
    $textfield->title = $this->t('3rd party formatter settings form');
    $textfield->default_value = $plugin->getThirdPartySetting('field_third_party_test', 'field_test_field_formatter_third_party_settings_form');
    return $textfield->toRenderable('field_test_field_formatter_third_party_settings_form');
  }

  /**
   * Implements hook_field_formatter_third_party_settings_form().
   */
  #[Hook('field_formatter_third_party_settings_form')]
  public function fieldFormatterThirdPartySettingsFormAdditionalImplementation(FormatterInterface $plugin, FieldDefinitionInterface $field_definition, $view_mode, $form, FormStateInterface $form_state): array {
    $number = $this->elementInfoManager->fromClass(Number::class);
    $number->title = $this->t('Second 3rd party formatter settings form');
    $number->default_value = $plugin->getThirdPartySetting('field_third_party_test', 'second_field_formatter_third_party_settings_form');
    return $number->toRenderable('second_field_formatter_third_party_settings_form');
  }

  /**
   * Implements hook_field_formatter_settings_summary_alter().
   */
  #[Hook('field_formatter_settings_summary_alter')]
  public function fieldFormatterSettingsSummaryAlter(&$summary, $context): void {
    $summary[] = 'field_test_field_formatter_settings_summary_alter';
  }

}
