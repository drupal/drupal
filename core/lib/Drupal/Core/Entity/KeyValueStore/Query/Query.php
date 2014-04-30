<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\KeyValueStore\Query\Query.
 */

namespace Drupal\Core\Entity\KeyValueStore\Query;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * Defines the entity query for entities stored in a key value backend.
 */
class Query extends QueryBase {

  /**
   * The key value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueFactory;

  /**
   * Constructs a new Query.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   * @param array $namespaces
   *   List of potential namespaces of the classes belonging to this query.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key value factory.
   */
  public function __construct(EntityTypeInterface $entity_type, $conjunction, array $namespaces, KeyValueFactoryInterface $key_value_factory) {
    parent::__construct($entity_type, $conjunction, $namespaces);
    $this->keyValueFactory = $key_value_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Load the relevant records.
    $records = $this->keyValueFactory->get('entity_storage__' . $this->entityTypeId)->getAll();

    // Apply conditions.
    $result = $this->condition->compile($records);

    // Apply sort settings.
    foreach ($this->sort as $sort) {
      $direction = $sort['direction'] == 'ASC' ? -1 : 1;
      $field = $sort['field'];
      uasort($result, function($a, $b) use ($field, $direction) {
        return ($a[$field] <= $b[$field]) ? $direction : -$direction;
      });
    }

    // Let the pager do its work.
    $this->initializePager();

    if ($this->range) {
      $result = array_slice($result, $this->range['start'], $this->range['length'], TRUE);
    }
    if ($this->count) {
      return count($result);
    }

    // Create the expected structure of entity_id => entity_id.
    $entity_ids = array_keys($result);
    return array_combine($entity_ids, $entity_ids);
  }

}
