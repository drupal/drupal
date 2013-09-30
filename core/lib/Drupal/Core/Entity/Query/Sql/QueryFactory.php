<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\Sql\QueryFactory.
 */

namespace Drupal\Core\Entity\Query\Sql;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Query\QueryBase;
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
   * The namespace of this class, the parent class etc.
   *
   * @var array
   */
  protected $namespaces;

  /**
   * Constructs a QueryFactory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection used by the entity query.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
    $this->namespaces = QueryBase::getNamespaces($this);
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
  public function get($entity_type, $conjunction, EntityManager $entity_manager) {
    $class = QueryBase::getClass($this->namespaces, 'Query');
    return new $class($entity_type, $entity_manager, $conjunction, $this->connection, $this->namespaces);
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
  public function getAggregate($entity_type, $conjunction, EntityManager $entity_manager) {
    $class = QueryBase::getClass($this->namespaces, 'QueryAggregate');
    return new $class($entity_type, $entity_manager, $conjunction, $this->connection, $this->namespaces);
  }

}
