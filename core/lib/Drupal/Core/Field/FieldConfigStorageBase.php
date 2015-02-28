<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldConfigStorageBase.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;

/**
 * Base storage class for field config entities.
 */
abstract class FieldConfigStorageBase extends ConfigEntityStorage {

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function mapFromStorageRecords(array $records) {
    foreach ($records as &$record) {
      $class = $this->fieldTypeManager->getPluginClass($record['field_type']);
      $record['settings'] = $class::fieldSettingsFromConfigData($record['settings']);
    }
    return parent::mapFromStorageRecords($records);
  }

  /**
   * {@inheritdoc}
   */
  protected function mapToStorageRecord(EntityInterface $entity) {
    $record = parent::mapToStorageRecord($entity);
    $class = $this->fieldTypeManager->getPluginClass($record['field_type']);
    $record['settings'] = $class::fieldSettingsToConfigData($record['settings']);
    return $record;
  }

}
