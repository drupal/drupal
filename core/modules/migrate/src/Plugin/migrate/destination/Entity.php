<?php

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\EntityFieldDefinitionTrait;
use Drupal\migrate\Plugin\Derivative\MigrateEntity;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

// cspell:ignore tnid

/**
 * Provides a generic destination to import entities.
 *
 * Available configuration keys:
 * - default_bundle: (optional) The bundle to use for this row if 'bundle' is
 *   not defined on the row. Setting this also allows the fields() method to
 *   return bundle fields as well as base fields.
 *
 * Examples:
 *
 * @code
 * source:
 *   plugin: d7_node
 * process:
 *   nid: tnid
 *   vid: vid
 *   langcode: language
 *   title: title
 *   ...
 *   revision_timestamp: timestamp
 * destination:
 *   plugin: entity:node
 * @endcode
 *
 * This will save the processed, migrated row as a node.
 *
 * @code
 * source:
 *   plugin: d7_node
 * process:
 *   nid: tnid
 *   vid: vid
 *   langcode: language
 *   title: title
 *   ...
 *   revision_timestamp: timestamp
 * destination:
 *   plugin: entity:node
 *   default_bundle: custom
 * @endcode
 *
 * This will save the processed, migrated row as a node of type 'custom'.
 */
#[MigrateDestination(
  id: 'entity',
  deriver: MigrateEntity::class
)]
abstract class Entity extends DestinationBase implements ContainerFactoryPluginInterface, DependentPluginInterface {

  use DependencyTrait;
  use EntityFieldDefinitionTrait;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The entity field manager.
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The list of the bundles of this entity type.
   *
   * @var array
   */
  protected $bundles;

  /**
   * Construct a new entity.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage for this entity type.
   * @param array $bundles
   *   The list of bundles this entity type has.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles) {
    $plugin_definition += [
      'label' => $storage->getEntityType()->getPluralLabel(),
    ];

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->storage = $storage;
    $this->bundles = $bundles;
    $this->supportsRollback = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    $entity_type_id = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')->getStorage($entity_type_id),
      array_keys($container->get('entity_type.bundle.info')->getBundleInfo($entity_type_id))
    );
  }

  /**
   * Gets the bundle for the row taking into account the default.
   *
   * @param \Drupal\migrate\Row $row
   *   The current row we're importing.
   *
   * @return string
   *   The bundle for this row.
   */
  public function getBundle(Row $row) {
    $default_bundle = $this->configuration['default_bundle'] ?? '';
    $bundle_key = $this->getKey('bundle');
    return $row->getDestinationProperty($bundle_key) ?: $default_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [];
  }

  /**
   * Creates or loads an entity.
   *
   * @param \Drupal\migrate\Row $row
   *   The row object.
   * @param array $old_destination_id_values
   *   The old destination IDs.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity we are importing into.
   */
  protected function getEntity(Row $row, array $old_destination_id_values) {
    $entity_id = reset($old_destination_id_values) ?: $this->getEntityId($row);
    if (!empty($entity_id) && ($entity = $this->storage->load($entity_id))) {
      // Allow updateEntity() to change the entity.
      $entity = $this->updateEntity($entity, $row) ?: $entity;
    }
    else {
      // Attempt to ensure we always have a bundle.
      if ($bundle = $this->getBundle($row)) {
        $row->setDestinationProperty($this->getKey('bundle'), $bundle);
      }

      // Stubs might need some required fields filled in.
      if ($row->isStub()) {
        $this->processStubRow($row);
      }
      $entity = $this->storage->create($row->getDestination());
      $entity->enforceIsNew();
    }
    return $entity;
  }

  /**
   * Updates an entity with the new values from row.
   *
   * This method should be implemented in extending classes.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param \Drupal\migrate\Row $row
   *   The row object to update from.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An updated entity from row values.
   *
   * @throws \LogicException
   *   Thrown for config entities, if the destination is for translations and
   *   either the "property" or "translation" property does not exist.
   */
  abstract protected function updateEntity(EntityInterface $entity, Row $row);

  /**
   * Populates as much of the stub row as possible.
   *
   * This method can be implemented in extending classes when needed.
   *
   * @param \Drupal\migrate\Row $row
   *   The row of data.
   */
  protected function processStubRow(Row $row) {}

  /**
   * Gets the entity ID of the row.
   *
   * @param \Drupal\migrate\Row $row
   *   The row of data.
   *
   * @return string
   *   The entity ID for the row that we are importing.
   */
  protected function getEntityId(Row $row) {
    return $row->getDestinationProperty($this->getKey('id'));
  }

  /**
   * Returns a specific entity key.
   *
   * @param string $key
   *   The name of the entity key to return.
   *
   * @return string|bool
   *   The entity key, or FALSE if it does not exist.
   *
   * @see \Drupal\Core\Entity\EntityTypeInterface::getKeys()
   */
  protected function getKey($key) {
    return $this->storage->getEntityType()->getKey($key);
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    // Delete the specified entity from Drupal if it exists.
    $entity = $this->storage->load(reset($destination_identifier));
    if ($entity) {
      if ($entity instanceof ContentEntityInterface) {
        $entity->setSyncing(TRUE);
      }
      $entity->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->addDependency('module', $this->storage->getEntityType()->getProvider());
    return $this->dependencies;
  }

}
