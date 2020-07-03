<?php

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a destination plugin for migrating entity display components.
 *
 * Display modes provide different presentations for viewing ('view modes') or
 * editing ('form modes') content. This destination plugin is an abstract base
 * class for migrating fields and other components into view and form modes.
 *
 * @see \Drupal\migrate\Plugin\migrate\destination\PerComponentEntityDisplay
 * @see \Drupal\migrate\Plugin\migrate\destination\PerComponentEntityFormDisplay
 */
abstract class ComponentEntityDisplayBase extends DestinationBase implements ContainerFactoryPluginInterface {

  const MODE_NAME = '';

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * PerComponentEntityDisplay constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $values = [];
    // array_intersect_key() won't work because the order is important because
    // this is also the return value.
    foreach (array_keys($this->getIds()) as $id) {
      $values[$id] = $row->getDestinationProperty($id);
    }
    $entity = $this->getEntity($values['entity_type'], $values['bundle'], $values[static::MODE_NAME]);
    if (!$row->getDestinationProperty('hidden')) {
      $entity->setComponent($values['field_name'], $row->getDestinationProperty('options') ?: []);
    }
    else {
      $entity->removeComponent($values['field_name']);
    }
    $entity->save();
    return array_values($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['entity_type']['type'] = 'string';
    $ids['bundle']['type'] = 'string';
    $ids[static::MODE_NAME]['type'] = 'string';
    $ids['field_name']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    // This is intentionally left empty.
  }

  /**
   * Gets the entity.
   *
   * @param string $entity_type
   *   The entity type to retrieve.
   * @param string $bundle
   *   The entity bundle.
   * @param string $mode
   *   The display mode.
   *
   * @return \Drupal\Core\Entity\Display\EntityDisplayInterface
   *   The entity display object.
   */
  abstract protected function getEntity($entity_type, $bundle, $mode);

}
