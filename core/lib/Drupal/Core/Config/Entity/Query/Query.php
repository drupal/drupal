<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\Query\Query.
 */

namespace Drupal\Core\Config\Entity\Query;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * Defines the entity query for configuration entities.
 */
class Query extends QueryBase implements QueryInterface {

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface
   */
  protected $entityType;

  /**
   * The config factory used by the config entity query.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The key value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueFactory;

  /**
   * Constructs a Query object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key value factory.
   * @param array $namespaces
   *   List of potential namespaces of the classes belonging to this query.
   */
  function __construct(EntityTypeInterface $entity_type, $conjunction, ConfigFactoryInterface $config_factory, KeyValueFactoryInterface $key_value_factory, array $namespaces) {
    parent::__construct($entity_type, $conjunction, $namespaces);
    $this->configFactory = $config_factory;
    $this->keyValueFactory = $key_value_factory;
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
   * {@inheritdoc}
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

    // Search the conditions for restrictions on configuration object names.
    $names = FALSE;
    $id_condition = NULL;
    $id_key = $this->entityType->getKey('id');
    if ($this->condition->getConjunction() == 'AND') {
      $lookup_keys = $this->entityType->getLookupKeys();
      $conditions = $this->condition->conditions();
      foreach ($conditions as $condition_key => $condition) {
        $operator = $condition['operator'] ?: (is_array($condition['value']) ? 'IN' : '=');
        if (is_string($condition['field']) && ($operator == 'IN' || $operator == '=')) {
          // Special case ID lookups.
          if ($condition['field'] == $id_key) {
            $ids = (array) $condition['value'];
            $names = array_map(function ($id) use ($prefix) {
              return $prefix . $id;
            }, $ids);
          }
          elseif (in_array($condition['field'], $lookup_keys)) {
            // If we don't find anything then there are no matches. No point in
            // listing anything.
            $names = array();
            $keys = (array) $condition['value'];
            $keys = array_map(function ($value) use ($condition) {
              return $condition['field'] . ':' . $value;
            }, $keys);
            foreach ($this->getConfigKeyStore()->getMultiple($keys) as $list) {
              $names = array_merge($names, $list);
            }
          }
        }
        // Save the first ID condition that is not an 'IN' or '=' for narrowing
        // down later.
        elseif (!$id_condition && $condition['field'] == $id_key) {
          $id_condition = $condition;
        }
        // We stop at the first restricting condition on name. In the case where
        // there are additional restricting conditions, results will be
        // eliminated when the conditions are checked on the loaded records.
        if ($names !== FALSE) {
          // If the condition has been responsible for narrowing the list of
          // configuration to check there is no point in checking it further.
          unset($conditions[$condition_key]);
          break;
        }
      }
    }
    // If no restrictions on IDs were found, we need to parse all records.
    if ($names === FALSE) {
      $names = $this->configFactory->listAll($prefix);
    }
    // In case we have an ID condition, try to narrow down the list of config
    // objects to load.
    if ($id_condition && !empty($names)) {
      $value = $id_condition['value'];
      $filter = NULL;
      switch ($id_condition['operator']) {
        case '<>':
          $filter = function ($name) use ($value, $prefix_length) {
            $id = substr($name, $prefix_length);
            return $id !== $value;
          };
          break;
        case 'STARTS_WITH':
          $filter = function ($name) use ($value, $prefix_length) {
            $id = substr($name, $prefix_length);
            return strpos($id, $value) === 0;
          };
          break;
        case 'CONTAINS':
          $filter = function ($name) use ($value, $prefix_length) {
            $id = substr($name, $prefix_length);
            return strpos($id, $value) !== FALSE;
          };
          break;
        case 'ENDS_WITH':
          $filter = function ($name) use ($value, $prefix_length) {
            $id = substr($name, $prefix_length);
            return strrpos($id, $value) === strlen($id) - strlen($value);
          };
          break;
      }
      if ($filter) {
        $names = array_filter($names, $filter);
      }
    }

    // Load the corresponding records.
    $records = array();
    foreach ($this->configFactory->loadMultiple($names) as $config) {
      $records[substr($config->getName(), $prefix_length)] = $config->get();
    }
    return $records;
  }

  /**
   * Gets the key value store used to store fast lookups.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   *   The key value store used to store fast lookups.
   */
  protected function getConfigKeyStore() {
    return $this->keyValueFactory->get(QueryFactory::CONFIG_LOOKUP_PREFIX . $this->entityTypeId);
  }

}
