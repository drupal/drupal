<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\Query\QueryFactory.
 */

namespace Drupal\Core\Config\Entity\Query;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryException;
use Drupal\Core\Entity\Query\QueryFactoryInterface;

/**
 * Provides a factory for creating entity query objects for the config backend.
 */
class QueryFactory implements QueryFactoryInterface {

  /**
   * The config factory used by the config entity query.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface;
   */
  protected $configFactory;

  /**
   * The namespace of this class, the parent class etc.
   *
   * @var array
   */
  protected $namespaces;

  /**
   * Constructs a QueryFactory object.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage used by the config entity query.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config storage used by the config entity query.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->namespaces = QueryBase::getNamespaces($this);
  }

  /**
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    return new Query($entity_type, $conjunction, $this->configFactory, $this->namespaces);
  }

  /**
   * {@inheritdoc}
   */
   public function getAggregate(EntityTypeInterface $entity_type, $conjunction) {
      throw new QueryException('Aggregation over configuration entities is not supported');
  }

}
