<?php

namespace Drupal\path\Plugin\migrate\destination;

use Drupal\Core\Path\AliasStorage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
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
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
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
    $source = $row->getDestinationProperty('source');
    $alias = $row->getDestinationProperty('alias');
    $langcode = $row->getDestinationProperty('langcode');
    $pid = $old_destination_id_values ? $old_destination_id_values[0] : NULL;

    // Check if this alias is for a node and if that node is a translation.
    if (preg_match('/^\/node\/\d+$/', $source) && $row->hasDestinationProperty('node_translation')) {

      // Replace the alias source with the translation source path.
      $node_translation = $row->getDestinationProperty('node_translation');
      $source = '/node/' . $node_translation[0];
      $langcode = $node_translation[1];
    }

    $path = $this->aliasStorage->save($source, $alias, $langcode, $pid);

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
    return [
      'pid' => 'The path id',
      'source' => 'The source path.',
      'alias' => 'The URL alias.',
      'langcode' => 'The language code for the URL.',
    ];
  }

}
