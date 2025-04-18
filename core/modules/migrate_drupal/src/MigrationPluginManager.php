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
   *   An array of migration tags that enforce source_module.
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

    $source_id = $definition['source']['plugin'];
    $source_definition = $this->sourceManager->getDefinition($source_id);
    // If the source plugin uses annotations, then the 'provider' key is the
    // array of providers and the 'providers' key is not defined.
    $providers = $source_definition['providers'] ?? $source_definition['provider'];

    // Check if the migration has any of the tags that trigger source_module
    // enforcement.
    $has_enforced_tags = !empty(array_intersect(
      $definition['migration_tags'] ?? [],
      $this->getEnforcedSourceModuleTags(),
    ));

    // If source_module is not defined in the migration, then check for it in
    // the source plugin.
    $has_source_module = !empty($definition['source']['source_module'])
      || !empty($source_definition['source_module']);

    $requires_migrate_drupal = in_array('migrate_drupal', $providers, TRUE);
    if ($requires_migrate_drupal && $has_enforced_tags && !$has_source_module) {
      throw new BadPluginDefinitionException($source_id, 'source_module');
    }

    if (!$requires_migrate_drupal && !$has_enforced_tags && $has_source_module) {
      @trigger_error("Setting the source_module property without the expected tags is deprecated in drupal:11.2.0 and will trigger an error in drupal:12.0.0. See https://www.drupal.org/node/3306373", E_USER_DEPRECATED);
    }
  }

}
