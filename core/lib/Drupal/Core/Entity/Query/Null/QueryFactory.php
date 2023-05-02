<?php

namespace Drupal\Core\Entity\Query\Null;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryFactoryInterface;

/**
 * Provides a factory for creating entity query objects for the null backend.
 */
class QueryFactory implements QueryFactoryInterface {

  /**
   * The namespace of this class, the parent class etc.
   *
   * @var array
   */
  protected $namespaces;

  /**
   * Constructs a QueryFactory object.
   *
   * Initializes the list of namespaces used to locate query
   * classes for different entity types.
   */
  public function __construct() {
    $this->namespaces = QueryBase::getNamespaces($this);
  }

  /**
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    return new Query($entity_type, $conjunction, $this->namespaces);
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregate(EntityTypeInterface $entity_type, $conjunction) {
    return new Query($entity_type, $conjunction, $this->namespaces);
  }

}
