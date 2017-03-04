<?php

namespace Drupal\Core\StringTranslation\Translator;

/**
 * String translator with a static cache for translations.
 *
 * This is a high performance way to provide a handful of string replacements.
 */
class StaticTranslation implements TranslatorInterface {

  /**
   * String translations
   *
   * @var array
   *   Array of cached translations indexed by language and context.
   */
  protected $translations;

  /**
   * Constructs a translator from an array of translations.
   *
   * @param array $translations
   *   Array of override strings indexed by language and context
   */
  public function __construct($translations = []) {
    $this->translations = $translations;
  }

  /**
   * {@inheritdoc}
   */
  public function getStringTranslation($langcode, $string, $context) {
    if (!isset($this->translations[$langcode])) {
      $this->translations[$langcode] = $this->getLanguage($langcode);
    }
    if (isset($this->translations[$langcode][$context][$string])) {
      return $this->translations[$langcode][$context][$string];
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->translations = [];
  }

  /**
   * Retrieves translations for a given language.
   *
   * @param string $langcode
   *   The langcode of the language.
   *
   * @return array
   *   A multidimensional array of translations, indexed by the context the
   *   source string belongs to. The second level is using original strings as
   *   keys. An empty array will be returned when no translations are available.
   */
  protected function getLanguage($langcode) {
    // This class is usually a base class but we do not declare as abstract
    // because it can be used on its own, by passing a simple array on the
    // constructor. This can be useful while testing, but it does not support
    // loading specific languages. All available languages should be passed
    // in the constructor array.
    return [];
  }

}
