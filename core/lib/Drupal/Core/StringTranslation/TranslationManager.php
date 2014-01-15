<?php

/**
 * @file
 * Contains \Drupal\Core\StringTranslation\TranslationManager.
 */

namespace Drupal\Core\StringTranslation;

use Drupal\Component\Utility\String;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\Translator\TranslatorInterface;

/**
 * Defines a chained translation implementation combining multiple translators.
 */
class TranslationManager implements TranslationInterface, TranslatorInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * An array of active translators keyed by priority.
   *
   * @var array
   *   Array of \Drupal\Core\Translation\Translator\TranslatorInterface objects
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
   * @param \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
    $this->defaultLangcode = $language_manager->getDefaultLanguage()->id;
  }

  /**
   * Initializes the injected language manager with the translation manager.
   *
   * This should be called right after instantiating the translation manager to
   * make it available to the language manager without introducing a circular
   * dependency.
   */
  public function initLanguageManager() {
    $this->languageManager->setTranslation($this);
  }

  /**
   * Appends a translation system to the translation chain.
   *
   * @param \Drupal\Core\Translation\Translator\TranslatorInterface $translator
   *   The translation interface to be appended to the translation chain.
   * @param int $priority
   *   The priority of the logger being added.
   *
   * @return \Drupal\Core\Translation\TranslationManager
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
    // Merge in defaults.
    if (empty($options['langcode'])) {
      $options['langcode'] = $this->defaultLangcode;
    }
    if (empty($options['context'])) {
      $options['context'] = '';
    }
    $translation = $this->getStringTranslation($options['langcode'], $string, $options['context']);
    $string = $translation === FALSE ? $string : $translation;

    if (empty($args)) {
      return $string;
    }
    else {
      return String::format($string, $args);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatPlural($count, $singular, $plural, array $args = array(), array $options = array()) {
    $args['@count'] = $count;
    // Join both forms to search a translation.
    $translatable_string = implode(LOCALE_PLURAL_DELIMITER, array($singular, $plural));
    // Translate as usual.
    $translated_strings = $this->translate($translatable_string, $args, $options);
    // Split joined translation strings into array.
    $translated_array = explode(LOCALE_PLURAL_DELIMITER, $translated_strings);

    if ($count == 1) {
      return $translated_array[0];
    }

    // Get the plural index through the gettext formula.
    // @todo implement static variable to minimize function_exists() usage.
    $index = (function_exists('locale_get_plural')) ? locale_get_plural($count, isset($options['langcode']) ? $options['langcode'] : NULL) : -1;
    if ($index == 0) {
      // Singular form.
      return $translated_array[0];
    }
    else {
      if (isset($translated_array[$index])) {
        // N-th plural form.
        return $translated_array[$index];
      }
      else {
        // If the index cannot be computed or there's no translation, use
        // the second plural form as a fallback (which allows for most flexiblity
        // with the replaceable @count value).
        return $translated_array[1];
      }
    }
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
