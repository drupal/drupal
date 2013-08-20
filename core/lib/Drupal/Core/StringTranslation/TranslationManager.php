<?php

/**
 * @file
 * Contains \Drupal\Core\StringTranslation\TranslationManager.
 */

namespace Drupal\Core\StringTranslation;

use Drupal\Core\StringTranslation\Translator\TranslatorInterface;
use Drupal\Component\Utility\String;

/**
 * Defines a chained translation implementation combining multiple translators.
 */
class TranslationManager implements TranslationInterface, TranslatorInterface {

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
   */
  public function __construct() {
    // @todo Inject language_manager or config system after language_default
    //   variable is converted to CMI.
    $this->defaultLangcode = language_default()->id;
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
