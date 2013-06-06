<?php

/**
 * @file
 * Definition of LocaleLookup
 */

namespace Drupal\locale;

use Drupal\Core\Utility\CacheArray;
use Drupal\locale\SourceString;
use Drupal\locale\TranslationString;

/**
 * Extends CacheArray to allow for dynamic building of the locale cache.
 */
class LocaleLookup extends CacheArray {

  /**
   * A language code.
   * @var string
   */
  protected $langcode;

  /**
   * The msgctxt context.
   * @var string
   */
  protected $context;

  /**
   * The locale storage
   *
   * @var Drupal\locale\StringStorageInterface
   */
  protected $stringStorage;

  /**
   * Constructs a LocaleCache object.
   */
  public function __construct($langcode, $context, $stringStorage) {
    $this->langcode = $langcode;
    $this->context = (string) $context;
    $this->stringStorage = $stringStorage;

    // Add the current user's role IDs to the cache key, this ensures that, for
    // example, strings for admin menu items and settings forms are not cached
    // for anonymous users.
    $rids = implode(':', $GLOBALS['user']->roles);
    parent::__construct("locale:$langcode:$context:$rids", 'cache', array('locale' => TRUE));
  }

  /**
   * Implements CacheArray::resolveCacheMiss().
   */
  protected function resolveCacheMiss($offset) {
    $translation = $this->stringStorage->findTranslation(array(
      'language' => $this->langcode,
      'source' => $offset,
      'context' => $this->context
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
}
