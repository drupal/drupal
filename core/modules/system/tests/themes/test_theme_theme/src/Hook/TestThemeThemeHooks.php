<?php

declare(strict_types=1);

namespace Drupal\test_theme_theme\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for test_theme_theme.
 */
class TestThemeThemeHooks {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ThemeSettingsProvider $themeSettingsProvider,
  ) {}

  /**
   * Implements hook_form_system_theme_settings_alter().
   */
  #[Hook('form_system_theme_settings_alter')]
  public function formSystemThemeSettingsAlter(&$form, FormStateInterface $form_state): void {
    $form['custom_logo'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Secondary logo.'),
      '#default_value' => $this->themeSettingsProvider->getSetting('custom_logo'),
      '#progress_indicator' => 'bar',
      '#progress_message' => $this->t('Processing...'),
      '#upload_location' => 'public://test',
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'gif png jpg jpeg',
        ],
      ],
    ];
    $form['#submit'][] = static::class . ':themeSettingsSubmit';
  }

  /**
   * Test theme form settings submission handler.
   */
  public function themeSettingsSubmit(&$form, FormStateInterface $form_state): void {
    if ($file_id = $form_state->getValue(['custom_logo', '0'])) {
      /** @var Drupal\file\Entity\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($file_id);
      $file->setPermanent();
      $file->save();
    }
  }

}
