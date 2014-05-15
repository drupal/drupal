<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\EntityComment.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\MigratePluginManager;
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
   * @param \Drupal\migrate\Plugin\MigratePluginManager $plugin_manager
   *   The migrate plugin manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, MigratePluginManager $plugin_manager, EntityManagerInterface $entity_manager, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $plugin_manager, $entity_manager);
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
      $container->get('plugin.manager.migrate.entity_field'),
      $container->get('entity.manager'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    if (($stub = !$row->getSourceProperty('entity_type')) && ($state = $this->state->get('comment.maintain_entity_statistics', 0))) {
      $this->state->set('comment.maintain_entity_statistics', 0);
    }
    $return = parent::import($row, $old_destination_id_values);
    if ($stub && $state) {
      $this->state->set('comment.maintain_entity_statistics', $state);
    }
    return $return;
  }

}
