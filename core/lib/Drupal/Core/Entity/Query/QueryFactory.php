<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\QueryFactory.
 */

namespace Drupal\Core\Entity\Query;

use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Factory class Creating entity query objects.
 */
class QueryFactory extends ContainerAware {

  /**
   * Stores the entity manager used by the query.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a QueryFactory object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager used by the query.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Returns a query object for a given entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query object that can query the given entity type.
   */
  public function get($entity_type_id, $conjunction = 'AND') {
    $service_name = $this->entityManager->getStorageController($entity_type_id)->getQueryServicename();
    return $this->container->get($service_name)->get($this->entityManager->getDefinition($entity_type_id), $conjunction);
  }

  /**
   * Returns an aggregated query object for a given entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   *
   * @return \Drupal\Core\Entity\Query\QueryAggregateInterface
   *   The aggregated query object that can query the given entity type.
   */
  public function getAggregate($entity_type_id, $conjunction = 'AND') {
    $service_name = $this->entityManager->getStorageController($entity_type_id)->getQueryServicename();
    return $this->container->get($service_name)->getAggregate($this->entityManager->getDefinition($entity_type_id), $conjunction);
  }

}
