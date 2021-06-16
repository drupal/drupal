<?php

namespace Drupal\migrate_drupal;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\migrate\Plugin\Exception\BadPluginDefinitionException;
use Drupal\migrate\Plugin\MigrateSourcePluginManager;
use Drupal\migrate\Plugin\MigrationPluginManager as BaseMigrationPluginManager;

/**
 * Manages migration plugins.
 *
 * Analyzes migration definitions to ensure that the source plugin of any
 * migration tagged with particular tags ('Drupal 6' or 'Drupal 7' by default)
 * defines a source_module property in its plugin annotation. This is done in
 * order to support the Migrate Drupal UI, which needs to know which modules
 * "own" the data being migrated into Drupal 8, on both the source and
 * destination sides.
 *
 * @todo Enforce the destination_module property too, in
 * https://www.drupal.org/project/drupal/issues/2923810.
 */
class MigrationPluginManager extends BaseMigrationPluginManager {

  /**
   * The Migrate source plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrateSourcePluginManager
   */
  protected $sourceManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The migration tags which will trigger source_module enforcement.
   *
   * @var string[]
   */
  protected $enforcedSourceModuleTags;

  /**
   * MigrationPluginManager constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\migrate\Plugin\MigrateSourcePluginManager $source_manager
   *   The Migrate source plugin manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend, LanguageManagerInterface $language_manager, MigrateSourcePluginManager $source_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($module_handler, $cache_backend, $language_manager);
    $this->sourceManager = $source_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Returns the migration tags that trigger source_module enforcement.
   *
   * @return string[]
   */
  protected function getEnforcedSourceModuleTags() {
    if ($this->enforcedSourceModuleTags === NULL) {
      $this->enforcedSourceModuleTags = $this->configFactory
        ->get('migrate_drupal.settings')
        ->get('enforce_source_module_tags') ?: [];
    }
    return $this->enforcedSourceModuleTags;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // If the migration has no tags, we don't need to enforce the source_module
    // annotation property.
    if (empty($definition['migration_tags'])) {
      return;
    }

    // Check if the migration has any of the tags that trigger source_module
    // enforcement.
    $applied_tags = array_intersect($this->getEnforcedSourceModuleTags(), $definition['migration_tags']);
    if ($applied_tags) {
      // Throw an exception if the source plugin definition does not define a
      // source_module.
      $source_id = $definition['source']['plugin'];
      $source_definition = $this->sourceManager->getDefinition($source_id);
      if (empty($source_definition['source_module'])) {
        throw new BadPluginDefinitionException($source_id, 'source_module');
      }
    }
  }

}
