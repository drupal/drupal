<?php

namespace Drupal\search\Plugin\migrate\destination;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;
use Drupal\migrate\Row;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migrate destination for search page.
 *
 * @MigrateDestination(
 *   id = "entity:search_page"
 * )
 */
class EntitySearchPage extends EntityConfigBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new EntitySearchPage.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\plugin\MigrationInterface $migration
   *   The migration.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage for this entity type.
   * @param array $bundles
   *   The list of bundles this entity type has.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $language_manager, $config_factory);
    $this->moduleHandler = $module_handler;
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
      $container->get('entity_type.manager')->getStorage($entity_type_id),
      array_keys($container->get('entity_type.bundle.info')->getBundleInfo($entity_type_id)),
      $container->get('language_manager'),
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // The search page settings may be for a module not enabled on the
    // destination so make sure it is enabled for updating search page settings.
    if ($this->moduleHandler->moduleExists($row->getDestinationProperty('module'))) {
      return parent::import($row, $old_destination_id_values);
    }
    $msg = sprintf("Search module '%s' is not enabled on this site.", $row->getDestinationProperty('module'));
    throw new MigrateException($msg, 0, NULL, MigrationInterface::MESSAGE_INFORMATIONAL, MigrateIdMapInterface::STATUS_IGNORED);
  }

  /**
   * {@inheritdoc}
   */
  protected function updateEntity(EntityInterface $entity, Row $row) {
    parent::updateEntity($entity, $row);
    $entity->setPlugin($row->getDestinationProperty('plugin'));
    // The user_search plugin does not have a setConfiguration() method.
    $plugin = $entity->getPlugin();
    if ($plugin instanceof ConfigurableSearchPluginBase) {
      $plugin->setConfiguration($row->getDestinationProperty('configuration'));
    }
    return $entity;
  }

}
