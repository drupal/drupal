<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\Query\Query.
 */

namespace Drupal\Core\Config\Entity\Query;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Defines the entity query for configuration entities.
 */
class Query extends QueryBase implements QueryInterface {

  /**
   * The config storage used by the config entity query.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The config factory used by the config entity query.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a Query object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The actual config storage which is used to list all config items.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param array $namespaces
   *   List of potential namespaces of the classes belonging to this query.
   */
  function __construct(EntityTypeInterface $entity_type, $conjunction, StorageInterface $config_storage, ConfigFactoryInterface $config_factory, array $namespaces) {
    parent::__construct($entity_type, $conjunction, $namespaces);
    $this->configStorage = $config_storage;
    $this->configFactory = $config_factory;
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
    // Load the relevant config records.
    $configs = $this->loadRecords();

    // Apply conditions.
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

  /**
   * Loads the config records to examine for the query.
   *
   * @return array
   *   Config records keyed by entity IDs.
   */
  protected function loadRecords() {
    $prefix = $this->entityType->getConfigPrefix() . '.';
    $prefix_length = strlen($prefix);

    // Search the conditions for restrictions on entity IDs.
    $ids = array();
    if ($this->condition->getConjunction() == 'AND') {
      foreach ($this->condition->conditions() as $condition) {
        if (is_string($condition['field']) && $condition['field'] == $this->entityType->getKey('id')) {
          $operator = $condition['operator'] ?: (is_array($condition['value']) ? 'IN' : '=');
          if ($operator == '=') {
            $ids = array($condition['value']);
          }
          elseif ($operator == 'IN') {
            $ids = $condition['value'];
          }
          // We stop at the first restricting condition on ID. In the (weird)
          // case where there are additional restricting conditions, results
          // will be eliminated when the conditions are checked on the loaded
          // records.
          if ($ids) {
            break;
          }
        }
      }
    }
    // If there are conditions restricting config ID, we can narrow the list of
    // records to load and parse.
    if ($ids) {
      $names = array_map(function ($id) use ($prefix) {
        return $prefix . $id;
      }, $ids);
    }
    // If no restrictions on IDs were found, we need to parse all records.
    else {
      $names = $this->configStorage->listAll($prefix);
    }

    // Load the corresponding records.
    $records = array();
    foreach ($this->configFactory->loadMultiple($names) as $config) {
      $records[substr($config->getName(), $prefix_length)] = $config->get();
    }
    return $records;
  }

}
