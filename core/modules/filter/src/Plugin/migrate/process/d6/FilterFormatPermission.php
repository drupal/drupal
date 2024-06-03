<?php

namespace Drupal\filter\Plugin\migrate\process\d6;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migrate filter format serial to string id in permission name.
 *
 * The filter_format_permission plugin is used to get the filter formats for a
 * role and convert it to a permission name.
 *
 *  Available configuration keys:
 *  - migration: (optional) The filter migration. Defaults to
 *  'd6_filter_format'.
 *
 *  Examples:
 *
 * @code
 *  process:
 *    result:
 *      plugin: filter_format_permission
 *      migration: d6_filter_format
 * @endcode
 *
 *  This will use the 'd6_filter_format' migration to lookup the destination
 *  filter formats for a role.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess(
  id: "filter_format_permission",
  handle_multiples: TRUE,
)]
class FilterFormatPermission extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The current migration.
   */
  protected MigrationInterface $migration;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrateLookupInterface $migrate_lookup) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->migrateLookup = $migrate_lookup;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
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
    $migration = $this->configuration['migration'] ?? 'd6_filter_format';
    if ($formats = $row->getSourceProperty("filter_permissions:$rid")) {
      foreach ($formats as $format) {
        $lookup_result = $this->migrateLookup->lookup($migration, [$format]);
        if ($lookup_result) {
          $value[] = 'use text format ' . $lookup_result[0]['format'];
        }
      }
    }
    return $value;
  }

}
