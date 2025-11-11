<?php

declare(strict_types=1);

namespace Drupal\ckeditor5;

use Drupal\Core\Cache\BackendChain;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Manages language mappings and discovery for ckeditor translations.
 */
class LanguageMapper {

  /**
   * Cache backend.
   */
  protected BackendChain $cache;

  public function __construct(
    #[Autowire('@cache.discovery')]
    CacheBackendInterface $persistent_cache,
    #[Autowire('@cache.memory')]
    CacheBackendInterface $memory_cache,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
    $this->cache = new BackendChain();
    $this->cache->appendBackend($memory_cache);
    $this->cache->appendBackend($persistent_cache);
  }

  /**
   * Returns a list of language codes supported by CKEditor 5.
   *
   * @return array
   *   The CKEditor 5 language codes.
   */
  public function getMappings(): array {
    // Cache the file system based language list calculation because this would
    // be expensive to calculate all the time. The cache is cleared on core
    // upgrades which is the only situation the CKEditor file listing should
    // change.
    $langcode_cache = $this->cache->get('ckeditor5.langcodes');
    if (!empty($langcode_cache)) {
      $langcodes = $langcode_cache->data;
    }
    if (empty($langcodes)) {
      $langcodes = [];
      // Collect languages included with CKEditor 5 based on file listing.
      $files = scandir('core/assets/vendor/ckeditor5/ckeditor5-dll/translations');
      foreach ($files as $file) {
        if (str_ends_with($file, '.js')) {
          $langcode = basename($file, '.js');
          $langcodes[$langcode] = $langcode;
        }
      }

      $this->cache->set('ckeditor5.langcodes', $langcodes);
    }

    // Get language mapping if available to map to Drupal language codes.
    // This is configurable in the user interface and not expensive to get, so
    // we don't include it in the cached language list.
    $language_mappings = $this->moduleHandler->moduleExists('language') ? language_get_browser_drupal_langcode_mappings() : [];
    foreach ($langcodes as $langcode) {
      // If this language code is available in a Drupal mapping, use that to
      // compute a possibility for matching from the Drupal langcode to the
      // CKEditor langcode.
      // For instance, CKEditor uses the langcode 'no' for Norwegian, Drupal
      // uses 'nb'. This would then remove the 'no' => 'no' mapping and
      // replace it with 'nb' => 'no'. Now Drupal knows which CKEditor
      // translation to load.
      if (isset($language_mappings[$langcode]) && !isset($langcodes[$language_mappings[$langcode]])) {
        $langcodes[$language_mappings[$langcode]] = $langcode;
        unset($langcodes[$langcode]);
      }
    }
    return $langcodes;
  }

  /**
   * Returns a specific ckeditor langcode based on the requested one.
   *
   * @param string $langcode
   *   The Drupal langcode to match.
   *
   * @return string
   *   The associated CKEditor 5 langcode.
   */
  public function getMapping(string $langcode): string {
    return $this->getMappings()[$langcode] ?? 'en';
  }

}
