<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\Entity.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field\FieldInfo;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @PluginId("entity")
 */
class Entity extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $storageController;

  /**
   * Constructs an entity destination plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param EntityStorageControllerInterface $storage_controller
   *   The storage controller for this entity type.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityStorageControllerInterface $storage_controller) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storageController = $storage_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorageController($configuration['entity_type'])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row) {
    // @TODO: add field handling. https://drupal.org/node/2164451
    // @TODO: add validation https://drupal.org/node/2164457
    $entity = $this->storageController->create($row->getDestination());
    $entity->save();
    return array($entity->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getIdsSchema() {
    // TODO: Implement getIdsSchema() method.
  }

  /**
   * {@inheritdoc}
   */
  public function fields(Migration $migration = NULL) {
    // TODO: Implement fields() method.
  }

}
