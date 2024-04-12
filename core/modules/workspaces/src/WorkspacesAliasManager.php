<?php

declare(strict_types=1);

namespace Drupal\workspaces;

use Drupal\path_alias\AliasManagerInterface;

/**
 * Decorates the path_alias.manager service for workspace-specific caching.
 *
 * @internal
 */
class WorkspacesAliasManager implements AliasManagerInterface {

  public function __construct(
    protected readonly AliasManagerInterface $inner,
    protected readonly WorkspaceManagerInterface $workspaceManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function setCacheKey($key): void {
    if ($this->workspaceManager->hasActiveWorkspace()) {
      $key = $this->workspaceManager->getActiveWorkspace()->id() . ':' . $key;
    }
    $this->inner->setCacheKey($key);
  }

  /**
   * {@inheritdoc}
   */
  public function writeCache(): void {
    $this->inner->writeCache();
  }

  /**
   * {@inheritdoc}
   */
  public function getPathByAlias($alias, $langcode = NULL): string {
    return $this->inner->getPathByAlias($alias, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasByPath($path, $langcode = NULL): string {
    return $this->inner->getAliasByPath($path, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear($source = NULL): void {
    $this->inner->cacheClear($source);
  }

}
