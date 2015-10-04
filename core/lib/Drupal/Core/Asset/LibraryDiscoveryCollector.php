<?php

/**
 * @file
 * Contains \Drupal\Core\Asset\LibraryDiscoveryCollector.
 */

namespace Drupal\Core\Asset;

use Drupal\Core\Asset\Exception\InvalidLibrariesOverrideSpecificationException;
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
    $this->storage[$key] = $this->getLibraryDefinitions($key);
    $this->persist($key);

    return $this->storage[$key];
  }


  /**
   * Returns the library definitions for a given extension.
   *
   * This also implements libraries-overrides for entire libraries that have
   * been specified by the LibraryDiscoveryParser.
   *
   * @param string $extension
   *   The name of the extension for which library definitions will be returned.
   *
   * @return array
   *   The library definitions for $extension with overrides applied.
   *
   * @throws \Drupal\Core\Asset\Exception\InvalidLibrariesOverrideSpecificationException
   */
  protected function getLibraryDefinitions($extension) {
    $libraries = $this->discoveryParser->buildByExtension($extension);
    foreach ($libraries as $name => $definition) {
      // Handle libraries that are marked for override or removal.
      // @see \Drupal\Core\Asset\LibraryDiscoveryParser::applyLibrariesOverride()
      if (isset($definition['override'])) {
        if ($definition['override'] === FALSE) {
          // Remove the library definition if FALSE is given.
          unset($libraries[$name]);
        }
        else {
          // Otherwise replace with existing library definition if it exists.
          // Throw an exception if it doesn't.
          list($replacement_extension, $replacement_name) = explode('/', $definition['override']);
          $replacement_definition = $this->get($replacement_extension);
          if (isset($replacement_definition[$replacement_name])) {
            $libraries[$name] = $replacement_definition[$replacement_name];
          }
          else {
            throw new InvalidLibrariesOverrideSpecificationException(sprintf('The specified library %s does not exist.', $definition['override']));
          }
        }
      }
    }
    return $libraries;
  }

}
