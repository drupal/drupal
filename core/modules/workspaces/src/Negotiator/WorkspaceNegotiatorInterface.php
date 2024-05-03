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
   * Notifies the negotiator that the workspace ID returned has been accepted.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   The negotiated workspace entity.
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace);

  /**
   * Unsets the negotiated workspace.
   */
  public function unsetActiveWorkspace();

}
