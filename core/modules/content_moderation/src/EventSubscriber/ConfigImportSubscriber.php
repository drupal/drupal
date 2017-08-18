<?php

namespace Drupal\content_moderation\EventSubscriber;

use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigImportValidateEventSubscriberBase;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Check moderation states are not being used before updating workflow config.
 */
class ConfigImportSubscriber extends ConfigImportValidateEventSubscriberBase {

  /**
   * The config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the event subscriber.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The config manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigManagerInterface $config_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->configManager = $config_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigImporterValidate(ConfigImporterEvent $event) {
    foreach (['update', 'delete'] as $op) {
      $unprocessed_configurations = $event->getConfigImporter()->getUnprocessedConfiguration($op);
      foreach ($unprocessed_configurations as $unprocessed_configuration) {
        if ($workflow = $this->getWorkflow($unprocessed_configuration)) {
          if ($op === 'update') {
            $original_workflow_config = $event->getConfigImporter()
              ->getStorageComparer()
              ->getSourceStorage()
              ->read($unprocessed_configuration);
            $workflow_config = $event->getConfigImporter()
              ->getStorageComparer()
              ->getTargetStorage()
              ->read($unprocessed_configuration);
            $diff = array_diff_key($workflow_config['type_settings']['states'], $original_workflow_config['type_settings']['states']);
            foreach (array_keys($diff) as $state_id) {
              $state = $workflow->getTypePlugin()->getState($state_id);
              if ($workflow->getTypePlugin()->workflowStateHasData($workflow, $state)) {
                $event->getConfigImporter()->logError($this->t('The moderation state @state_label is being used, but is not in the source storage.', ['@state_label' => $state->label()]));
              }
            }
          }
          if ($op === 'delete') {
            if ($workflow->getTypePlugin()->workflowHasData($workflow)) {
              $event->getConfigImporter()->logError($this->t('The workflow @workflow_label is being used, and cannot be deleted.', ['@workflow_label' => $workflow->label()]));
            }
          }
        }
      }
    }
  }

  /**
   * Get the workflow entity object from the configuration name.
   *
   * @param string $config_name
   *   The configuration object name.
   *
   * @return \Drupal\workflows\WorkflowInterface|null
   *   A workflow entity object. NULL if no matching entity is found.
   */
  protected function getWorkflow($config_name) {
    $entity_type_id = $this->configManager->getEntityTypeIdByName($config_name);
    if ($entity_type_id !== 'workflow') {
      return;
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type */
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $entity_id = ConfigEntityStorage::getIDFromConfigName($config_name, $entity_type->getConfigPrefix());
    return $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
  }

}
