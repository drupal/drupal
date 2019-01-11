<?php

namespace Drupal\content_translation;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides the logic needed to update field storage definitions when needed.
 */
class ContentTranslationUpdatesManager implements EventSubscriberInterface {
  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['entityManager' => 'entity.manager'];

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The installed entity definition repository.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
   */
  protected $entityLastInstalledSchemaRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $updateManager;

  /**
   * Constructs an updates manager instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager
   *   The entity definition update manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository
   *   The installed entity definition repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityDefinitionUpdateManagerInterface $update_manager, EntityFieldManagerInterface $entity_field_manager = NULL, EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->updateManager = $update_manager;
    if (!$entity_field_manager) {
      @trigger_error('The entity_field.manager service must be passed to ContentTranslationUpdatesManager::__construct(), it is required before Drupal 9.0.0. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $entity_field_manager = \Drupal::service('entity_field.manager');
    }
    $this->entityFieldManager = $entity_field_manager;
    if (!$entity_last_installed_schema_repository) {
      @trigger_error('The entity.last_installed_schema.repository service must be passed to ContentTranslationUpdatesManager::__construct(), it is required before Drupal 9.0.0. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $entity_last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');
    }
    $this->entityLastInstalledSchemaRepository = $entity_last_installed_schema_repository;
  }

  /**
   * Executes field storage definition updates if needed.
   *
   * @param array $entity_types
   *   A list of entity type definitions to be processed.
   */
  public function updateDefinitions(array $entity_types) {
    // Handle field storage definition creation, if needed.
    // @todo Generalize this code in https://www.drupal.org/node/2346013.
    // @todo Handle initial values in https://www.drupal.org/node/2346019.
    if ($this->updateManager->needsUpdates()) {
      foreach ($entity_types as $entity_type_id => $entity_type) {
        $storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
        $installed_storage_definitions = $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type_id);
        foreach (array_diff_key($storage_definitions, $installed_storage_definitions) as $storage_definition) {
          /** @var $storage_definition \Drupal\Core\Field\FieldStorageDefinitionInterface */
          if ($storage_definition->getProvider() == 'content_translation') {
            $this->updateManager->installFieldStorageDefinition($storage_definition->getName(), $entity_type_id, 'content_translation', $storage_definition);
          }
        }
      }
    }
  }

  /**
   * Listener for the ConfigImporter import event.
   */
  public function onConfigImporterImport() {
    $entity_types = array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $entity_type) {
      return $entity_type->isTranslatable();
    });
    $this->updateDefinitions($entity_types);
  }

  /**
   * Listener for migration imports.
   */
  public function onMigrateImport(MigrateImportEvent $event) {
    $migration = $event->getMigration();
    $configuration = $migration->getDestinationConfiguration();
    $entity_types = NestedArray::getValue($configuration, ['content_translation_update_definitions']);
    if ($entity_types) {
      $entity_types = array_intersect_key($this->entityTypeManager->getDefinitions(), array_flip($entity_types));
      $this->updateDefinitions($entity_types);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::IMPORT][] = ['onConfigImporterImport', 60];
    if (class_exists('\Drupal\migrate\Event\MigrateEvents')) {
      $events[MigrateEvents::POST_IMPORT][] = ['onMigrateImport'];
    }
    return $events;
  }

}
