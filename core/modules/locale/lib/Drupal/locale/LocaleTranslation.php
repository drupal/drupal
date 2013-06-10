<?php

/**
 * @file
 * Contains \Drupal\Core\Language\LocaleTranslation.
 */

namespace Drupal\locale;

use Drupal\Core\DestructableInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\StringTranslation\Translator\TranslatorInterface;
use Drupal\locale\StringStorageInterface;
use Drupal\locale\LocaleLookup;

/**
 * String translator using the locale module.
 *
 * Full featured translation system using locale's string storage and
 * database caching.
 */
class LocaleTranslation implements TranslatorInterface, DestructableInterface {

  /**
   * Storage for strings.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $storage;

  /**
   * Cached translations
   *
   * @var array
   *   Array of \Drupal\locale\LocaleLookup objects indexed by language code
   *   and context.
   */
  protected $translations;

  /**
   * Constructs a translator using a string storage.
   *
   * @param \Drupal\locale\StringStorageInterface $storage
   *   Storage to use when looking for new translations.
   */
  public function __construct(StringStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getStringTranslation($langcode, $string, $context) {
    // If the language is not suitable for locale module, just return.
    if ($langcode == Language::LANGCODE_SYSTEM || ($langcode == 'en' && !variable_get('locale_translate_english', FALSE))) {
      return FALSE;
    }
    // Strings are cached by langcode, context and roles, using instances of the
    // LocaleLookup class to handle string lookup and caching.
    if (!isset($this->translations[$langcode][$context])) {
      $this->translations[$langcode][$context] = new LocaleLookup($langcode, $context, $this->storage);
    }
    $translation = $this->translations[$langcode][$context][$string];
    return $translation === TRUE ? FALSE : $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->translations = array();
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    // @see \Drupal\locale\Locale\Lookup::__destruct().
    // @todo Remove once http://drupal.org/node/1786490 is in.
    foreach ($this->translations as $context) {
      foreach ($context as $lookup) {
        if ($lookup instanceof DestructableInterface) {
          $lookup->destruct();
        }
      }
    }
  }

}
