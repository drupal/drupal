<?php

namespace Drupal\content_translation\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for content_translation.
 */
class ContentTranslationThemeHooks {

  /**
   * Implements hook_preprocess_HOOK() for language-content-settings-table.html.twig.
   */
  #[Hook('preprocess_language_content_settings_table')]
  public function preprocessLanguageContentSettingsTable(&$variables): void {
    \Drupal::moduleHandler()->loadInclude('content_translation', 'inc', 'content_translation.admin');
    _content_translation_preprocess_language_content_settings_table($variables);
  }

}
