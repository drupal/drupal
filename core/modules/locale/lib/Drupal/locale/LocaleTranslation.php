<?php

/**
 * @file
 * Contains \Drupal\locale\LocaleTranslation.
 */

namespace Drupal\locale;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DestructableInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Lock\LockBackendAbstract;
use Drupal\Core\Lock\LockBackendInterface;
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
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Cached translations
   *
   * @var array
   *   Array of \Drupal\locale\LocaleLookup objects indexed by language code
   *   and context.
   */
  protected $translations = array();

  /**
   * The cache backend that should be used.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The lock backend that should be used.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * Constructs a translator using a string storage.
   *
   * @param \Drupal\locale\StringStorageInterface $storage
   *   Storage to use when looking for new translations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(StringStorageInterface $storage, CacheBackendInterface $cache, LockBackendInterface $lock, ConfigFactory $config_factory) {
    $this->storage = $storage;
    $this->cache = $cache;
    $this->lock = $lock;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getStringTranslation($langcode, $string, $context) {
    // If the language is not suitable for locale module, just return.
    $translate_english = $this->configFactory->get('locale.settings')->get('translate_english');
    if ($langcode == Language::LANGCODE_SYSTEM || ($langcode == 'en' && !$translate_english)) {
      return FALSE;
    }
    // Strings are cached by langcode, context and roles, using instances of the
    // LocaleLookup class to handle string lookup and caching.
    if (!isset($this->translations[$langcode][$context])) {
      $this->translations[$langcode][$context] = new LocaleLookup($langcode, $context, $this->storage, $this->cache, $this->lock);
    }
    $translation = $this->translations[$langcode][$context]->get($string);
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
    foreach ($this->translations as $context) {
      foreach ($context as $lookup) {
        if ($lookup instanceof DestructableInterface) {
          $lookup->destruct();
        }
      }
    }
  }

}
