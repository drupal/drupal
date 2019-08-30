<?php

namespace Drupal\workspaces\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on the presence of an active workspace.
 */
class ActiveWorkspaceCheck implements AccessInterface {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a new ActiveWorkspaceCheck.
   *
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager) {
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route) {
    if (!$route->hasRequirement('_has_active_workspace')) {
      return AccessResult::neutral();
    }

    $required_value = filter_var($route->getRequirement('_has_active_workspace'), FILTER_VALIDATE_BOOLEAN);
    return AccessResult::allowedIf($required_value === $this->workspaceManager->hasActiveWorkspace())->addCacheContexts(['workspace']);
  }

}
