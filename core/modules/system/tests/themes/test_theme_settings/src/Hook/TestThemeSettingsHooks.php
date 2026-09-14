<?php

declare(strict_types=1);

namespace Drupal\test_theme_settings\Hook;

use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;

/**
 * Hook implementations for test_theme_settings.
 */
class TestThemeSettingsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_form_system_theme_settings_alter().
   */
  #[Hook('form_system_theme_settings_alter')]
  public function formSystemThemeSettingsAlter(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\Core\Extension\ThemeSettingsProvider $theme_settings_provider */
    $theme_settings_provider = \Drupal::service(ThemeSettingsProvider::class);
    $form['custom_logo'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Secondary logo.'),
      '#default_value' => $theme_settings_provider->getSetting('custom_logo'),
      '#progress_indicator' => 'bar',
      '#progress_message' => $this->t('Processing...'),
      '#upload_location' => 'public://test',
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'gif png jpg jpeg'],
      ],
    ];

    $form['multi_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Multiple file field with all file extensions'),
      '#multiple' => TRUE,
      '#default_value' => $theme_settings_provider->getSetting('multi_file'),
      '#upload_location' => 'public://test',
      '#upload_validators' => [
        'FileExtension' => [],
      ],
    ];

    $form['#submit'][] = [static::class, 'submitForm'];
  }

  /**
   * Form submission handler for the theme settings form.
   */
  public static function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($file_id = $form_state->getValue(['custom_logo', '0'])) {
      $file = File::load($file_id);
      $file->setPermanent();
      $file->save();
    }

  }

}
