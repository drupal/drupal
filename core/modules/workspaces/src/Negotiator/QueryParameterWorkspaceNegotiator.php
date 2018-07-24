<?php

namespace Drupal\workspaces\Negotiator;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the query parameter workspace negotiator.
 */
class QueryParameterWorkspaceNegotiator extends SessionWorkspaceNegotiator {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return is_string($request->query->get('workspace')) && parent::applies($request);
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveWorkspace(Request $request) {
    $workspace_id = $request->query->get('workspace');

    if ($workspace_id && ($workspace = $this->workspaceStorage->load($workspace_id))) {
      $this->setActiveWorkspace($workspace);
      return $workspace;
    }

    return NULL;
  }

}
