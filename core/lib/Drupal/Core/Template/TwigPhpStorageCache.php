<?php

namespace Drupal\Core\Template;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\PhpStorage\PhpStorageFactory;

/**
 * Provides an alternate cache storage for Twig using PhpStorage.
 *
 * This class is designed to work on setups with multiple webheads using a local
 * filesystem for the twig cache. When generating the cache key, a hash value
 * depending on the enabled extensions is included. This prevents stale
 * templates from being reused when twig extensions are enabled or disabled.
 *
 * @see \Drupal\Core\DependencyInjection\Compiler\TwigExtensionPass
 */
class TwigPhpStorageCache implements \Twig_CacheInterface {

  /**
   * The cache object used for auto-refresh via mtime.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The PhpStorage object used for storing the templates.
   *
   * @var \Drupal\Component\PhpStorage\PhpStorageInterface
   */
  protected $storage;

  /**
   * The template cache filename prefix.
   *
   * @var string
   */
  protected $templateCacheFilenamePrefix;

  /**
   * Store cache backend and other information internally.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache bin.
   * @param string $twig_extension_hash
   *   The Twig extension hash.
   */
  public function __construct(CacheBackendInterface $cache, $twig_extension_hash) {
    $this->cache = $cache;
    $this->templateCacheFilenamePrefix = $twig_extension_hash;
  }

  /**
   * Gets the PHP code storage object to use for the compiled Twig files.
   *
   * @return \Drupal\Component\PhpStorage\PhpStorageInterface
   */
  protected function storage() {
    if (!isset($this->storage)) {
      $this->storage = PhpStorageFactory::get('twig');
    }
    return $this->storage;
  }

  /**
   * {@inheritdoc}
   */
  public function generateKey($name, $className) {
    $hash = hash('sha256', $className);

    if (strpos($name, '{# inline_template_start #}') === 0) {
      // $name is an inline template, and can have characters that are not valid
      // for a filename. $hash is unique for each inline template so we just use
      // the generic name 'inline-template' here.
      $name = 'inline-template';
    }
    else {
      $name = basename($name);
    }

    // The first part is what is invalidated.
    return $this->templateCacheFilenamePrefix . '_' . $name . '_' . $hash;
  }

  /**
   * {@inheritdoc}
   */
  public function load($key) {
    $this->storage()->load($key);
  }

  /**
   * {@inheritdoc}
   */
  public function write($key, $content) {
    $this->storage()->save($key, $content);
    // Save the last mtime.
    $cid = 'twig:' . $key;
    $this->cache->set($cid, REQUEST_TIME);
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestamp($key) {
    $cid = 'twig:' . $key;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }
    else {
      return 0;
    }
  }

}
