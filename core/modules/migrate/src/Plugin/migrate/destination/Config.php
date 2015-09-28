<?php
/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\Config.
 *
 * Provides Configuration Management destination plugin.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class Config extends DestinationBase implements ContainerFactoryPluginInterface, DependentPluginInterface {
  use DependencyTrait;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->config = $config_factory->getEditable($configuration['config_name']);
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
      $container->get('config.factory')
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

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $provider = explode('.', $this->config->getName(), 2)[0];
    $this->addDependency('module', $provider);
    return $this->dependencies;
  }

}
