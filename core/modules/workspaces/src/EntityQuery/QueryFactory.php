<?php

namespace Drupal\workspaces\EntityQuery;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\Sql\QueryFactory as BaseQueryFactory;
use Drupal\workspaces\WorkspaceInformationInterface;
use Drupal\workspaces\WorkspaceManagerInterface;

/**
 * Workspaces-specific entity query implementation.
 *
 * @internal
 */
class QueryFactory extends BaseQueryFactory {

  public function __construct(
    Connection $connection,
    protected readonly WorkspaceManagerInterface $workspaceManager,
    protected readonly WorkspaceInformationInterface $workspaceInfo,
  ) {
    $this->connection = $connection;
    $this->namespaces = QueryBase::getNamespaces($this);
  }

  /**
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    $class = QueryBase::getClass($this->namespaces, 'Query');
    return new $class($entity_type, $conjunction, $this->connection, $this->namespaces, $this->workspaceManager, $this->workspaceInfo);
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregate(EntityTypeInterface $entity_type, $conjunction) {
    $class = QueryBase::getClass($this->namespaces, 'QueryAggregate');
    return new $class($entity_type, $conjunction, $this->connection, $this->namespaces, $this->workspaceManager, $this->workspaceInfo);
  }

}
