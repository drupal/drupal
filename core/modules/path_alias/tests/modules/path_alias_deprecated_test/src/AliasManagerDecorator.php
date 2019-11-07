<?php

namespace Drupal\path_alias_deprecated_test;

use Drupal\Core\Path\AliasManagerInterface;

/**
 * Test alias manager decorator.
 */
class AliasManagerDecorator implements AliasManagerInterface {

  /**
   * The decorated alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * AliasManagerDecorator constructor.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The decorated alias manager.
   */
  public function __construct(AliasManagerInterface $alias_manager) {
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getPathByAlias($alias, $langcode = NULL) {
    $this->aliasManager->getPathByAlias($alias, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasByPath($path, $langcode = NULL) {
    return $this->aliasManager->getAliasByPath($path, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear($source = NULL) {
    $this->aliasManager->cacheClear($source);
  }

}
