<?php

/**
 * @file
 * Contains \Drupal\Core\StringTranslation\StringTranslationTrait.
 */

namespace Drupal\Core\StringTranslation;

/**
 * Wrapper methods for \Drupal\Core\StringTranslation\TranslationInterface.
 *
 * Using this trait will add t() and formatPlural() methods to the class. These
 * must be used for every translatable string, similar to how procedural code
 * must use the global functions t() and \Drupal::translation()->formatPlural().
 * This allows string extractor tools to find translatable strings.
 *
 * If the class is capable of injecting services from the container, it should
 * inject the 'string_translation' service and assign it to
 * $this->stringTranslation.
 *
 * @see \Drupal\Core\StringTranslation\TranslationInterface
 * @see container
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
   * Formats a translated string containing a count of items.
   *
   * See the
   * \Drupal\Core\StringTranslation\TranslationInterface::formatPluralTranslated()
   * documentation for details.
   */
  protected function formatPluralTranslated($count, $translated, array $args = array(), array $options = array()) {
    return $this->getStringTranslation()->formatPluralTranslated($count, $translated, $args, $options);
  }

  /**
   * Returns the number of plurals supported by a given language.
   *
   * See the
   * \Drupal\Core\StringTranslation\TranslationInterface::getNumberOfPlurals()
   * documentation for details.
   */
  protected function getNumberOfPlurals($langcode = NULL) {
    return $this->getStringTranslation()->getNumberOfPlurals($langcode);
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
