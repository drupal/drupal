<?php

namespace Drupal\views\Plugin;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\PreWarmablePluginManagerTrait;
use Drupal\Core\PreWarm\PreWarmableInterface;
use Drupal\views\Plugin\views\HandlerBase;
use Drupal\views\Plugin\views\join\JoinPluginInterface;
use Drupal\views\Plugin\views\ViewsHandlerInterface;
use Drupal\views\ViewsData;
use Symfony\Component\DependencyInjection\Container;

/**
 * Plugin type manager for all views handlers.
 */
class ViewsHandlerManager extends DefaultPluginManager implements FallbackPluginManagerInterface, PreWarmableInterface {

  use PreWarmablePluginManagerTrait;

  /**
   * The views data cache.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * The handler type.
   *
   * @var string
   *
   * @see \Drupal\views\ViewExecutable::getHandlerTypes().
   */
  protected $handlerType;

  /**
   * Constructs a ViewsHandlerManager object.
   *
   * @param string $handler_type
   *   The plugin type, for example filter.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\views\ViewsData $views_data
   *   The views data cache.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct($handler_type, \Traversable $namespaces, ViewsData $views_data, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $plugin_definition_annotation_name = 'Drupal\views\Annotation\Views' . Container::camelize($handler_type);
    // Special handling until all views plugins have attribute classes.
    $attribute_name_candidate = 'Drupal\views\Attribute\Views' . Container::camelize($handler_type);
    $plugin_definition_attribute_name = class_exists($attribute_name_candidate) ? $attribute_name_candidate : Plugin::class;
    $plugin_interface = ViewsHandlerInterface::class;
    if ($handler_type == 'join') {
      $plugin_interface = JoinPluginInterface::class;
    }
    parent::__construct("Plugin/views/$handler_type", $namespaces, $module_handler, $plugin_interface, $plugin_definition_attribute_name, $plugin_definition_annotation_name);

    $this->setCacheBackend($cache_backend, "views:$handler_type");
    $this->alterInfo('views_plugins_' . $handler_type);

    $this->viewsData = $views_data;
    $this->handlerType = $handler_type;
    $this->defaults = [
      'plugin_type' => $handler_type,
    ];
  }

  /**
   * Fetches a handler from the data cache.
   *
   * @param array $item
   *   An associative array representing the handler to be retrieved:
   *   - table: The name of the table containing the handler.
   *   - field: The name of the field the handler represents.
   * @param string|null $override_plugin_id
   *   (optional) Override the actual handler object with this plugin ID. Used
   *   for aggregation when the handler is redirected to the aggregation
   *   handler.
   *
   * @return \Drupal\views\Plugin\views\ViewsHandlerInterface
   *   An instance of a handler object. May be a broken handler instance.
   */
  public function getHandler(array $item, ?string $override_plugin_id = NULL): ViewsHandlerInterface {
    $table = $item['table'];
    $field = $item['field'];
    // Get the plugin manager for this type.
    $data = $table ? $this->viewsData->get($table) : $this->viewsData->getAll();

    if (isset($data[$field][$this->handlerType])) {
      $definition = $data[$field][$this->handlerType];
      foreach (['group', 'title', 'title short', 'label', 'help', 'real field', 'real table', 'entity type', 'entity field'] as $key) {
        if (!isset($definition[$key])) {
          // First check the field level.
          if (!empty($data[$field][$key])) {
            $definition[$key] = $data[$field][$key];
          }
          // Then if that doesn't work, check the table level.
          elseif (!empty($data['table'][$key])) {
            $definition_key = $key === 'entity type' ? 'entity_type' : $key;
            $definition[$definition_key] = $data['table'][$key];
          }
        }
      }

      // When aggregation is enabled, particular plugins need to be
      // replaced in order to override the query with a query that
      // can run the aggregate counts, sums, or averages for example.
      // @see Drupal\views\Plugin\views\query\Sql::getAggregationInfo()
      // for example which aggressively overrides any filter used
      // by a number of mathematical-type queries regardless of the
      // original filter.
      $plugin_id = $override_plugin_id ?: $definition['id'];
      // Try to use the overridden handler.
      $handler = $this->createInstance($plugin_id, $definition);
      if ($override_plugin_id && method_exists($handler, 'broken') && $handler->broken()) {
        $handler = $this->createInstance($definition['id'], $definition);
      }
      return $handler;
    }

    // Finally, use the 'broken' handler.
    return $this->createInstance('broken', ['original_configuration' => $item]);
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $instance = parent::createInstance($plugin_id, $configuration);
    if ($instance instanceof HandlerBase) {
      $instance->setModuleHandler($this->moduleHandler);
      $instance->setViewsData($this->viewsData);
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'broken';
  }

}
