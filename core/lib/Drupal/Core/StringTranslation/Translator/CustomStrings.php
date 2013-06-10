<?php

/**
 * @file
 * Contains \Drupal\Core\StringTranslation\Translator\CustomStrings.
 */

namespace Drupal\Core\StringTranslation\Translator;

/**
 * String translator using overrides from variables.
 *
 * This is a high performance way to provide a handful of string replacements.
 * See settings.php for examples.
 */
class CustomStrings extends StaticTranslation {

  /**
   * {@inheritdoc}
   */
  protected function loadLanguage($langcode) {
    return variable_get('locale_custom_strings_' . $langcode, array());
  }

}
