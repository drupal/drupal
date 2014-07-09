<?php
/**
 * @file
 * Provides Configuration Management destination plugin.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\Config as ConfigObject;

/**
 * Persist data to the config system.
 *
 * When a property is NULL, the default is used unless the configuration option
 * 'store null' is set to TRUE.
 *
 * @MigrateDestination(
 *   id = "config"
 * )
 */
class Config extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a Config destination object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   * @param \Drupal\Core\Config\Config $config
   *   The configuration object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, ConfigObject $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->config = $config;
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
      $container->get('config.factory')->get($configuration['config_name'])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    foreach ($row->getRawDestination() as $key => $value) {
      if (isset($value) || !empty($this->configuration['store null'])) {
        $this->config->set(str_replace(Row::PROPERTY_SEPARATOR, '.', $key), $value);
      }
    }
    $this->config->save();
    return TRUE;
  }

  /**
   * Throw an exception because config can not be rolled back.
   *
   * @param array $destination_keys
   *   The array of destination ids to roll back.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function rollbackMultiple(array $destination_keys) {
    throw new MigrateException('Configuration can not be rolled back');
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    // @todo Dynamically fetch fields using Config Schema API.
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array();
  }

}
