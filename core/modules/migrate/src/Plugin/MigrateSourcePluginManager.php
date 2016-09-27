<?php

namespace Drupal\migrate\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate\Plugin\Discovery\AnnotatedClassDiscoveryAutomatedProviders;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\migrate\Plugin\Discovery\ProviderFilterDecorator;

/**
 * Plugin manager for migrate source plugins.
 *
 * @see \Drupal\migrate\Plugin\MigrateSourceInterface
 * @see \Drupal\migrate\Plugin\source\SourcePluginBase
 * @see \Drupal\migrate\Annotation\MigrateSource
 * @see plugin_api
 *
 * @ingroup migration
 */
class MigrateSourcePluginManager extends MigratePluginManager {

  /**
   * The class loader.
   *
   * @var object
   */
  protected $classLoader;

  /**
   * MigrateSourcePluginManager constructor.
   *
   * @param string $type
   *   The type of the plugin: row, source, process, destination, entity_field,
   *   id_map.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct($type, $namespaces, $cache_backend, $module_handler, 'Drupal\migrate\Annotation\MigrateSource');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!$this->discovery) {
      $discovery = new AnnotatedClassDiscoveryAutomatedProviders($this->subdir, $this->namespaces, $this->pluginDefinitionAnnotationName, $this->additionalAnnotationNamespaces);
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
    }
    return $this->discovery;
  }

  /**
   * Finds plugin definitions.
   *
   * @return array
   *   List of definitions to store in cache.
   *
   * @todo This is a temporary solution to the fact that migration source
   *   plugins have more than one provider. This functionality will be moved to
   *   core in https://www.drupal.org/node/2786355.
   */
  protected function findDefinitions() {
    $definitions = $this->getDiscovery()->getDefinitions();
    foreach ($definitions as $plugin_id => &$definition) {
      $this->processDefinition($definition, $plugin_id);
    }
    $this->alterDefinitions($definitions);
    return ProviderFilterDecorator::filterDefinitions($definitions, function ($provider) {
      return $this->providerExists($provider);
    });
  }

}
