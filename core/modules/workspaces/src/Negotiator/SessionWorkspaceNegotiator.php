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
class SessionWorkspaceNegotiator implements WorkspaceNegotiatorInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * The workspace storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $workspaceStorage;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   The session.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(AccountInterface $current_user, Session $session, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->session = $session;
    $this->workspaceStorage = $entity_type_manager->getStorage('workspace');
  }

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
  public function getActiveWorkspace(Request $request) {
    $workspace_id = $this->session->get('active_workspace_id');

    if ($workspace_id && ($workspace = $this->workspaceStorage->load($workspace_id))) {
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
