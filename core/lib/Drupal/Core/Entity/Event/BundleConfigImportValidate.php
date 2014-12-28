<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Event\BundleConfigImportValidate
 */

namespace Drupal\Core\Entity\Event;

use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigImportValidateEventSubscriberBase;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Entity config importer validation event subscriber.
 */
class BundleConfigImportValidate extends ConfigImportValidateEventSubscriberBase {

  /**
   * The config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs the event subscriber.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The config manager
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(ConfigManagerInterface $config_manager, EntityManagerInterface $entity_manager) {
    $this->configManager = $config_manager;
    $this->entityManager = $entity_manager;
  }

  /**
   * Ensures bundles that will be deleted are not in use.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The config import event.
   */
  public function onConfigImporterValidate(ConfigImporterEvent $event) {
    foreach ($event->getChangelist('delete') as $config_name) {
      // Get the config entity type ID. This also ensure we are dealing with a
      // configuration entity.
      if ($entity_type_id = $this->configManager->getEntityTypeIdByName($config_name)) {
        $entity_type = $this->entityManager->getDefinition($entity_type_id);
        // Does this entity type define a bundle of another entity type.
        if ($bundle_of = $entity_type->getBundleOf()) {
          // Work out if there are entities with this bundle.
          $bundle_of_entity_type = $this->entityManager->getDefinition($bundle_of);
          $bundle_id = ConfigEntityStorage::getIDFromConfigName($config_name, $entity_type->getConfigPrefix());
          $entity_query = $this->entityManager->getStorage($bundle_of)->getQuery();
          $entity_ids = $entity_query->condition($bundle_of_entity_type->getKey('bundle'), $bundle_id)
            ->accessCheck(FALSE)
            ->range(0, 1)
            ->execute();
          if (!empty($entity_ids)) {
            $entity = $this->entityManager->getStorage($entity_type_id)->load($bundle_id);
            $event->getConfigImporter()->logError($this->t('Entities exist of type %entity_type and %bundle_label %bundle. These entities need to be deleted before importing.', array('%entity_type' => $bundle_of_entity_type->getLabel(), '%bundle_label' => $bundle_of_entity_type->getBundleLabel(), '%bundle' => $entity->label())));
          }
        }
      }
    }
  }

}
