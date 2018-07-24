<?php

namespace Drupal\workspaces\Negotiator;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workspaces\WorkspaceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the default workspace negotiator.
 */
class DefaultWorkspaceNegotiator implements WorkspaceNegotiatorInterface {

  /**
   * The workspace storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $workspaceStorage;

  /**
   * The default workspace entity.
   *
   * @var \Drupal\workspaces\WorkspaceInterface
   */
  protected $defaultWorkspace;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->workspaceStorage = $entity_type_manager->getStorage('workspace');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveWorkspace(Request $request) {
    if (!$this->defaultWorkspace) {
      $default_workspace = $this->workspaceStorage->create([
        'id' => WorkspaceInterface::DEFAULT_WORKSPACE,
        'label' => Unicode::ucwords(WorkspaceInterface::DEFAULT_WORKSPACE),
      ]);
      $default_workspace->enforceIsNew(FALSE);

      $this->defaultWorkspace = $default_workspace;
    }

    return $this->defaultWorkspace;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace) {}

}
