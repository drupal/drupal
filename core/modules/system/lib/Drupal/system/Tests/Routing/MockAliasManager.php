<?php

/**
 * @file
 * Contains Drupal\system\Tests\Routing\MockAliasManager.
 */

namespace Drupal\system\Tests\Routing;

use Drupal\Core\Path\AliasManagerInterface;

/**
 * An easily configurable mock alias manager.
 */
class MockAliasManager implements AliasManagerInterface {

  /**
   * Array of mocked aliases. Keys are system paths, followed by language.
   *
   * @var type
   */
  protected $aliases = array();

  protected $systemPaths = array();

  protected $lookedUp = array();

  public $defaultLanguage = 'en';

  public function addAlias($path, $alias, $path_language = NULL) {
    $language = $path_language ?: $this->defaultLanguage;

    $this->aliases[$path][$language] = $alias;
    $this->systemPaths[$alias][$language] = $path;
  }

  public function getSystemPath($path, $path_language = NULL) {
    $language = $path_language ?: $this->defaultLanguage;
    return $this->systemPaths[$path][$language];
  }

  public function getPathAlias($path, $path_language = NULL) {
    $language = $path_language ?: $this->defaultLanguage;
    $this->lookedUp[$path] = 1;
    return $this->aliases[$path][$language];
  }

  public function getPathLookups() {
    return array_keys($this->lookedUp);
  }

  public function preloadPathLookups(array $path_list) {
    // Not needed.
  }
}
