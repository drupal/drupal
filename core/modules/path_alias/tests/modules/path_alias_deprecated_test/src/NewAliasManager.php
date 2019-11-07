<?php

namespace Drupal\path_alias_deprecated_test;

use Drupal\Core\Path\AliasManagerInterface;

/**
 * New test implementation for the alias manager.
 */
class NewAliasManager implements AliasManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function getPathByAlias($alias, $langcode = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasByPath($path, $langcode = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear($source = NULL) {
  }

}
