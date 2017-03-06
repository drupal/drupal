<?php

namespace Drupal\Core\Config\Entity\Query;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryException;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a factory for creating entity query objects for the config backend.
 */
class QueryFactory implements QueryFactoryInterface, EventSubscriberInterface {

  /**
   * The prefix for the key value collection for fast lookups.
   */
  const CONFIG_LOOKUP_PREFIX = 'config.entity.key_store.';

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config storage used by the config entity query.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   The key value factory.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, KeyValueFactoryInterface $key_value, ConfigManagerInterface $config_manager) {
    $this->configFactory = $config_factory;
    $this->keyValueFactory = $key_value;
    $this->configManager = $config_manager;
    $this->namespaces = QueryBase::getNamespaces($this);
  }

  /**
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    return new Query($entity_type, $conjunction, $this->configFactory, $this->keyValueFactory, $this->namespaces);
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregate(EntityTypeInterface $entity_type, $conjunction) {
    throw new QueryException('Aggregation over configuration entities is not supported');
  }

  /**
   * Gets the key value store used to store fast lookups.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   *   The key value store used to store fast lookups.
   */
  protected function getConfigKeyStore(EntityTypeInterface $entity_type) {
    return $this->keyValueFactory->get(static::CONFIG_LOOKUP_PREFIX . $entity_type->id());
  }

  /**
   * Updates or adds lookup data.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Config\Config $config
   *   The configuration object that is being saved.
   */
  protected function updateConfigKeyStore(ConfigEntityTypeInterface $entity_type, Config $config) {
    $config_key_store = $this->getConfigKeyStore($entity_type);
    foreach ($entity_type->getLookupKeys() as $lookup_key) {
      foreach ($this->getKeys($config, $lookup_key, 'get', $entity_type) as $key) {
        $values = $config_key_store->get($key, []);
        if (!in_array($config->getName(), $values, TRUE)) {
          $values[] = $config->getName();
          $config_key_store->set($key, $values);
        }
      }
    }
  }

  /**
   * Deletes lookup data.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Config\Config $config
   *   The configuration object that is being deleted.
   */
  protected function deleteConfigKeyStore(ConfigEntityTypeInterface $entity_type, Config $config) {
    $config_key_store = $this->getConfigKeyStore($entity_type);
    foreach ($entity_type->getLookupKeys() as $lookup_key) {
      foreach ($this->getKeys($config, $lookup_key, 'getOriginal', $entity_type) as $key) {
        $values = $config_key_store->get($key, []);
        $pos = array_search($config->getName(), $values, TRUE);
        if ($pos !== FALSE) {
          unset($values[$pos]);
        }
        if (empty($values)) {
          $config_key_store->delete($key);
        }
        else {
          $config_key_store->set($key, $values);
        }
      }
    }
  }

  /**
   * Creates lookup keys for configuration data.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The configuration object.
   *  @param string $key
   *   The configuration key to look for.
   * @param string $get_method
   *   Which method on the config object to call to get the value. Either 'get'
   *   or 'getOriginal'.
   * @param \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type
   *   The configuration entity type.
   *
   * @return array
   *   An array of lookup keys concatenated to the configuration values.
   *
   * @throws \Drupal\Core\Config\Entity\Query\InvalidLookupKeyException
   *   The provided $key cannot end with a wildcard. This makes no sense since
   *   you cannot do fast lookups against this.
   */
  protected function getKeys(Config $config, $key, $get_method, ConfigEntityTypeInterface $entity_type) {
    if (substr($key, -1) == '*') {
      throw new InvalidLookupKeyException(strtr('%entity_type lookup key %key ends with a wildcard this can not be used as a lookup', ['%entity_type' => $entity_type->id(), '%key' => $key]));
    }
    $parts = explode('.*', $key);
    // Remove leading dots.
    array_walk($parts, function (&$value) {
      $value = trim($value, '.');
    });

    $values = (array) $this->getValues($config, $parts[0], $get_method, $parts);

    $output = [];
    // Flatten the array to a single dimension and add the key to all the
    // values.
    array_walk_recursive($values, function ($current) use (&$output, $key) {
      if (is_scalar($current)) {
        $current = $key . ':' . $current;
      }
      $output[] = $current;
    });
    return $output;
  }

  /**
   * Finds all the values for a configuration key in a configuration object.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The configuration object.
   * @param string $key
   *   The current key being checked.
   * @param string $get_method
   *   Which method on the config object to call to get the value.
   * @param array $parts
   *   All the parts of a configuration key we are checking.
   * @param int $start
   *   Which position of $parts we are processing. Defaults to 0.
   *
   * @return array|null
   *   The array of configuration values the match the provided key. NULL if
   *   the configuration object does not have a value that corresponds to the
   *   key.
   */
  protected function getValues(Config $config, $key, $get_method, array $parts, $start = 0) {
    $value = $config->$get_method($key);
    if (is_array($value)) {
      $new_value = [];
      $start++;
      if (!isset($parts[$start])) {
        // The configuration object does not have a value that corresponds to
        // the key.
        return NULL;
      }
      foreach (array_keys($value) as $key_bit) {
        $new_key = $key . '.' . $key_bit;
        if (!empty($parts[$start])) {
          $new_key .= '.' . $parts[$start];
        }
        $new_value[] = $this->getValues($config, $new_key, $get_method, $parts, $start);
      }
      $value = $new_value;
    }
    return $value;
  }

  /**
   * Updates configuration entity in the key store.
   *
   * @param ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();
    $entity_type_id = $this->configManager->getEntityTypeIdByName($saved_config->getName());
    if ($entity_type_id) {
      $entity_type = $this->configManager->getEntityManager()->getDefinition($entity_type_id);
      $this->updateConfigKeyStore($entity_type, $saved_config);
    }
  }

  /**
   * Removes configuration entity from key store.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigDelete(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();
    $entity_type_id = $this->configManager->getEntityTypeIdByName($saved_config->getName());
    if ($entity_type_id) {
      $entity_type = $this->configManager->getEntityManager()->getDefinition($entity_type_id);
      $this->deleteConfigKeyStore($entity_type, $saved_config);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 128];
    $events[ConfigEvents::DELETE][] = ['onConfigDelete', 128];
    return $events;
  }

}
