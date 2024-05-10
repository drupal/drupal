<?php

namespace Drupal\workspaces\Negotiator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workspaces\WorkspaceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Defines the session workspace negotiator.
 */
class SessionWorkspaceNegotiator implements WorkspaceNegotiatorInterface, WorkspaceIdNegotiatorInterface {

  public function __construct(
    protected readonly AccountInterface $currentUser,
    protected readonly Session $session,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
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
  public function getActiveWorkspace(Request $request) {
    $workspace_id = $this->getActiveWorkspaceId($request);

    if ($workspace_id && ($workspace = $this->entityTypeManager->getStorage('workspace')->load($workspace_id))) {
      return $workspace;
    }

    return NULL;
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
