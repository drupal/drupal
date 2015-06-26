<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\process\d6\FilterFormatPermission.
 */


namespace Drupal\migrate_drupal\Plugin\migrate\process\d6;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Entity\MigrationInterface;
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
   * The migration plugin.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $migrationPlugin;

  /**
   * {@inheritdoc}
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
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migrate.process')->createInstance('migration', array('migration' => 'd6_filter_format'), $migration)
    );
  }

  /**
   * {@inheritdoc}
   *
   * Migrate filter format serial to string id in permission name.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $rid = $row->getSourceProperty('rid');
    if ($formats = $row->getSourceProperty("filter_permissions:$rid")) {
      foreach ($formats as $format) {
        $new_id = $this->migrationPlugin->transform($format, $migrate_executable, $row, $destination_property);
        if ($new_id) {
          $value[] = 'use text format ' . $new_id;
        }
      }
    }
    return $value;
  }

}
