<?php

namespace Drupal\field\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get the value from a method call on a field plugin instance.
 *
 * This process plugin will instantiate a field plugin based on the given
 * field type and then call the given method on it for the return value.
 *
 * Available configuration keys:
 * - source: The source field type to use to instantiate a field plugin.
 * - method: The method to be called on the field plugin instance.
 *
 * If no field plugin for the given field type is found, NULL will be returned.
 *
 * Example:
 *
 * @code
 * process:
 *   type:
 *     plugin: process_field
 *     source: type
 *     method: getFieldType
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 * @see \Drupal\migrate_drupal\Plugin\MigrateFieldInterface;
 *
 * @MigrateProcessPlugin(
 *   id = "process_field"
 * )
 */
class ProcessField extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The cckfield plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface
   */
  protected $cckPluginManager;

  /**
   * The field plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface
   */
  protected $fieldPluginManager;

  /**
   * The migration being run.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * Constructs a ProcessField plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface $cck_plugin_manager
   *   The cckfield plugin manager.
   * @param \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface $field_plugin_manager
   *   The field plugin manager.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration being run.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrateCckFieldPluginManagerInterface $cck_plugin_manager, MigrateFieldPluginManagerInterface $field_plugin_manager, MigrationInterface $migration = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cckPluginManager = $cck_plugin_manager;
    $this->fieldPluginManager = $field_plugin_manager;
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
      $container->get('plugin.manager.migrate.field'),
      $migration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_string($value)) {
      throw new MigrateException('The input value must be a string.');
    }

    if (empty($this->configuration['method'])) {
      throw new MigrateException('You need to specify the name of a method to be called on the Field plugin.');
    }
    $method = $this->configuration['method'];

    try {
      return $this->callMethodOnFieldPlugin($this->fieldPluginManager, $value, $method, $row);
    }
    catch (PluginNotFoundException $e) {
      try {
        return $this->callMethodOnFieldPlugin($this->cckPluginManager, $value, $method, $row);
      }
      catch (PluginNotFoundException $e) {
        return NULL;
      }
    }
  }

  /**
   * Instantiate a field plugin and call a method on it.
   *
   * @param \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface $field_plugin_manager
   *   The field plugin manager.
   * @param string $field_type
   *   The field type for which to get the field plugin.
   * @param string $method
   *   The method to call on the field plugin.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   *
   * @return mixed
   *   The return value from the method called on the field plugin.
   */
  protected function callMethodOnFieldPlugin(MigrateFieldPluginManagerInterface $field_plugin_manager, $field_type, $method, Row $row) {
    $plugin_id = $field_plugin_manager->getPluginIdFromFieldType($field_type, [], $this->migration);
    $plugin_instance = $field_plugin_manager->createInstance($plugin_id, [], $this->migration);
    if (!is_callable([$plugin_instance, $method])) {
      throw new MigrateException('The specified method does not exists or is not callable.');
    }
    return call_user_func_array([$plugin_instance, $method], [$row]);
  }

}
