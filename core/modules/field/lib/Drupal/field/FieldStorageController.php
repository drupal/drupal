<?php

/**
 * @file
 * Contains \Drupal\field\FieldStorageController.
 */

namespace Drupal\field;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\KeyValueStore\StateInterface;

/**
 * Controller class for fields.
 */
class FieldStorageController extends ConfigStorageController {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

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
   * Constructs a FieldStorageController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_info
   *   The entity info for the entity type.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query_factory
   *   The entity query factory.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   * @param \Drupal\Core\KeyValueStore\StateInterface $state
   *   The state key value store.
   */
  public function __construct(EntityTypeInterface $entity_info, ConfigFactory $config_factory, StorageInterface $config_storage, QueryFactory $entity_query_factory, UuidInterface $uuid_service, EntityManagerInterface $entity_manager, ModuleHandler $module_handler, StateInterface $state) {
    parent::__construct($entity_info, $config_factory, $config_storage, $entity_query_factory, $uuid_service);
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_info) {
    return new static(
      $entity_info,
      $container->get('config.factory'),
      $container->get('config.storage'),
      $container->get('entity.query'),
      $container->get('uuid'),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $conditions = array()) {
    // Include deleted instances if specified in the $conditions parameters.
    $include_deleted = isset($conditions['include_deleted']) ? $conditions['include_deleted'] : FALSE;
    unset($conditions['include_deleted']);

    // Get fields stored in configuration.
    if (isset($conditions['entity_type']) && isset($conditions['field_name'])) {
      // Optimize for the most frequent case where we do have a specific ID.
      $id = $conditions['entity_type'] . $conditions['field_name'];
      $fields = $this->entityManager->getStorageController($this->entityType)->loadMultiple(array($id));
    }
    else {
      // No specific ID, we need to examine all existing fields.
      $fields = $this->entityManager->getStorageController($this->entityType)->loadMultiple();
    }

    // Merge deleted fields (stored in state) if needed.
    if ($include_deleted) {
      $deleted_fields = $this->state->get('field.field.deleted') ?: array();
      foreach ($deleted_fields as $id => $config) {
        $fields[$id] = $this->entityManager->getStorageController($this->entityType)->create($config);
      }
    }

    // Collect matching fields.
    $matching_fields = array();
    foreach ($fields as $field) {
      foreach ($conditions as $key => $value) {
        // Extract the actual value against which the condition is checked.
        switch ($key) {
          case 'field_name';
            $checked_value = $field->name;
            break;

          default:
            $checked_value = $field->$key;
            break;
        }

        // Skip to the next field as soon as one condition does not match.
        if ($checked_value != $value) {
          continue 2;
        }
      }

      // When returning deleted fields, key the results by UUID since they can
      // include several fields with the same ID.
      $key = $include_deleted ? $field->uuid : $field->id;
      $matching_fields[$key] = $field;
    }

    return $matching_fields;

  }
}
