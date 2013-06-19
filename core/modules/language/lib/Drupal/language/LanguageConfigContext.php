<?php

/**
 * @file
 * Contains \Drupal\language\LanguageConfigContext.
 */

namespace Drupal\language;

use Drupal\Core\Config\Context\ConfigContext;
use Drupal\Core\Language\Language;


/**
 * Defines a configuration context object for a language.
 *
 * This should be used when configuration objects need a context for a language
 * other than the current language.
 *
 */
class LanguageConfigContext extends ConfigContext {

  /**
   * Predefined key for language object.
   */
  const LANGUAGE_KEY = 'language';

  /**
   * Creates the configuration context for language.
   *
   * @param \Drupal\Core\Language\Language $language
   *   The language to add to the config context.
   *
   * @return \Drupal\Core\Language\Language
   *   The language config context object.
   */
  public function setLanguage(Language $language) {
    $this->set(self::LANGUAGE_KEY, $language);
    // Re-initialize since the language change changes the context fundamentally.
    $this->init();
    return $this;
  }

}
