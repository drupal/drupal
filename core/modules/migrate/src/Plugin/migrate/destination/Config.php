<?php

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Configuration Management destination plugin.
 *
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
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $language_manager;

  /**
   * Constructs a Config destination object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->config = $config_factory->getEditable($configuration['config_name']);
    $this->language_manager = $language_manager;
    if ($this->isTranslationDestination()) {
      $this->supportsRollback = TRUE;
    }
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
      $container->get('config.factory'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    if ($this->isTranslationDestination()) {
      $this->config = $this->language_manager->getLanguageConfigOverride($row->getDestinationProperty('langcode'), $this->config->getName());
    }

    foreach ($row->getRawDestination() as $key => $value) {
      if (isset($value) || !empty($this->configuration['store null'])) {
        $this->config->set(str_replace(Row::PROPERTY_SEPARATOR, '.', $key), $value);
      }
    }
    $this->config->save();
    $ids[] = $this->config->getName();
    if ($this->isTranslationDestination()) {
      $ids[] = $row->getDestinationProperty('langcode');
    }
    return $ids;
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
    $ids['config_name']['type'] = 'string';
    if ($this->isTranslationDestination()) {
      $ids['langcode']['type'] = 'string';
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $provider = explode('.', $this->config->getName(), 2)[0];
    $this->addDependency('module', $provider);
    return $this->dependencies;
  }

  /**
   * Get whether this destination is for translations.
   *
   * @return bool
   *   Whether this destination is for translations.
   */
  protected function isTranslationDestination() {
    return !empty($this->configuration['translations']);
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    if ($this->isTranslationDestination()) {
      $language = $destination_identifier['langcode'];
      $config = $this->language_manager->getLanguageConfigOverride($language, $this->config->getName());
      $config->delete();
    }
  }

}
