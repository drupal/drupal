<?php

namespace Drupal\workspaces\Negotiator;

use Drupal\workspaces\WorkspaceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Workspace negotiators provide a way to get the active workspace.
 *
 * \Drupal\workspaces\WorkspaceManager acts as the service collector for
 * Workspace negotiators.
 */
interface WorkspaceNegotiatorInterface {

  /**
   * Checks whether the negotiator applies to the current request or not.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return bool
   *   TRUE if the negotiator applies for the current request, FALSE otherwise.
   */
  public function applies(Request $request);

  /**
   * Gets the negotiated workspace, if any.
   *
   * Note that it is the responsibility of each implementation to check whether
   * the negotiated workspace actually exists in the storage.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Drupal\workspaces\WorkspaceInterface|null
   *   The negotiated workspace or NULL if the negotiator could not determine a
   *   valid workspace.
   */
  public function getActiveWorkspace(Request $request);

  /**
   * Sets the negotiated workspace.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   The workspace entity.
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace);

}
