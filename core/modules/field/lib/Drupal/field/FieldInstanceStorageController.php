<?php

/**
 * @file
 * Contains \Drupal\field\FieldInstanceStorageController.
 */

namespace Drupal\field;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;

/**
 * Controller class for field instances.
 *
 * Note: the class take no special care about importing instances after their
 * field in importCreate(), since this is guaranteed by the alphabetical order
 * (field.field.* entries are processed before field.instance.* entries).
 * @todo Revisit after http://drupal.org/node/1944368.
 */
class FieldInstanceStorageController extends ConfigStorageController {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $state;

  /**
   * Constructs a FieldInstanceStorageController object.
   *
   * @param string $entity_type
   *   The entity type for which the instance is created.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage service.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $state
   *   The state key value store.
   */
  public function __construct($entity_type, array $entity_info, ConfigFactory $config_factory, StorageInterface $config_storage, QueryFactory $entity_query_factory, EntityManager $entity_manager, ModuleHandler $module_handler, KeyValueStoreInterface $state) {
    parent::__construct($entity_type, $entity_info, $config_factory, $config_storage, $entity_query_factory);
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('config.factory'),
      $container->get('config.storage'),
      $container->get('entity.query'),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function importDelete($name, Config $new_config, Config $old_config) {
    // If the field has been deleted in the same import, the instance will be
    // deleted by then, and there is nothing left to do. Just return TRUE so
    // that the file does not get written to active store.
    if (!$old_config->get()) {
      return TRUE;
    }
    return parent::importDelete($name, $new_config, $old_config);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $conditions = array()) {
    // Include instances of inactive fields if specified in the
    // $conditions parameters.
    $include_inactive = $conditions['include_inactive'];
    unset($conditions['include_inactive']);
    // Include deleted instances if specified in the $conditions parameters.
    $include_deleted = $conditions['include_deleted'];
    unset($conditions['include_deleted']);

    // Get instances stored in configuration.
    if (isset($conditions['entity_type']) && isset($conditions['bundle']) && isset($conditions['field_name'])) {
      // Optimize for the most frequent case where we do have a specific ID.
      $id = $conditions['entity_type'] . '.' . $conditions['bundle'] . '.' . $conditions['field_name'];
      $instances = $this->entityManager->getStorageController($this->entityType)->loadMultiple(array($id));
    }
    else {
      // No specific ID, we need to examine all existing instances.
      $instances = $this->entityManager->getStorageController($this->entityType)->loadMultiple();
    }

    // Merge deleted instances (stored in state) if needed.
    if ($include_deleted) {
      $deleted_instances = $this->state->get('field.instance.deleted') ?: array();
      foreach ($deleted_instances as $id => $config) {
        $instances[$id] = $this->entityManager->getStorageController($this->entityType)->create($config);
      }
    }

    // Translate "do not include inactive fields" into actual conditions.
    if (!$include_inactive) {
      $conditions['field.active'] = TRUE;
    }

    // Collect matching instances.
    $matching_instances = array();
    foreach ($instances as $instance) {
      // Only include instances on unknown entity types if 'include_inactive'.
      if (!$include_inactive && !$this->entityManager->getDefinition($instance->entity_type)) {
        continue;
      }

      // Some conditions are checked against the field.
      $field = $instance->getField();

      // Only keep the instance if it matches all conditions.
      foreach ($conditions as $key => $value) {
        // Extract the actual value against which the condition is checked.
        switch ($key) {
          case 'field_name':
            $checked_value = $field->name;
            break;

          case 'field.active':
            $checked_value = $field->active;
            break;

          case 'field_id':
            $checked_value = $instance->field_uuid;
            break;

          default:
            $checked_value = $instance->$key;
            break;
        }

        // Skip to the next instance as soon as one condition does not match.
        if ($checked_value != $value) {
          continue 2;
        }
      }

      $this->moduleHandler->invokeAll('field_read_instance', $instance);

      $matching_instances[] = $instance;
    }

    return $matching_instances;
  }

}
