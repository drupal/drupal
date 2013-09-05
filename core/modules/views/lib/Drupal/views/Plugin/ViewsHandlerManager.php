<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\ViewsHandlerManager.
 */

namespace Drupal\views\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\views\Plugin\Discovery\ViewsHandlerDiscovery;
use Drupal\views\ViewsData;

/**
 * Plugin type manager for all views handlers.
 */
class ViewsHandlerManager extends PluginManagerBase {

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
   * @see \Drupal\views\ViewExecutable::viewsHandlerTypes().
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
   */
  public function __construct($handler_type, \Traversable $namespaces, ViewsData $views_data) {
    $this->discovery = new ViewsHandlerDiscovery($handler_type, $namespaces);
    $this->discovery = new CacheDecorator($this->discovery, "views:$handler_type", 'views_info');

    $this->factory = new ContainerFactory($this);

    $this->viewsData = $views_data;
    $this->handlerType = $handler_type;
  }


  /**
   * Fetches a handler from the data cache.
   *
   * @param array $item
   *   An associative array representing the handler to be retrieved:
   *   - table: The name of the table containing the handler.
   *   - field: The name of the field the handler represents.
   *   - optional: (optional) Whether or not this handler is optional. If a
   *     handler is missing and not optional, a debug message will be displayed.
   *     Defaults to FALSE.
   * @param string|null $override
   *   (optional) Override the actual handler object with this plugin ID. Used for
   *   aggregation when the handler is redirected to the aggregation handler.
   *
   * @return \Drupal\views\Plugin\views\HandlerBase
   *   An instance of a handler object. May be a broken handler instance.
   */
  public function getHandler($item, $override = NULL) {
    $table = $item['table'];
    $field = $item['field'];
    $optional = !empty($item['optional']);
    // Get the plugin manager for this type.
    $data = $this->viewsData->get($table);

    if (isset($data[$field][$this->handlerType])) {
      $definition = $data[$field][$this->handlerType];
      foreach (array('group', 'title', 'title short', 'help', 'real field', 'real table') as $key) {
        if (!isset($definition[$key])) {
          // First check the field level.
          if (!empty($data[$field][$key])) {
            $definition[$key] = $data[$field][$key];
          }
          // Then if that doesn't work, check the table level.
          elseif (!empty($data['table'][$key])) {
            $definition[$key] = $data['table'][$key];
          }
        }
      }

      // @todo This is crazy. Find a way to remove the override functionality.
      $plugin_id = $override ? : $definition['id'];
      // Try to use the overridden handler.
      try {
        return $this->createInstance($plugin_id, $definition);
      }
      catch (PluginException $e) {
        // If that fails, use the original handler.
        try {
          return $this->createInstance($definition['id'], $definition);
        }
        catch (PluginException $e) {
          // Deliberately empty, this case is handled generically below.
        }
      }
    }

    if (!$optional) {
      // debug(t("Missing handler: @table @field @type", array('@table' => $table, '@field' => $field, '@type' => $this->handlerType)));
    }

    // Finally, use the 'broken' handler.
    return $this->createInstance('broken', array('optional' => $optional, 'original_configuration' => $item));
  }

}
