<?php

namespace Drupal\Core\DependencyInjection;

use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dependency injection friendly methods for serialization.
 */
trait DependencySerializationTrait {

  /**
   * An array of service IDs keyed by property name used for serialization.
   *
   * @var array
   */
  protected $_serviceIds = [];

  /**
   * An array of entity type IDs keyed by the property name of their storages.
   *
   * @var array
   */
  protected $_entityStorages = [];

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $this->_serviceIds = [];
    $vars = get_object_vars($this);
    foreach ($vars as $key => $value) {
      if (is_object($value) && isset($value->_serviceId)) {
        // If a class member was instantiated by the dependency injection
        // container, only store its ID so it can be used to get a fresh object
        // on unserialization.
        $this->_serviceIds[$key] = $value->_serviceId;
        unset($vars[$key]);
      }
      // Special case the container, which might not have a service ID.
      elseif ($value instanceof ContainerInterface) {
        $this->_serviceIds[$key] = 'service_container';
        unset($vars[$key]);
      }
      elseif ($value instanceof EntityStorageInterface) {
        // If a class member is an entity storage, only store the entity type ID
        // the storage is for so it can be used to get a fresh object on
        // unserialization. By doing this we prevent possible memory leaks when
        // the storage is serialized when it contains a static cache of entity
        // objects and additionally we ensure that we'll not have multiple
        // storage objects for the same entity type and therefore prevent
        // returning different references for the same entity.
        $this->_entityStorages[$key] = $value->getEntityTypeId();
        unset($vars[$key]);
      }
    }

    return array_keys($vars);
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    // Tests in isolation potentially unserialize in the parent process.
    $phpunit_bootstrap = isset($GLOBALS['__PHPUNIT_BOOTSTRAP']);
    if ($phpunit_bootstrap && !\Drupal::hasContainer()) {
      return;
    }
    $container = \Drupal::getContainer();
    foreach ($this->_serviceIds as $key => $service_id) {
      // In rare cases, when test data is serialized in the parent process,
      // there is a service container but it doesn't contain all expected
      // services. To avoid fatal errors during the wrap-up of failing tests, we
      // check for this case, too.
      if ($phpunit_bootstrap && !$container->has($service_id)) {
        continue;
      }
      $this->$key = $container->get($service_id);
    }
    $this->_serviceIds = [];

    // In rare cases, when test data is serialized in the parent process, there
    // is a service container but it doesn't contain all expected services. To
    // avoid fatal errors during the wrap-up of failing tests, we check for this
    // case, too.
    if ($this->_entityStorages && (!$phpunit_bootstrap || $container->has('entity_type.manager'))) {
      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
      $entity_type_manager = $container->get('entity_type.manager');
      foreach ($this->_entityStorages as $key => $entity_type_id) {
        $this->$key = $entity_type_manager->getStorage($entity_type_id);
      }
    }
    $this->_entityStorages = [];
  }

}
