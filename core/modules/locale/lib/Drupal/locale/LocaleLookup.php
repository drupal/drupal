<?php

/**
 * @file
 * Definition of LocaleLookup
 */

namespace Drupal\locale;

use Drupal\Core\Utility\CacheArray;

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
   * Constructs a LocaleCache object.
   */
  public function __construct($langcode, $context) {
    $this->langcode = $langcode;
    $this->context = (string) $context;

    // Add the current user's role IDs to the cache key, this ensures that, for
    // example, strings for admin menu items and settings forms are not cached
    // for anonymous users.
    $rids = implode(':', array_keys($GLOBALS['user']->roles));
    parent::__construct("locale:$langcode:$context:$rids", 'cache');
  }

  /**
   * Overrides DrupalCacheArray::resolveCacheMiss().
   */
  protected function resolveCacheMiss($offset) {
    $translation = db_query("SELECT s.lid, t.translation, s.version FROM {locales_source} s LEFT JOIN {locales_target} t ON s.lid = t.lid AND t.language = :language WHERE s.source = :source AND s.context = :context", array(
      ':language' => $this->langcode,
      ':source' => $offset,
      ':context' => $this->context,
    ))->fetchObject();
    if ($translation) {
      if ($translation->version != VERSION) {
        // This is the first use of this string under current Drupal version.
        // Update the {locales_source} table to indicate the string is current.
        db_update('locales_source')
          ->fields(array('version' => VERSION))
          ->condition('lid', $translation->lid)
          ->execute();
      }
      $value = !empty($translation->translation) ? $translation->translation : TRUE;
    }
    else {
      // We don't have the source string, update the {locales_source} table to
      // indicate the string is not translated.
      db_merge('locales_source')
        ->insertFields(array(
          'location' => request_uri(),
          'version' => VERSION,
        ))
        ->key(array(
          'source' => $offset,
          'context' => $this->context,
        ))
        ->execute();
        $value = TRUE;
    }
    $this->storage[$offset] = $value;
    // Disabling the usage of string caching allows a module to watch for
    // the exact list of strings used on a page. From a performance
    // perspective that is a really bad idea, so we have no user
    // interface for this. Be careful when turning this option off!
    if (variable_get('locale_cache_strings', 1)) {
      $this->persist($offset);
    }
    return $value;
  }
}
