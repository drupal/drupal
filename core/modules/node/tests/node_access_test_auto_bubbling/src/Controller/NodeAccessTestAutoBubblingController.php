<?php

/**
 * @file
 * Contains \Drupal\node_access_test_auto_bubbling\Controller\NodeAccessTestAutoBubblingController.
 */

namespace Drupal\node_access_test_auto_bubbling\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns a node ID listing.
 */
class NodeAccessTestAutoBubblingController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The entity query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructs a new NodeAccessTestAutoBubblingController.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query factory.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * Lists the three latest published node IDs.
   *
   * @return array
   *   A render array.
   */
  public function latest() {
    $nids = $this->entityQuery->get('node')
      ->condition('status', NODE_PUBLISHED)
      ->sort('created', 'DESC')
      ->range(0, 3)
      ->execute();
    return ['#markup' => $this->t('The three latest nodes are: @nids.', ['@nids' => implode(', ', $nids)])];
  }

}
