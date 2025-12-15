<?php

namespace Drupal\history\Controller;

use Drupal\Core\Url;
use Drupal\history\HistoryManager;
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

  /**
   * Returns a set of nodes' last read timestamps.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function renderNewCommentsNodeLinks(Request $request): JsonResponse {
    if (!$this->moduleHandler()->moduleExists('comment')) {
      throw new NotFoundHttpException();
    }

    if ($this->currentUser()->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    if (!$request->request->has('node_ids') || !$request->request->has('field_name')) {
      throw new NotFoundHttpException();
    }
    $nids = $request->request->all('node_ids');
    $field_name = $request->request->get('field_name');

    // Only handle up to 100 nodes.
    $nids = array_slice($nids, 0, 100);

    $links = [];
    foreach ($nids as $nid) {
      $node = $this->entityTypeManager()->getStorage('node')->load($nid);
      $new = \Drupal::service(HistoryManager::class)->getCountNewComments($node);
      $page_number = $this->entityTypeManager()->getStorage('comment')
        ->getNewCommentPageNumber($node->{$field_name}->comment_count, $new, $node, $field_name);
      $query = $page_number ? ['page' => $page_number] : NULL;
      $links[$nid] = [
        'new_comment_count' => (int) $new,
        'first_new_comment_link' => Url::fromRoute(
          'entity.node.canonical',
          ['node' => $node->id()],
          ['query' => $query, 'fragment' => 'new']
        )->toString(),
      ];
    }

    return new JsonResponse($links);
  }

}
