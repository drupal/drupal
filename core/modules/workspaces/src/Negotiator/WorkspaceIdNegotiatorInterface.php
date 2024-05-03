<?php

namespace Drupal\workspaces\Negotiator;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface for workspace negotiators that return only the negotiated ID.
 */
interface WorkspaceIdNegotiatorInterface {

  /**
   * Performs workspace negotiation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return string|null
   *   A valid workspace ID if the negotiation was successful, NULL otherwise.
   */
  public function getActiveWorkspaceId(Request $request): ?string;

}
