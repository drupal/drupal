<?php

namespace Drupal\workspaces;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines the WorkspaceCacheContext service, for "per workspace" caching.
 *
 * Cache context ID: 'workspace'.
 */
class WorkspaceCacheContext implements CacheContextInterface {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a new WorkspaceCacheContext service.
   *
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager) {
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Workspace');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->workspaceManager->hasActiveWorkspace() ? $this->workspaceManager->getActiveWorkspace()->id() : 'live';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($type = NULL) {
    // The active workspace will always be stored in the user's session.
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheContexts(['session']);

    return $cacheability;
  }

}
