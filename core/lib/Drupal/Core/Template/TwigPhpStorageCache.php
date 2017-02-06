<?php

namespace Drupal\Core\Template;

use Drupal\Component\Utility\Crypt;
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
   * The maximum length for each part of the cache key suffix.
   */
  const SUFFIX_SUBSTRING_LENGTH = 25;

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
   * @param string $twig_cache_prefix
   *   A Twig cache file prefix that changes when Twig extensions change.
   */
  public function __construct(CacheBackendInterface $cache, $twig_cache_prefix) {
    $this->cache = $cache;
    $this->templateCacheFilenamePrefix = $twig_cache_prefix;
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
    if (strpos($name, '{# inline_template_start #}') === 0) {
      // $name is an inline template, and can have characters that are not valid
      // for a filename. $suffix is unique for each inline template so we just
      // use the generic name 'inline-template' here.
      $name = 'inline-template';
    }
    else {
      $name = basename($name);
    }

    // Windows (and some encrypted Linux systems) only support 255 characters in
    // a path. On Windows a requirements error is displayed and installation is
    // blocked if Drupal's public files path is longer than 120 characters.
    // Thus, to always be less than 255, file paths may not be more than 135
    // characters long. Using the default PHP file storage class, the Twig cache
    // file path will be 124 characters long at most, which provides a margin of
    // safety.
    $suffix = substr($name, 0, self::SUFFIX_SUBSTRING_LENGTH) . '_';
    $suffix .= substr(Crypt::hashBase64($className), 0, self::SUFFIX_SUBSTRING_LENGTH);

    // The cache prefix is what gets invalidated.
    return $this->templateCacheFilenamePrefix . '_' . $suffix;
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
