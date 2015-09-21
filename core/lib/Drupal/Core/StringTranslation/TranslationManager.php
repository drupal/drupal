<?php

/**
 * @file
 * Contains \Drupal\Core\StringTranslation\TranslationManager.
 */

namespace Drupal\Core\StringTranslation;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\StringTranslation\Translator\TranslatorInterface;

/**
 * Defines a chained translation implementation combining multiple translators.
 */
class TranslationManager implements TranslationInterface, TranslatorInterface {

  /**
   * An array of active translators keyed by priority.
   *
   * @var array
   *   Array of \Drupal\Core\StringTranslation\Translator\TranslatorInterface objects
   */
  protected $translators = array();

  /**
   * Holds the array of translators sorted by priority.
   *
   * If this is NULL a rebuild will be triggered.
   *
   * @var array
   *   An array of path processor objects.
   *
   * @see \Drupal\Core\StringTranslation\TranslationManager::addTranslator()
   * @see \Drupal\Core\StringTranslation\TranslationManager::sortTranslators()
   */
  protected $sortedTranslators = NULL;

  /**
   * The default langcode used in translations.
   *
   * @var string
   *   A language code.
   */
  protected $defaultLangcode;

  /**
   * Constructs a TranslationManager object.
   *
   * @param \Drupal\Core\Language\LanguageDefault $default_language
   *   The default language.
   */
  public function __construct(LanguageDefault $default_language) {
    $this->defaultLangcode = $default_language->get()->getId();
  }

  /**
   * Appends a translation system to the translation chain.
   *
   * @param \Drupal\Core\StringTranslation\Translator\TranslatorInterface $translator
   *   The translation interface to be appended to the translation chain.
   * @param int $priority
   *   The priority of the logger being added.
   *
   * @return \Drupal\Core\StringTranslation\TranslationManager
   *   The called object.
   */
  public function addTranslator(TranslatorInterface $translator, $priority = 0) {
    $this->translators[$priority][] = $translator;
    // Reset sorted translators property to trigger rebuild.
    $this->sortedTranslators = NULL;
    return $this;
  }

  /**
   * Sorts translators according to priority.
   *
   * @return array
   *   A sorted array of translators objects.
   */
  protected function sortTranslators() {
    $sorted = array();
    krsort($this->translators);

    foreach ($this->translators as $translators) {
      $sorted = array_merge($sorted, $translators);
    }
    return $sorted;
  }

  /**
   * {@inheritdoc}
   */
  public function getStringTranslation($langcode, $string, $context) {
    if ($this->sortedTranslators === NULL) {
      $this->sortedTranslators = $this->sortTranslators();
    }
    foreach ($this->sortedTranslators as $translator) {
      $translation = $translator->getStringTranslation($langcode, $string, $context);
      if ($translation !== FALSE) {
        return $translation;
      }
    }
    // No translator got a translation.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function translate($string, array $args = array(), array $options = array()) {
    $safe = TRUE;
    foreach (array_keys($args) as $arg_key) {
      // If the string has arguments that start with '!' we consider it unsafe
      // and return the translation as a string for backward compatibility
      // purposes.
      // @todo https://www.drupal.org/node/2570037 remove this temporary
      // workaround.
      if (0 === strpos($arg_key, '!') && !SafeMarkup::isSafe($args[$arg_key])) {
        $safe = FALSE;
        break;
      }
    }
    $wrapper = new TranslatableString($string, $args, $options, $this);
    return $safe ? $wrapper : (string) $wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function translateString(TranslatableString $translated_string) {
    return $this->doTranslate($translated_string->getUntranslatedString(), $translated_string->getOptions());
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * @param string $string
   *   A string containing the English string to translate.
   * @param array $options
   *   An associative array of additional options, with the following elements:
   *   - 'langcode': The language code to translate to a language other than
   *      what is used to display the page.
   *   - 'context': The context the source string belongs to.
   *
   * @return string
   *   The translated string.
   */
  protected function doTranslate($string, array $options = array()) {
    // Merge in options defaults.
    $options = $options + [
      'langcode' => $this->defaultLangcode,
      'context' => '',
    ];
    $translation = $this->getStringTranslation($options['langcode'], $string, $options['context']);
    return $translation === FALSE ? $string : $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function formatPlural($count, $singular, $plural, array $args = array(), array $options = array()) {
    $safe = TRUE;
    foreach (array_keys($args) as $arg_key) {
      // If the string has arguments that start with '!' we consider it unsafe
      // and return the translation as a string for backward compatibility
      // purposes.
      // @todo https://www.drupal.org/node/2570037 remove this temporary
      // workaround.
      if (0 === strpos($arg_key, '!') && !SafeMarkup::isSafe($args[$arg_key])) {
        $safe = FALSE;
        break;
      }
    }
    $plural = new PluralTranslatableString($count, $singular, $plural, $args, $options, $this);
    return $safe ? $plural : (string) $plural;
  }

  /**
   * Sets the default langcode.
   *
   * @param string $langcode
   *   A language code.
   */
  public function setDefaultLangcode($langcode) {
    $this->defaultLangcode = $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    if ($this->sortedTranslators === NULL) {
      $this->sortedTranslators = $this->sortTranslators();
    }
    foreach ($this->sortedTranslators as $translator) {
      $translator->reset();
    }
  }

}
