<?php

namespace Drupal\system\Tests\Routing;

use Drupal\path_alias\AliasManagerInterface;

/**
 * An easily configurable mock alias manager.
 */
class MockAliasManager implements AliasManagerInterface {

  /**
   * Array of mocked aliases. Keys are system paths, followed by language.
   *
   * @var array
   */
  protected $aliases = [];

  /**
   * Array of mocked aliases. Keys are aliases, followed by language.
   *
   * @var array
   */
  protected $systemPaths = [];

  /**
   * An index of aliases that have been requested.
   *
   * @var array
   */
  protected $lookedUp = [];

  /**
   * The language to assume a path alias is for if not specified.
   *
   * @var string
   */
  public $defaultLanguage = 'en';

  /**
   * Adds an alias to the in-memory alias table for this object.
   *
   * @param string $path
   *   The system path of the alias.
   * @param string $alias
   *   The alias of the system path.
   * @param string $path_language
   *   The language of this alias.
   */
  public function addAlias($path, $alias, $path_language = NULL) {
    $language = $path_language ?: $this->defaultLanguage;

    if ($path[0] !== '/') {
      throw new \InvalidArgumentException('The path needs to start with a slash.');
    }
    if ($alias[0] !== '/') {
      throw new \InvalidArgumentException('The alias needs to start with a slash.');
    }

    $this->aliases[$path][$language] = $alias;
    $this->systemPaths[$alias][$language] = $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getPathByAlias($alias, $langcode = NULL) {
    $langcode = $langcode ?: $this->defaultLanguage;
    return $this->systemPaths[$alias][$langcode];
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasByPath($path, $langcode = NULL) {
    if ($path[0] !== '/') {
      throw new \InvalidArgumentException(sprintf('Source path %s has to start with a slash.', $path));
    }

    $langcode = $langcode ?: $this->defaultLanguage;
    $this->lookedUp[$path] = 1;
    return $this->aliases[$path][$langcode];
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear($source = NULL) {
    // Not needed.
  }

}
