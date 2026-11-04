<?php

namespace Drupal\locale;

use Drupal\Component\Gettext\PoItem;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A cache collector to allow for dynamic building of the locale cache.
 */
class LocaleLookup extends CacheCollector {

  /**
   * The msgctxt context.
   *
   * @var string
   */
  protected $context;

  /**
   * Constructs a LocaleLookup object.
   *
   * @param string $langcode
   *   The language code.
   * @param string $context
   *   The string context.
   * @param \Drupal\locale\StringStorageInterface $stringStorage
   *   The string storage.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected $langcode,
    $context,
    protected StringStorageInterface $stringStorage,
    protected CacheBackendInterface $cache,
    protected LockBackendInterface $lock,
    protected ConfigFactoryInterface $configFactory,
    protected LanguageManagerInterface $languageManager,
    protected RequestStack $requestStack,
  ) {
    $this->context = (string) $context;
    $this->tags = ['locale'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCid() {
    if (!isset($this->cid)) {
      // Add the current user's role IDs to the cache key, this ensures that,
      // for example, strings for admin menu items and settings forms are not
      // cached for anonymous users.
      $user = \Drupal::currentUser();
      $rids = '';
      if ($user) {
        $roles = $user->getRoles();
        sort($roles);
        $rids = implode(':', $roles);
      }
      $this->cid = "locale:{$this->langcode}:{$this->context}:$rids";

      // Getting the roles from the current user might have resulted in t()
      // calls that attempted to get translations from the locale cache. In that
      // case they would not go into this method again as
      // CacheCollector::lazyLoadCache() already set the loaded flag. They would
      // however call resolveCacheMiss() and add that string to the list of
      // cache misses that need to be written into the cache. Prevent that by
      // resetting that list. All that happens in such a case are a few uncached
      // translation lookups.
      $this->keysToPersist = [];
    }
    return $this->cid;
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveCacheMiss($offset) {
    $translation = $this->stringStorage->findTranslation([
      'language' => $this->langcode,
      'source' => $offset,
      'context' => $this->context,
    ]);

    if ($translation) {
      $value = !empty($translation->translation) ? $translation->translation : TRUE;
    }
    else {
      // We don't have the source string, update the {locales_source} table to
      // indicate the string is not translated.
      $this->stringStorage->createString([
        'source' => $offset,
        'context' => $this->context,
        'version' => \Drupal::VERSION,
      ])->addLocation('path', $this->requestStack->getCurrentRequest()->getRequestUri())->save();
      $value = TRUE;
    }

    // If there is no translation available for the current language then use
    // language fallback to try other translations.
    if ($value === TRUE) {
      $fallbacks = $this->languageManager->getFallbackCandidates([
        'langcode' => $this->langcode,
        'operation' => 'locale_lookup',
        'data' => $offset,
      ]);
      if (!empty($fallbacks)) {
        foreach ($fallbacks as $langcode) {
          $translation = $this->stringStorage->findTranslation([
            'language' => $langcode,
            'source' => $offset,
            'context' => $this->context,
          ]);

          if ($translation && !empty($translation->translation)) {
            $value = $translation->translation;
            break;
          }
        }
      }
    }

    if (is_string($value) && str_contains($value, PoItem::DELIMITER)) {
      // Community translations imported from localize.drupal.org as well as
      // migrated translations may contain @count[number].
      $value = preg_replace('!@count\[\d+\]!', '@count', $value);
    }

    $this->storage[$offset] = $value;
    // Disabling the usage of string caching allows a module to watch for
    // the exact list of strings used on a page. From a performance
    // perspective that is a really bad idea, so we have no user
    // interface for this. Be careful when turning this option off!
    if ($this->configFactory->get('locale.settings')->get('cache_strings')) {
      $this->persist($offset);
    }
    return $value;
  }

}
