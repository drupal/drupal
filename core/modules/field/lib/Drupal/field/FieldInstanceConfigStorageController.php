<?php

/**
 * @file
 * Contains \Drupal\field\FieldInstanceConfigStorageController.
 */

namespace Drupal\field;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\KeyValueStore\StateInterface;

/**
 * Controller class for field instances.
 *
 * Note: the class take no special care about importing instances after their
 * field in importCreate(), since this is guaranteed by the alphabetical order
 * (field.field.* entries are processed before field.instance.* entries).
 * @todo Revisit after http://drupal.org/node/1944368.
 */
class FieldInstanceConfigStorageController extends ConfigStorageController {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\KeyValueStore\StateInterface
   */
  protected $state;

  /**
   * Constructs a FieldInstanceConfigStorageController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\KeyValueStore\StateInterface $state
   *   The state key value store.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, StorageInterface $config_storage, UuidInterface $uuid_service, EntityManagerInterface $entity_manager, StateInterface $state) {
    parent::__construct($entity_type, $config_factory, $config_storage, $uuid_service);
    $this->entityManager = $entity_manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('config.storage'),
      $container->get('uuid'),
      $container->get('entity.manager'),
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
    // Include deleted instances if specified in the $conditions parameters.
    $include_deleted = isset($conditions['include_deleted']) ? $conditions['include_deleted'] : FALSE;
    unset($conditions['include_deleted']);

    // Get instances stored in configuration.
    if (isset($conditions['entity_type']) && isset($conditions['bundle']) && isset($conditions['field_name'])) {
      // Optimize for the most frequent case where we do have a specific ID.
      $id = $conditions['entity_type'] . '.' . $conditions['bundle'] . '.' . $conditions['field_name'];
      $instances = $this->entityManager->getStorageController($this->entityTypeId)->loadMultiple(array($id));
    }
    else {
      // No specific ID, we need to examine all existing instances.
      $instances = $this->entityManager->getStorageController($this->entityTypeId)->loadMultiple();
    }

    // Merge deleted instances (stored in state) if needed.
    if ($include_deleted) {
      $deleted_instances = $this->state->get('field.instance.deleted') ?: array();
      foreach ($deleted_instances as $id => $config) {
        $instances[$id] = $this->entityManager->getStorageController($this->entityTypeId)->create($config);
      }
    }

    // Collect matching instances.
    $matching_instances = array();
    foreach ($instances as $instance) {
      // Some conditions are checked against the field.
      $field = $instance->getField();

      // Only keep the instance if it matches all conditions.
      foreach ($conditions as $key => $value) {
        // Extract the actual value against which the condition is checked.
        switch ($key) {
          case 'field_name':
            $checked_value = $field->name;
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

      $matching_instances[] = $instance;
    }

    return $matching_instances;
  }

}
