<?php

namespace Drupal\file\Plugin\migrate\process\d6;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateProcessPlugin(
 *   id = "d6_cck_file"
 * )
 */
class CckFile extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migration process plugin, configured for lookups in d6_file.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $migrationPlugin;

  /**
   * Constructs a CckFile plugin instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The current migration.
   * @param \Drupal\migrate\Plugin\MigrateProcessInterface $migration_plugin
   *   An instance of the 'migration' process plugin.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrateProcessInterface $migration_plugin) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->migrationPlugin = $migration_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    // Configure the migration process plugin to look up migrated IDs from
    // a d6 file migration.
    $migration_plugin_configuration = $configuration + [
      'migration' => 'd6_file',
      'source' => ['fid'],
    ];

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migrate.process')->createInstance('migration', $migration_plugin_configuration, $migration)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $options = unserialize($value['data']);

    // Try to look up the ID of the migrated file. If one cannot be found, it
    // means the file referenced by the current field item did not migrate for
    // some reason -- file migration is notoriously brittle -- and we do NOT
    // want to send invalid file references into the field system (it causes
    // fatals), so return an empty item instead.
    try {
      $fid = $this->migrationPlugin->transform($value['fid'], $migrate_executable, $row, $destination_property);
    }
    // If the migration plugin completely fails its lookup process, it will
    // throw a MigrateSkipRowException. It shouldn't, but that is being dealt
    // with at https://www.drupal.org/node/2487568. Until that lands, return
    // an empty item.
    catch (MigrateSkipRowException $e) {
      return [];
    }

    if ($fid) {
      return [
        'target_id' => $fid,
        'display' => $value['list'],
        'description' => isset($options['description']) ? $options['description'] : '',
        'alt' => isset($options['alt']) ? $options['alt'] : '',
        'title' => isset($options['title']) ? $options['title'] : '',
      ];
    }
    else {
      return [];
    }
  }

}
