<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\Process\d6\UserPicture.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\Process\d6;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The user picture process plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "d6_user_picture"
 * )
 */
class UserPicture extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migration plugin.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $migrationPlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration, MigrateProcessInterface $migration_plugin) {
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
      $container->get('plugin.manager.migrate.process')->createInstance('migration', array('migration' => 'd6_user_picture_file'), $migration)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    return $row->getSourceProperty('picture') ? $this->migrationPlugin->transform($value, $migrate_executable, $row, $destination_property) : NULL;
  }

}
