<?php

namespace Drupal\history\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Returns responses for History module routes.
 */
class HistoryController extends ControllerBase {

  /**
   * Returns a set of nodes' last read timestamps.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function getNodeReadTimestamps(Request $request) {
    if ($this->currentUser()->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    if (!$request->request->has('node_ids')) {
      throw new NotFoundHttpException();
    }
    $nids = $request->request->all('node_ids');
    return new JsonResponse(history_read_multiple($nids));
  }

  /**
   * Marks a node as read by the current user right now.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param \Drupal\node\NodeInterface $node
   *   The node whose "last read" timestamp should be updated.
   */
  public function readNode(Request $request, NodeInterface $node) {
    if ($this->currentUser()->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    // Update the history table, stating that this user viewed this node.
    history_write($node->id());

    return new JsonResponse((int) history_read($node->id()));
  }

}
