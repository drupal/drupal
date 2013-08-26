<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\Query\Query.
 */

namespace Drupal\Core\Config\Entity\Query;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Defines the entity query for configuration entities.
 */
class Query extends QueryBase implements QueryInterface {

  /**
   * Stores the entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The config storage used by the config entity query.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * Constructs a Query object.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager that stores all meta information.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The actual config storage which is used to list all config items.
   */
  function __construct($entity_type, $conjunction, EntityManager $entity_manager, StorageInterface $config_storage) {
    parent::__construct($entity_type, $conjunction);
    $this->entityManager = $entity_manager;
    $this->configStorage = $config_storage;
  }

  /**
   * Overrides \Drupal\Core\Entity\Query\QueryBase::condition().
   *
   * Additional to the syntax defined in the QueryInterface you can use
   * placeholders (*) to match all keys of an subarray. Let's take the follow
   * yaml file as example:
   * @code
   *  level1:
   *    level2a:
   *      level3: 1
   *    level2b:
   *      level3: 2
   * @endcode
   * Then you can filter out via $query->condition('level1.*.level3', 1).
   */
  public function condition($property, $value = NULL, $operator = NULL, $langcode = NULL) {
    return parent::condition($property, $value, $operator, $langcode);
  }

  /**
   * Implements \Drupal\Core\Entity\Query\QueryInterface::execute().
   */
  public function execute() {
    // Load all config files.
    $entity_info = $this->entityManager->getDefinition($this->getEntityType());
    $prefix = $entity_info['config_prefix'] . '.';
    $prefix_length = strlen($prefix);
    $names = $this->configStorage->listAll($prefix);
    $configs = array();
    foreach ($names as $name) {
      $configs[substr($name, $prefix_length)] = \Drupal::config($name)->get();
    }

    $result = $this->condition->compile($configs);

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

    // Create the expected structure of entity_id => entity_id. Config
    // entities have string entity IDs.
    foreach ($result as $key => &$value) {
      $value = (string) $key;
    }
    return $result;
  }

}
