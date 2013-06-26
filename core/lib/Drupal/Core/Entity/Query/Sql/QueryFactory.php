<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\Sql\QueryFactory.
 */

namespace Drupal\Core\Entity\Query\Sql;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Query\QueryFactoryInterface;

/**
 * Factory class creating entity query objects for the SQL backend.
 *
 * @see \Drupal\Core\Entity\Query\Sql\Query
 * @see \Drupal\Core\Entity\Query\Sql\QueryAggregate
 */
class QueryFactory implements QueryFactoryInterface {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a QueryFactory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection used by the entity query.
   */
  function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Constructs a entity query for a certain entity type.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   *
   * @return \Drupal\Core\Entity\Query\Sql\Query
   *   The factored query.
   */
  function get($entity_type, $conjunction, EntityManager $entity_manager) {
    return new Query($entity_type, $entity_manager, $conjunction, $this->connection);
  }

  /**
   * Constructs a entity aggregation query for a certain entity type.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   *
   * @return \Drupal\Core\Entity\Query\Sql\QueryAggregate
   *   The factored aggregation query.
   */
  function getAggregate($entity_type, $conjunction, EntityManager $entity_manager) {
    return new QueryAggregate($entity_type, $entity_manager, $conjunction, $this->connection);
  }

}
