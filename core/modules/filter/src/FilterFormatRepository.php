<?php

declare(strict_types=1);

namespace Drupal\filter;

use Drupal\Core\Cache\BackendChain;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provides a repository service for filter formats.
 */
readonly class FilterFormatRepository implements FilterFormatRepositoryInterface {

  /**
   * Chained cache backend.
   */
  protected BackendChain $cache;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected AccountProxyInterface $currentUser,
    protected LanguageManagerInterface $languageManager,
    #[Autowire(service: 'cache.default')]
    protected CacheBackendInterface $persistentCache,
    #[Autowire(service: 'cache.memory')]
    protected CacheBackendInterface $memoryCache,
  ) {
    $this->cache = (new BackendChain())
      ->appendBackend($memoryCache)
      ->appendBackend($persistentCache);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllFormats(): array {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $cid = "filter_formats:all:$langcode";

    if ($cached = $this->cache->get($cid)) {
      return $cached->data;
    }

    $formats = $this->entityTypeManager->getStorage('filter_format')
      ->loadByProperties(['status' => TRUE]);
    uasort($formats, ConfigEntityBase::class . '::sort');
    $this->cache->set($cid, $formats, Cache::PERMANENT, $this->getCacheTags());

    return $formats;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormatsForAccount(AccountInterface $account): array {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $uid = $account->id();
    $cid = "filter_formats:user:$uid:$langcode";

    if ($cached = $this->memoryCache->get($cid)) {
      return $cached->data;
    }

    $formats = array_filter(
      $this->getAllFormats(),
      fn(FilterFormatInterface $format): bool => $format->access('use', $account),
    );
    $this->memoryCache->set($cid, $formats, Cache::PERMANENT, $this->getCacheTags());

    return $formats;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormatsByRole(string $roleId): array {
    return array_filter(
      $this->getAllFormats(),
      fn(FilterFormatInterface $format): bool => isset($format->getRoles()[$roleId]),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFormat(?AccountInterface $account = NULL): FilterFormatInterface {
    // Get a list of formats for this user, ordered by weight. The first one
    // available is the user's default format.
    $formats = $this->getFormatsForAccount($account ?? $this->currentUser);
    return reset($formats);
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackFormatId(): ?string {
    // This variable is automatically set in the database for all installations
    // of Drupal. In the event that it gets disabled or deleted somehow, there
    // is no safe default to return, since we do not want to risk making an
    // existing (and potentially unsafe) text format on the site automatically
    // available to all users. Returning NULL at least guarantees that this
    // cannot happen.
    return $this->configFactory->get('filter.settings')->get('fallback_format');
  }

  /**
   * Returns the 'filter_format' entity type list cache tags.
   *
   * @return string[]
   *   A list of cache tags.
   */
  protected function getCacheTags(): array {
    return $this->entityTypeManager->getDefinition('filter_format')->getListCacheTags();
  }

}
