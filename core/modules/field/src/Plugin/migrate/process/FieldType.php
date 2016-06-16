<?php

namespace Drupal\field\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\process\StaticMap;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateProcessPlugin(
 *   id = "field_type"
 * )
 */
class FieldType extends StaticMap implements ContainerFactoryPluginInterface {

  /**
   * The cckfield plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $cckPluginManager;

  /**
   * The migration object.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * Constructs a FieldType plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $cck_plugin_manager
   *   The cckfield plugin manager.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration being run.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PluginManagerInterface $cck_plugin_manager, MigrationInterface $migration = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cckPluginManager = $cck_plugin_manager;
    $this->migration = $migration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.migrate.cckfield'),
      $migration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $field_type = is_array($value) ? $value[0] : $value;

    try {
      return $this->cckPluginManager->createInstance($field_type, [], $this->migration)->getFieldType($row);
    }
    catch (PluginNotFoundException $e) {
      return parent::transform($value, $migrate_executable, $row, $destination_property);
    }
  }

}
