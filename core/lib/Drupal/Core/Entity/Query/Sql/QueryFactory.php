<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\Sql\QueryFactory.
 */

namespace Drupal\Core\Entity\Query\Sql;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeInterface;
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
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    $class = QueryBase::getClass($this->namespaces, 'Query');
    return new $class($entity_type, $conjunction, $this->connection, $this->namespaces);
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregate(EntityTypeInterface $entity_type, $conjunction) {
    $class = QueryBase::getClass($this->namespaces, 'QueryAggregate');
    return new $class($entity_type, $conjunction, $this->connection, $this->namespaces);
  }

}
