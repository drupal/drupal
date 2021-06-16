<?php

namespace Drupal\Core\Field;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
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
    foreach ($records as $id => &$record) {
      try {
        $class = $this->fieldTypeManager->getPluginClass($record['field_type']);
      }
      catch (PluginNotFoundException $e) {
        $config_id = $this->getPrefix() . $id;
        throw new PluginNotFoundException($record['field_type'], "Unable to determine class for field type '{$record['field_type']}' found in the '$config_id' configuration", $e->getCode(), $e);
      }
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
