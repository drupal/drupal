<?php

/**
 * @file
 * Contains \Drupal\locale\Locale\Lookup.
 */

namespace Drupal\locale;

use Drupal\Core\DestructableInterface;
use Drupal\Core\Utility\CacheArray;
use Drupal\locale\SourceString;
use Drupal\locale\TranslationString;

/**
 * Extends CacheArray to allow for dynamic building of the locale cache.
 */
class LocaleLookup extends CacheArray implements DestructableInterface {

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
   * Constructs a LocaleCache object.
   */
  public function __construct($langcode, $context, $string_storage) {
    $this->langcode = $langcode;
    $this->context = (string) $context;
    $this->stringStorage = $string_storage;

    // Add the current user's role IDs to the cache key, this ensures that, for
    // example, strings for admin menu items and settings forms are not cached
    // for anonymous users.
    $rids = isset($GLOBALS['user']) ? implode(':', array_keys($GLOBALS['user']->roles)) : '0';
    parent::__construct("locale:$langcode:$context:$rids", 'cache', array('locale' => TRUE));
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
        'version' => VERSION
      ))->addLocation('path', request_uri())->save();
      $value = TRUE;
    }
    $this->storage[$offset] = $value;
    // Disabling the usage of string caching allows a module to watch for
    // the exact list of strings used on a page. From a performance
    // perspective that is a really bad idea, so we have no user
    // interface for this. Be careful when turning this option off!
    if (config('locale.settings')->get('cache_strings')) {
      $this->persist($offset);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    parent::__destruct();
  }

  /**
   * {@inheritdoc}
   */
  public function __destruct() {
    // Do nothing to avoid segmentation faults. This can be restored after the
    // cache collector from http://drupal.org/node/1786490 is used.
  }

}
