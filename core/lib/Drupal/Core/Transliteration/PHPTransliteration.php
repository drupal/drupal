<?php

/**
 * @file
 * Contains \Drupal\Core\Transliteration\PHPTransliteration.
 */

namespace Drupal\Core\Transliteration;

use Drupal\Component\Transliteration\PHPTransliteration as BaseTransliteration;

/**
 * Enhances PHPTransliteration with an alter hook.
 *
 * @ingroup transliteration
 * @see hook_transliteration_overrides_alter()
 */
class PHPTransliteration extends BaseTransliteration {

  /**
   * Overrides \Drupal\Component\Transliteration\PHPTransliteration::readLanguageOverrides().
   *
   * Allows modules to alter the language-specific $overrides array by invoking
   * hook_transliteration_overrides_alter().
   */
  protected function readLanguageOverrides($langcode) {
    parent::readLanguageOverrides($langcode);

    // Let modules alter the language-specific overrides.
    \Drupal::moduleHandler()->alter('transliteration_overrides', $this->languageOverrides[$langcode], $langcode);
  }

}
