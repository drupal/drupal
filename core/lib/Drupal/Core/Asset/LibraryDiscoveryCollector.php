<?php

/**
 * @file
 * Contains \Drupal\Core\Asset\LibraryDiscoveryCollector.
 */

namespace Drupal\Core\Asset;

use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * A CacheCollector implementation for building library extension info.
 */
class LibraryDiscoveryCollector extends CacheCollector {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The library discovery parser.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryParser
   */
  protected $discoveryParser;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs a CacheCollector object.
   *
   * @param string $cid
   *   The cid for the array being cached.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\Asset\LibraryDiscoveryParser $discovery_parser
   *   The library discovery parser.
   */
  public function __construct(CacheBackendInterface $cache, LockBackendInterface $lock, LibraryDiscoveryParser $discovery_parser, ThemeManagerInterface $theme_manager) {
    $this->themeManager = $theme_manager;
    parent::__construct(NULL, $cache, $lock, ['library_info']);

    $this->discoveryParser = $discovery_parser;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCid() {
    if (!isset($this->cid)) {
      $this->cid = 'library_info:' . $this->themeManager->getActiveTheme()->getName();
    }

    return $this->cid;
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveCacheMiss($key) {
    $this->storage[$key] = $this->discoveryParser->buildByExtension($key);
    $this->persist($key);

    return $this->storage[$key];
  }
}
