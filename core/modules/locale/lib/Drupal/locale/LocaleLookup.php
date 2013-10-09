<?php

/**
 * @file
 * Contains \Drupal\locale\Locale\Lookup.
 */

namespace Drupal\locale;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\DestructableInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * A cache collector to allow for dynamic building of the locale cache.
 */
class LocaleLookup extends CacheCollector {

  /**
   * A language code.
   *
   * @var string
   */
  protected $langcode;

  /**
   * The msgctxt context.
   *
   * @var string
   */
  protected $context;

  /**
   * The locale storage.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $stringStorage;

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
   * Constructs a LocaleLookup object.
   *
   * @param string $langcode
   *   The language code.
   * @param string $context
   *   The string context.
   * @param \Drupal\locale\StringStorageInterface $string_storage
   *   The string storage.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   */
  public function __construct($langcode, $context, StringStorageInterface $string_storage, CacheBackendInterface $cache, LockBackendInterface $lock) {
    $this->langcode = $langcode;
    $this->context = (string) $context;
    $this->stringStorage = $string_storage;

    // Add the current user's role IDs to the cache key, this ensures that, for
    // example, strings for admin menu items and settings forms are not cached
    // for anonymous users.
    $user = \Drupal::currentUser();
    $rids = $user ? implode(':', array_keys($user->getRoles())) : '0';
    parent::__construct("locale:$langcode:$context:$rids", $cache, $lock, array('locale' => TRUE));
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveCacheMiss($offset) {
    $translation = $this->stringStorage->findTranslation(array(
      'language' => $this->langcode,
      'source' => $offset,
      'context' => $this->context,
    ));

    if ($translation) {
      $value = !empty($translation->translation) ? $translation->translation : TRUE;
    }
    else {
      // We don't have the source string, update the {locales_source} table to
      // indicate the string is not translated.
      $this->stringStorage->createString(array(
        'source' => $offset,
        'context' => $this->context,
        'version' => \Drupal::VERSION
      ))->addLocation('path', request_uri())->save();
      $value = TRUE;
    }
    $this->storage[$offset] = $value;
    // Disabling the usage of string caching allows a module to watch for
    // the exact list of strings used on a page. From a performance
    // perspective that is a really bad idea, so we have no user
    // interface for this. Be careful when turning this option off!
    if (\Drupal::config('locale.settings')->get('cache_strings')) {
      $this->persist($offset);
    }
    return $value;
  }

}
