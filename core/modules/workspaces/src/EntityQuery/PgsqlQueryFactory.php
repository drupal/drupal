<?php

namespace Drupal\workspaces\EntityQuery;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\Sql\pgsql\QueryFactory as BaseQueryFactory;
use Drupal\workspaces\WorkspaceManagerInterface;

/**
 * Workspaces PostgreSQL-specific entity query implementation.
 */
class PgsqlQueryFactory extends BaseQueryFactory {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a PgsqlQueryFactory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection used by the entity query.
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   */
  public function __construct(Connection $connection, WorkspaceManagerInterface $workspace_manager) {
    $this->connection = $connection;
    $this->workspaceManager = $workspace_manager;
    $this->namespaces = QueryBase::getNamespaces($this);
  }

  /**
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    $class = QueryBase::getClass($this->namespaces, 'Query');
    return new $class($entity_type, $conjunction, $this->connection, $this->namespaces, $this->workspaceManager);
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregate(EntityTypeInterface $entity_type, $conjunction) {
    $class = QueryBase::getClass($this->namespaces, 'QueryAggregate');
    return new $class($entity_type, $conjunction, $this->connection, $this->namespaces, $this->workspaceManager);
  }

}
