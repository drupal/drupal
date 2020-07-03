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
 * Persists data to the config system.
 *
 * Available configuration keys:
 * - store null: (optional) Boolean, if TRUE, when a property is NULL, NULL is
 *   stored, otherwise the default is used. Defaults to FALSE.
 * - translations: (optional) Boolean, if TRUE, the destination will be
 *   associated with the langcode provided by the source plugin. Defaults to
 *   FALSE.
 *
 * Destination properties expected in the imported row:
 * - config_name: The machine name of the config.
 * - langcode: (optional) The language code of the config.
 *
 * Examples:
 *
 * @code
 * source:
 *   plugin: variable
 *   variables:
 *     - node_admin_theme
 * process:
 *   use_admin_theme: node_admin_theme
 * destination:
 *   plugin: config
 *   config_name: node.settings
 * @endcode
 *
 * This will add the value of the variable "node_admin_theme" to the config with
 * the machine name "node.settings" as "node.settings.use_admin_theme".
 *
 * @code
 * source:
 *   plugin: d6_variable_translation
 *   variables:
 *     - site_offline_message
 * process:
 *   langcode: language
 *   message: site_offline_message
 * destination:
 *   plugin: config
 *   config_name: system.maintenance
 *   translations: true
 * @endcode
 *
 * This will add the value of the variable "site_offline_message" to the config
 * with the machine name "system.maintenance" as "system.maintenance.message",
 * coupled with the relevant langcode as obtained from the
 * "d6_variable_translation" source plugin.
 *
 * @see \Drupal\migrate_drupal\Plugin\migrate\source\d6\VariableTranslation
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
  public function import(Row $row, array $old_destination_id_values = []) {
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
  public function fields() {
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

  /**
   * {@inheritdoc}
   */
  public function getDestinationModule() {
    if (!empty($this->configuration['destination_module'])) {
      return $this->configuration['destination_module'];
    }
    if (!empty($this->pluginDefinition['destination_module'])) {
      return $this->pluginDefinition['destination_module'];
    }
    // Config translations require the config_translation module so set the
    // migration provider to 'config_translation'. The corresponding non
    // translated configuration is expected to be handled in a separate
    // migration.
    if (isset($this->configuration['translations'])) {
      return 'config_translation';
    }
    // Get the module handling this configuration object from the config_name,
    // which is of the form <module_name>.<configuration object name>
    return !empty($this->configuration['config_name']) ? explode('.', $this->configuration['config_name'], 2)[0] : NULL;
  }

}
