<?php

/**
 * @file
 * Contains \Drupal\Core\StringTranslation\TranslationManager.
 */

namespace Drupal\Core\StringTranslation;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\StateInterface;
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
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a TranslationManager object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   (optional) The state service.
   */
  public function __construct(LanguageManagerInterface $language_manager, StateInterface $state = NULL) {
    $this->languageManager = $language_manager;
    $this->defaultLangcode = $language_manager->getDefaultLanguage()->getId();
    $this->state = $state;
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
    $wrapper = new TranslationWrapper($string, $args, $options, $this);
    return $safe ? $wrapper : (string) $wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function translateString(TranslationWrapper $translated_string) {
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
    $translatable_string = implode(LOCALE_PLURAL_DELIMITER, array($singular, $plural));
    $translated_strings = $this->doTranslate($translatable_string, $options);
    return $this->formatPluralTranslated($count, $translated_strings, $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function formatPluralTranslated($count, $translation, array $args = array(), array $options = array()) {
    $args['@count'] = $count;
    $translated_array = explode(LOCALE_PLURAL_DELIMITER, $translation);

    if ($count == 1) {
      return SafeMarkup::format($translated_array[0], $args);
    }

    // Get the plural index through the gettext formula.
    // @todo implement static variable to minimize function_exists() usage.
    $index = (function_exists('locale_get_plural')) ? locale_get_plural($count, isset($options['langcode']) ? $options['langcode'] : NULL) : -1;
    if ($index == 0) {
      // Singular form.
      $return = $translated_array[0];
    }
    else {
      if (isset($translated_array[$index])) {
        // N-th plural form.
        $return = $translated_array[$index];
      }
      else {
        // If the index cannot be computed or there's no translation, use
        // the second plural form as a fallback (which allows for most flexibility
        // with the replaceable @count value).
        $return = $translated_array[1];
      }
    }

    return SafeMarkup::format($return, $args);
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

  /**
   * @inheritdoc.
   */
  public function getNumberOfPlurals($langcode = NULL) {
    // If the state service is not injected, we assume 2 plural variants are
    // allowed. This may happen in the installer for simplicity. We also assume
    // 2 plurals if there is no explicit information yet.
    if (isset($this->state)) {
      $langcode = $langcode ?: $this->languageManager->getCurrentLanguage()->getId();
      $plural_formulas = $this->state->get('locale.translation.plurals') ?: array();
      if (isset($plural_formulas[$langcode]['plurals'])) {
        return $plural_formulas[$langcode]['plurals'];
      }
    }
    return 2;
  }

}
