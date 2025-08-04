<?php

namespace Drupal\migrate\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\Discovery\AttributeDiscoveryWithAnnotationsAutomatedProviders;
use Drupal\migrate\Plugin\Discovery\ProviderFilterDecorator;

/**
 * Plugin manager for migrate source plugins.
 *
 * @see \Drupal\migrate\Plugin\MigrateSourceInterface
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 * @see \Drupal\migrate\Attribute\MigrateSource
 * @see plugin_api
 *
 * @ingroup migration
 */
class MigrateSourcePluginManager extends MigratePluginManager {

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
    parent::__construct($type, $namespaces, $cache_backend, $module_handler, MigrateSource::class, 'Drupal\migrate\Annotation\MigrateSource');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!$this->discovery) {
      $discovery = new AttributeDiscoveryWithAnnotationsAutomatedProviders(
        $this->subdir,
        $this->namespaces,
        $this->pluginDefinitionAttributeName,
        $this->pluginDefinitionAnnotationName,
        $this->additionalAnnotationNamespaces,
      );
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
   * @todo This provides backwards compatibility for migration source plugins
   *   using annotations and having more than one provider. This functionality
   *   will be deprecated with plugin discovery by annotations in
   *   https://www.drupal.org/project/drupal/issues/3522409.
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
