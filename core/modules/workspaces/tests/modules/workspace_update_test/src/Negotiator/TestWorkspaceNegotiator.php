<?php

declare(strict_types=1);

namespace Drupal\workspace_update_test\Negotiator;

use Drupal\workspaces\Entity\Workspace;
use Drupal\workspaces\Negotiator\WorkspaceIdNegotiatorInterface;
use Drupal\workspaces\Negotiator\WorkspaceNegotiatorInterface;
use Drupal\workspaces\WorkspaceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a workspace negotiator used for testing.
 */
class TestWorkspaceNegotiator implements WorkspaceNegotiatorInterface, WorkspaceIdNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveWorkspaceId(Request $request): ?string {
    return 'test';
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveWorkspace(Request $request) {
    return Workspace::load($this->getActiveWorkspaceId($request));
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace) {
    // Nothing to do here.
  }

  /**
   * {@inheritdoc}
   */
  public function unsetActiveWorkspace() {
    // Nothing to do here.
  }

}
