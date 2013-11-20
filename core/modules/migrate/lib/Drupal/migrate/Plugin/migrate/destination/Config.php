<?php
/**
 * @file
 *   Provides Configuration Management destination plugin.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\Config as ConfigObject;

/**
 * Persist data to the config system.
 *
 * @PluginID("d8_config")
 */
class Config extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param ConfigObject $config
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigObject $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')->get($configuration['config_name'])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row) {
    $this->config
      ->setData($row->getDestination())
      ->save();
  }

  /**
   * @param array $destination_keys
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function rollbackMultiple(array $destination_keys) {
    throw new MigrateException('Configuration can not be rolled back');
  }

  /**
   * Derived classes must implement fields(), returning a list of available
   * destination fields.
   *
   * @todo Review the cases where we need the Migration parameter, can we avoid that?
   *
   * @param Migration $migration
   *   Optionally, the migration containing this destination.
   * @return array
   *  - Keys: machine names of the fields
   *  - Values: Human-friendly descriptions of the fields.
   */
  public function fields(Migration $migration = NULL) {
    // @todo Dynamically fetch fields using Config Schema API.
  }

  public function getIdsSchema() {
    return array($this->config->getName() => array());
  }
}
