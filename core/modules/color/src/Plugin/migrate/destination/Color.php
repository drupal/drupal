<?php

namespace Drupal\color\Plugin\migrate\destination;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;

/**
 * Persist color data to the config system.
 *
 * @MigrateDestination(
 *   id = "color"
 * )
 */
class Color extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a Color object.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The current migration.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->configFactory = $config_factory;
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
  public function import(Row $row, array $old_destination_id_values = []) {
    $imported = FALSE;
    $value = $row->getDestinationProperty('value');
    if (isset($value)) {
      $this->configFactory->getEditable($row->getDestinationProperty('configuration_name'))
        ->set($row->getDestinationProperty('element_name'), $row->getDestinationProperty('value'))
        ->save();
      $imported = TRUE;
    }
    return $imported;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['name']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [];
  }

}
