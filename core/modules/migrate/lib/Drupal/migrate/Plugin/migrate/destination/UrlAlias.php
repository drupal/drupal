<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\UrlAlias.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Path\PathInterface;
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
   * The path crud service.
   *
   * @var \Drupal\Core\Path\PathInterface $path
   */
  protected $path;

  /**
   * Constructs an entity destination plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param MigrationInterface $migration
   *   The migration.
   * @param \Drupal\Core\Path\PathInterface $path
   *   The path crud service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration, PathInterface $path) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->path = $path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('path.crud')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {

    $path = $this->path->save(
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
