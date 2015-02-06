<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\process\d6\BlockVisibility.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\process\d6;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateProcessPlugin(
 *   id = "d6_block_visibility"
 * )
 */
class BlockVisibility extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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
      $container->get('plugin.manager.migrate.process')->createInstance('migration', array('migration' => 'd6_user_role'), $migration)
    );
  }
  /**
   * {@inheritdoc}
   *
   * Set the block visibility settings.
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    list($pages, $roles, $old_visibility) = $value;
    $visibility = array();

    if (!empty($pages)) {
      $visibility['request_path']['pages'] = $pages;
      $visibility['request_path']['id'] = 'request_path';
      $visibility['request_path']['negate'] = !$old_visibility;
    }

    if (!empty($roles)) {
      foreach ($roles as $key => $role_id) {
        $new_role = $this->migrationPlugin->transform($role_id, $migrate_executable, $row, $destination_property);
        $visibility['user_role']['roles'][$new_role] = $new_role;
      }
      $visibility['user_role']['id'] = 'user_role';
      $visibility['user_role']['context_mapping']['user'] = 'user.current_user';
    }
    return $visibility;
  }

}
