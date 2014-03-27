<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\EntityBaseContent.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\field\FieldInfo;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The destination class for all content entities lacking a specific class.
 */
class EntityContentBase extends Entity {

  /**
   * @var \Drupal\field\FieldInfo
   */
  protected $fieldInfo;

  /**
   * Constructs a content entity.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage for this entity type.
   * @param array $bundles
   *   The list of bundles this entity type has.
   * @param \Drupal\migrate\Plugin\MigratePluginManager $plugin_manager
   *   The plugin manager.
   * @param \Drupal\Field\FieldInfo $field_info
   *   The field info.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, MigratePluginManager $plugin_manager, FieldInfo $field_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles);
    $this->migrateEntityFieldPluginManager = $plugin_manager;
    $this->fieldInfo = $field_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_type = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity.manager')->getStorage($entity_type),
      array_keys($container->get('entity.manager')->getBundleInfo($entity_type)),
      $container->get('plugin.manager.migrate.entity_field'),
      $container->get('field.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    if ($all_instances = $this->fieldInfo->getInstances($this->storage->getEntityTypeId())) {
      /** @var \Drupal\Field\Entity\FieldInstanceConfig[] $instances */
      $instances = array();
      if ($bundle_key = $this->getKey('bundle')) {
        $bundle = $row->getDestinationProperty($bundle_key);
        if (isset($all_instances[$bundle])) {
          $instances = $all_instances[$bundle];
        }
      }
      foreach ($instances as $field_name => $instance) {
        $field_type = $instance->getType();
        if ($this->migrateEntityFieldPluginManager->getDefinition($field_type)) {
          $destination_value = $this->migrateEntityFieldPluginManager->createInstance($field_type)->import($instance, $row->getDestinationProperty($field_name));
          // @TODO: check for NULL return? Add an unset to $row? Maybe needed in
          // exception handling? Propagate exception?
          $row->setDestinationProperty($field_name, $destination_value);
        }
      }
    }
    $entity = $this->getEntity($row, $old_destination_id_values);
    return $this->save($entity, $old_destination_id_values);
  }

  /**
   * Save the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   * @param array $old_destination_id_values
   *   An array of destination id values.
   *
   * @return array
   *   An array containing the entity id.
   */
  protected function save(ContentEntityInterface $entity, array $old_destination_id_values = array()) {
    $entity->keepNewRevisionId(TRUE);
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
   * Update an entity with the new values from row.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param \Drupal\migrate\Row $row
   *   The row object to update from.
   */
  protected function updateEntity(EntityInterface $entity, Row $row) {
    foreach ($row->getDestination() as $field_name => $values) {
      $field = $entity->$field_name;
      if ($field instanceof TypedDataInterface) {
        $field->setValue($values);
      }
    }
  }

}
