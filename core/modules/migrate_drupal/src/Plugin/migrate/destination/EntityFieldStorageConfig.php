<?php

namespace Drupal\migrate_drupal\Plugin\migrate\destination;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\EntityFieldStorageConfig as BaseEntityFieldStorageConfig;

/**
 * Deprecated. Destination with Drupal specific config dependencies.
 *
 * @MigrateDestination(
 *   id = "md_entity:field_storage_config"
 * )
 *
 * @deprecated in Drupal 8.2.x and will be removed in Drupal 9.0.x. Use
 *   \Drupal\migrate\Plugin\migrate\destination\EntityFieldStorageConfig
 *   instead.
 *
 * @see \Drupal\migrate\Plugin\migrate\destination\EntityFieldStorageConfig
 */
class EntityFieldStorageConfig extends BaseEntityFieldStorageConfig {

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * Construct a new plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage for this entity type.
   * @param array $bundles
   *   The list of bundles this entity type has.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, FieldTypePluginManagerInterface $field_type_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $language_manager, $config_factory, $field_type_plugin_manager);
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_type_id = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity.manager')->getStorage($entity_type_id),
      array_keys($container->get('entity_type.bundle.info')->getBundleInfo($entity_type_id)),
      $container->get('language_manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->dependencies = parent::calculateDependencies();
    // Add a dependency on the module that provides the field type using the
    // source plugin configuration.
    $source_configuration = $this->migration->getSourceConfiguration();
    if (isset($source_configuration['constants']['type'])) {
      $field_type = $this->fieldTypePluginManager->getDefinition($source_configuration['constants']['type']);
      $this->addDependency('module', $field_type['provider']);
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getEntityTypeId($plugin_id) {
    return 'field_storage_config';
  }

}
