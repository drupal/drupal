<?php

namespace Drupal\locale;

use Drupal\locale\Model\TranslationUpdateMode;

/**
 * Provides the locale default update options.
 *
 * @internal
 */
class LocaleDefaultOptions {

  /**
   * Flag for locally not customized interface translation.
   *
   * Such translations are imported from .po files downloaded from
   * localize.drupal.org for example.
   */
  public const NOT_CUSTOMIZED = 0;

  /**
   * Flag for locally customized interface translation.
   *
   * Such translations are edited from their imported originals on the user
   * interface or are imported as customized.
   */
  public const CUSTOMIZED = 1;

  /**
   * Returns default import options for translation update.
   *
   * @return array
   *   Array of translation import options.
   */
  public static function updateOptions(): array {
    $config = \Drupal::config('locale.settings');
    return [
      'customized' => LocaleDefaultOptions::NOT_CUSTOMIZED,
      'overwrite_options' => [
        'not_customized' => $config->get('translation.overwrite_not_customized'),
        'customized' => $config->get('translation.overwrite_customized'),
      ],
      'finish_feedback' => TRUE,
      'use_remote' => $config->get('translation.use_source') == TranslationUpdateMode::RemoteAndLocal->value,
    ];
  }

}
