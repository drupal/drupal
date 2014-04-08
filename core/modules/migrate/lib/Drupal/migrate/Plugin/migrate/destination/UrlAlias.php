<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\UrlAlias.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Path\AliasStorage;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * @MigrateDestination(
 *   id = "url_alias"
 * )
 */
class UrlAlias extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The alias storage service.
   *
   * @var \Drupal\Core\Path\AliasStorage $aliasStorage
   */
  protected $aliasStorage;

  /**
   * Constructs an entity destination plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param MigrationInterface $migration
   *   The migration.
   * @param \Drupal\Core\Path\AliasStorage $alias_storage
   *   The alias storage service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, AliasStorage $alias_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->aliasStorage = $alias_storage;
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
      $container->get('path.alias_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {

    $path = $this->aliasStorage->save(
      $row->getDestinationProperty('source'),
      $row->getDestinationProperty('alias'),
      $row->getDestinationProperty('langcode'),
      $old_destination_id_values ? $old_destination_id_values[0] : NULL
    );

    return array($path['pid']);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['pid']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    // TODO: Implement fields() method.
  }

}
