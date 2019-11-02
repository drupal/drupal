<?php

namespace Drupal\file\Plugin\migrate\process\d6;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateProcessPlugin(
 *   id = "d6_field_file"
 * )
 */
class FieldFile extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migration process plugin, configured for lookups in d6_file.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   *
   * @deprecated in drupal:8.8.x and is removed from drupal:9.0.0. Use
   *   the migrate.lookup service instead.
   *
   * @see https://www.drupal.org/node/3047268
   */
  protected $migrationPlugin;

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * Constructs a FieldFile plugin instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The current migration.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   */
  // @codingStandardsIgnoreLine
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, $migrate_lookup) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if ($migrate_lookup instanceof MigrateProcessInterface) {
      @trigger_error('Passing a migration process plugin as the fourth argument to ' . __METHOD__ . ' is deprecated in drupal:8.8.0 and will throw an error in drupal:9.0.0. Pass the migrate.lookup service instead. See https://www.drupal.org/node/3047268', E_USER_DEPRECATED);
      $this->migrationPlugin = $migrate_lookup;
      $migrate_lookup = \Drupal::service('migrate.lookup');
    }
    elseif (!$migrate_lookup instanceof MigrateLookupInterface) {
      throw new \InvalidArgumentException("The fifth argument to " . __METHOD__ . " must be an instance of MigrateLookupInterface.");
    }
    $this->migration = $migration;
    $this->migrateLookup = $migrate_lookup;

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
      $container->get('migrate.lookup')
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
    // This BC layer is included because if the plugin constructor was called
    // in the legacy way with a migration_lookup process plugin, it may have
    // been preconfigured with a different migration to look up against. While
    // this is unlikely, for maximum BC we will continue to use the plugin to do
    // the lookup if it is provided, and support for this will be removed in
    // Drupal 9.
    if ($this->migrationPlugin) {
      $fid = $this->migrationPlugin->transform($value['fid'], $migrate_executable, $row, $destination_property);
    }
    else {
      $lookup_result = $this->migrateLookup->lookup('d6_file', [$value['fid']]);
      if ($lookup_result) {
        $fid = $lookup_result[0]['fid'];
      }
    }
    if (!empty($fid)) {
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
