<?php

namespace Drupal\locale;

/**
 * Provides the locale default update options.
 *
 * @internal
 */
class LocaleDefaultOptions {

  /**
   * Returns default import options for translation update.
   *
   * @return array
   *   Array of translation import options.
   */
  public static function updateOptions(): array {
    $config = \Drupal::config('locale.settings');
    return [
      'customized' => LOCALE_NOT_CUSTOMIZED,
      'overwrite_options' => [
        'not_customized' => $config->get('translation.overwrite_not_customized'),
        'customized' => $config->get('translation.overwrite_customized'),
      ],
      'finish_feedback' => TRUE,
      'use_remote' => locale_translation_use_remote_source(),
    ];
  }

}
