<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\EntityContentBase.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The destination class for all content entities lacking a specific class.
 */
class EntityContentBase extends Entity {

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Constructs a content entity.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage for this entity type.
   * @param array $bundles
   *   The list of bundles this entity type has.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, EntityManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles);
    $this->entityManager = $entity_manager;
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_type = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity.manager')->getStorage($entity_type),
      array_keys($container->get('entity.manager')->getBundleInfo($entity_type)),
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $this->rollbackAction = MigrateIdMapInterface::ROLLBACK_DELETE;
    $entity = $this->getEntity($row, $old_destination_id_values);
    if (!$entity) {
      throw new MigrateException('Unable to get entity');
    }
    return $this->save($entity, $old_destination_id_values);
  }

  /**
   * Saves the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   * @param array $old_destination_id_values
   *   (optional) An array of destination ID values. Defaults to an empty array.
   *
   * @return array
   *   An array containing the entity ID.
   */
  protected function save(ContentEntityInterface $entity, array $old_destination_id_values = array()) {
    $entity->save();
    return array($entity->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $id_key = $this->getKey('id');
    $ids[$id_key]['type'] = 'integer';
    return $ids;
  }

  /**
   * Updates an entity with the new values from row.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param \Drupal\migrate\Row $row
   *   The row object to update from.
   */
  protected function updateEntity(EntityInterface $entity, Row $row) {
    // If the migration has specified a list of properties to be overwritten,
    // clone the row with an empty set of destination values, and re-add only
    // the specified properties.
    if (isset($this->configuration['overwrite_properties'])) {
      $clone = $row->cloneWithoutDestination();
      foreach ($this->configuration['overwrite_properties'] as $property) {
        $clone->setDestinationProperty($property, $row->getDestinationProperty($property));
      }
      $row = $clone;
    }

    foreach ($row->getDestination() as $field_name => $values) {
      $field = $entity->$field_name;
      if ($field instanceof TypedDataInterface) {
        $field->setValue($values);
      }
    }

    $this->setRollbackAction($row->getIdMap());
  }

  /**
   * Populates as much of the stub row as possible.
   *
   * @param \Drupal\migrate\Row $row
   *   The row of data.
   */
  protected function processStubRow(Row $row) {
    $bundle_key = $this->getKey('bundle');
    if ($bundle_key && empty($row->getDestinationProperty($bundle_key))) {
      if (empty($this->bundles)) {
        throw new MigrateException('Stubbing failed, no bundles available for entity type: ' . $this->storage->getEntityTypeId());
      }
      $row->setDestinationProperty($bundle_key, reset($this->bundles));
    }

    // Populate any required fields not already populated.
    $fields = $this->entityManager
      ->getFieldDefinitions($this->storage->getEntityTypeId(), $bundle_key);
    foreach ($fields as $field_name => $field_definition) {
      if ($field_definition->isRequired() && is_null($row->getDestinationProperty($field_name))) {
        // Use the configured default value for this specific field, if any.
        if ($default_value = $field_definition->getDefaultValueLiteral()) {
          $values[] = $default_value;
        }
        else {
          // Otherwise, ask the field type to generate a sample value.
          $field_type = $field_definition->getType();
          /** @var \Drupal\Core\Field\FieldItemInterface $field_type_class */
          $field_type_class = $this->fieldTypeManager
            ->getPluginClass($field_definition->getType());
          $values = $field_type_class::generateSampleValue($field_definition);
          if (is_null($values)) {
            // Handle failure to generate a sample value.
            throw new MigrateException('Stubbing failed, unable to generate value for field ' . $field_name);
          }
        }

        $row->setDestinationProperty($field_name, $values);
      }
    }
  }

}
