<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\QueryFactoryInterface.
 */

namespace Drupal\Core\Entity\Query;

use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Defines an interface for QueryFactory classes.
 */
interface QueryFactoryInterface {

  /**
   * Instantiates an entity query for a given entity type.
   *
   * @param string $entity_type
   *   The entity type for the query.
   * @param string $conjunction
   *   The operator to use to combine conditions: 'AND' or 'OR'.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager that handles the entity type.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager that handles the entity type.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query for a specific configuration entity type.
   */
  public function get($entity_type, $conjunction, EntityManagerInterface $entity_manager);

  /**
   * Returns a aggregation query object for a given entity type.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager that handles the entity type.
   *
   * @throws \Drupal\Core\Entity\Query\QueryException
   * @return \Drupal\Core\Entity\Query\QueryAggregateInterface
   *   The query object that can query the given entity type.
   */
  public function getAggregate($entity_type, $conjunction, EntityManagerInterface $entity_manager);

}
