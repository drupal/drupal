<?php

namespace Drupal\views\Plugin;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\views\ViewsData;
use Symfony\Component\DependencyInjection\Container;
use Drupal\views\Plugin\views\HandlerBase;

/**
 * Plugin type manager for all views handlers.
 */
class ViewsHandlerManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

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
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\views\ViewsData $views_data
   *   The views data cache.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct($handler_type, \Traversable $namespaces, ViewsData $views_data, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $plugin_definition_annotation_name = 'Drupal\views\Annotation\Views' . Container::camelize($handler_type);
    $plugin_interface = 'Drupal\views\Plugin\views\ViewsHandlerInterface';
    if ($handler_type == 'join') {
      $plugin_interface = 'Drupal\views\Plugin\views\join\JoinPluginInterface';
    }
    parent::__construct("Plugin/views/$handler_type", $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    $this->setCacheBackend($cache_backend, "views:$handler_type");
    $this->alterInfo('views_plugins_' . $handler_type);

    $this->viewsData = $views_data;
    $this->handlerType = $handler_type;
    $this->defaults = array(
      'plugin_type' => $handler_type,
    );
  }

  /**
   * Fetches a handler from the data cache.
   *
   * @param array $item
   *   An associative array representing the handler to be retrieved:
   *   - table: The name of the table containing the handler.
   *   - field: The name of the field the handler represents.
   * @param string|null $override
   *   (optional) Override the actual handler object with this plugin ID. Used for
   *   aggregation when the handler is redirected to the aggregation handler.
   *
   * @return \Drupal\views\Plugin\views\ViewsHandlerInterface
   *   An instance of a handler object. May be a broken handler instance.
   */
  public function getHandler($item, $override = NULL) {
    $table = $item['table'];
    $field = $item['field'];
    // Get the plugin manager for this type.
    $data = $this->viewsData->get($table);

    if (isset($data[$field][$this->handlerType])) {
      $definition = $data[$field][$this->handlerType];
      foreach (array('group', 'title', 'title short', 'label', 'help', 'real field', 'real table', 'entity type', 'entity field') as $key) {
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

      // @todo This is crazy. Find a way to remove the override functionality.
      $plugin_id = $override ?: $definition['id'];
      // Try to use the overridden handler.
      $handler = $this->createInstance($plugin_id, $definition);
      if ($override && method_exists($handler, 'broken') && $handler->broken()) {
        $handler = $this->createInstance($definition['id'], $definition);
      }
      return $handler;
    }

    // Finally, use the 'broken' handler.
    return $this->createInstance('broken', array('original_configuration' => $item));
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
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
  public function getFallbackPluginId($plugin_id, array $configuration = array()) {
    return 'broken';
  }

}
