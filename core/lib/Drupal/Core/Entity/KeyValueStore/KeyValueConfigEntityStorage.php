<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\KeyValueStore\KeyValueConfigEntityStorage.
 */

namespace Drupal\Core\Entity\KeyValueStore;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides a key value backend for configuration entities.
 */
class KeyValueConfigEntityStorage extends KeyValueEntityStorage implements ConfigEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public static function getIDFromConfigName($config_name, $config_prefix) {
    // @todo Implement and test. See https://www.drupal.org/node/2406645
  }

  /**
   * {@inheritdoc}
   */
  public function createFromStorageRecord(array $values) {
    // @todo Implement and test. See https://www.drupal.org/node/2406645
  }

  /**
   * {@inheritdoc}
   */
  public function updateFromStorageRecord(ConfigEntityInterface $entity, array $values) {
    // @todo Implement and test. See https://www.drupal.org/node/2406645
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrideFree($id) {
    // KeyValueEntityStorage does not support storing and retrieving overrides,
    // it does not use the configuration factory. This is just a test method.
    // See https://www.drupal.org/node/2393751.
    return $this->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleOverrideFree(array $ids = NULL) {
    // KeyValueEntityStorage does not support storing and retrieving overrides,
    // it does not use the configuration factory. This is just a test method.
    // See https://www.drupal.org/node/2393751.
    return $this->loadMultiple($ids);
  }

}
