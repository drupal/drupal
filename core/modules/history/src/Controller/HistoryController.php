<?php

namespace Drupal\history\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * The history repository service.
   *
   * @var \Drupal\history\HistoryRepositoryInterface
   */
  protected $historyRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->historyRepository = $container->get('history.repository');
    return $instance;
  }

  /**
   * Returns a set of nodes' last read timestamps.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function getNodeReadTimestamps(Request $request) {
    if ($this->currentUser()->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    $nids = $request->request->get('node_ids');
    if (!isset($nids)) {
      throw new NotFoundHttpException();
    }
    return new JsonResponse($this->historyRepository->getLastViewed('node', $nids));
  }

  /**
   * Marks a node as read by the current user right now.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param \Drupal\node\NodeInterface $node
   *   The node whose "last read" timestamp should be updated.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function readNode(Request $request, NodeInterface $node) {
    if ($this->currentUser()->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    // Update the history table, stating that this user viewed this node.
    $timestamps = $this->historyRepository
      ->updateLastViewed($node)
      ->getLastViewed('node', [$node->id()]);
    return new JsonResponse($timestamps[$node->id()]);
  }

}
