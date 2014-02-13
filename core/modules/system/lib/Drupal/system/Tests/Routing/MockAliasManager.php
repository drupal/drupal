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
   * @var array
   */
  protected $aliases = array();

  /**
   * Array of mocked aliases. Keys are aliases, followed by language.
   *
   * @var array
   */
  protected $systemPaths = array();

  /**
   * An index of aliases that have been requested.
   *
   * @var array
   */
  protected $lookedUp = array();

  /**
   * The language to assume a path alias is for if not specified.
   *
   * @var string
   */
  public $defaultLanguage = 'en';

  /**
   * Adds an alias to the in-memory alias table for this object.
   *
   * @param type $path
   *   The system path of the alias.
   * @param type $alias
   *   The alias of the system path.
   * @param type $path_language
   *   The language of this alias.
   */
  public function addAlias($path, $alias, $path_language = NULL) {
    $language = $path_language ?: $this->defaultLanguage;

    $this->aliases[$path][$language] = $alias;
    $this->systemPaths[$alias][$language] = $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getSystemPath($path, $path_language = NULL) {
    $language = $path_language ?: $this->defaultLanguage;
    return $this->systemPaths[$path][$language];
  }

  /**
   * {@inheritdoc}
   */
  public function getPathAlias($path, $path_language = NULL) {
    $language = $path_language ?: $this->defaultLanguage;
    $this->lookedUp[$path] = 1;
    return $this->aliases[$path][$language];
  }

  /**
   * {@inheritdoc}
   */
  public function getPathLookups() {
    return array_keys($this->lookedUp);
  }

  /**
   * {@inheritdoc}
   */
  public function preloadPathLookups(array $path_list) {
    // Not needed.
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear($source = NULL) {
    // Not needed.
  }
}
