<?php

namespace Drupal\workspaces\Negotiator;

use Drupal\Core\Session\AccountInterface;
use Drupal\workspaces\WorkspaceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Defines the session workspace negotiator.
 */
class SessionWorkspaceNegotiator implements WorkspaceNegotiatorInterface, WorkspaceIdNegotiatorInterface {

  public function __construct(
    protected readonly AccountInterface $currentUser,
    protected readonly SessionInterface $session,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    // This negotiator only applies if the current user is authenticated.
    return $this->currentUser->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveWorkspaceId(Request $request): ?string {
    return $this->session->get('active_workspace_id');
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace) {
    $this->session->set('active_workspace_id', $workspace->id());
  }

  /**
   * {@inheritdoc}
   */
  public function unsetActiveWorkspace() {
    $this->session->remove('active_workspace_id');
  }

}
