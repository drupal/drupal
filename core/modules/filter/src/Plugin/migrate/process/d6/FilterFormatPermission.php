<?php

namespace Drupal\filter\Plugin\migrate\process\d6;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migrate filter format serial to string id in permission name.
 *
 * @MigrateProcessPlugin(
 *   id = "filter_format_permission",
 *   handle_multiples = TRUE
 * )
 */
class FilterFormatPermission extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Migration process plugin.
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
   * Constructs a FilterFormatPermission plugin instance.
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
   *
   * Migrate filter format serial to string id in permission name.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $rid = $row->getSourceProperty('rid');
    $migration = isset($this->configuration['migration']) ? $this->configuration['migration'] : 'd6_filter_format';
    if ($formats = $row->getSourceProperty("filter_permissions:$rid")) {
      foreach ($formats as $format) {

        // This BC layer is included because if the plugin constructor was
        // called in the legacy way with a migration_lookup process plugin, it
        // may have been preconfigured with a different migration to look up
        // against. While this is unlikely, for maximum BC we will continue to
        // use the plugin to do the lookup if it is provided, and support for
        // this will be removed in Drupal 9.
        if ($this->migrationPlugin) {
          $new_id = $this->migrationPlugin->transform($format, $migrate_executable, $row, $destination_property);
        }
        else {
          $lookup_result = $this->migrateLookup->lookup($migration, [$format]);
          if ($lookup_result) {
            $new_id = $lookup_result[0]['format'];
          }
        }
        if (!empty($new_id)) {
          $value[] = 'use text format ' . $new_id;
        }
      }
    }
    return $value;
  }

}
