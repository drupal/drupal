<?php

/**
 * @file
 * Contains \Drupal\Core\StringTranslation\StringTranslationTrait.
 */

namespace Drupal\Core\StringTranslation;

/**
 * Wrapper methods for \Drupal\Core\StringTranslation\TranslationInterface.
 *
 * Injected translation can be performed by using a protected method ::t(), so
 * string extractor tools can find all translatable strings. This method must
 * wrap \Drupal\Core\StringTranslation\TranslationInterface::translate().
 * This trait provides this method in a re-usable way.
 *
 * Procedural code must use the global function t(). Any other approach will
 * result in untranslatable strings, because the string extractor will not be
 * able to find them.
 *
 * @ingroup i18n
 */
trait StringTranslationTrait {

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->getStringTranslation()->translate($string, $args, $options);
  }

  /**
   * Formats a string containing a count of items.
   *
   * See the \Drupal\Core\StringTranslation\TranslationInterface::formatPlural()
   * documentation for details.
   */
  protected function formatPlural($count, $singular, $plural, array $args = array(), array $options = array()) {
    return $this->getStringTranslation()->formatPlural($count, $singular, $plural, $args, $options);
  }

  /**
   * Gets the string translation service.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The string translation service.
   */
  protected function getStringTranslation() {
    if (!$this->stringTranslation) {
      $this->stringTranslation = \Drupal::service('string_translation');
    }

    return $this->stringTranslation;
  }

  /**
   * Sets the string translation service to use.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   *
   * @return $this
   */
  public function setStringTranslation(TranslationInterface $translation) {
    $this->stringTranslation = $translation;

    return $this;
  }

}
