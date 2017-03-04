<?php

namespace Drupal\comment\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateDestination(
 *   id = "entity:comment"
 * )
 */
class EntityComment extends EntityContentBase {

  /**
   * The state storage object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * An array of entity IDs for the 'commented entity' keyed by entity type.
   *
   * @var array
   */
  protected $stubCommentedEntityIds;

  /**
   * Builds an comment entity destination.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param MigrationInterface $migration
   *   The migration.
   * @param EntityStorageInterface $storage
   *   The storage for this entity type.
   * @param array $bundles
   *   The list of bundles this entity type has.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, EntityManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_manager, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $entity_manager, $field_type_manager);
    $this->state = $state;
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
      $container->get('plugin.manager.field.field_type'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    if ($row->isStub() && ($state = $this->state->get('comment.maintain_entity_statistics', 0))) {
      $this->state->set('comment.maintain_entity_statistics', 0);
    }
    $return = parent::import($row, $old_destination_id_values);
    if ($row->isStub() && $state) {
      $this->state->set('comment.maintain_entity_statistics', $state);
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function processStubRow(Row $row) {
    parent::processStubRow($row);
    // Neither uid nor name is required in itself, but it is required to set one
    // of them.
    $row->setDestinationProperty('name', 'anonymous_stub');
  }

}
