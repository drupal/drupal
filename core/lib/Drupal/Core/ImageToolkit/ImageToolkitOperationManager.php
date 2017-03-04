<?php

namespace Drupal\Core\ImageToolkit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Utility\SafeMarkup;
use Psr\Log\LoggerInterface;

/**
 * Manages toolkit operation plugins.
 *
 * @see \Drupal\Core\ImageToolkit\Annotation\ImageToolkitOperation
 * @see \Drupal\Core\ImageToolkit\ImageToolkitOperationBase
 * @see \Drupal\Core\ImageToolkit\ImageToolkitOperationInterface
 * @see plugin_api
 */
class ImageToolkitOperationManager extends DefaultPluginManager implements ImageToolkitOperationManagerInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The image toolkit manager.
   *
   * @var \Drupal\Core\ImageToolkit\ImageToolkitManager
   */
  protected $toolkitManager;

  /**
   * Constructs the ImageToolkitOperationManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\ImageToolkit\ImageToolkitManager $toolkit_manager
   *   The image toolkit manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, LoggerInterface $logger, ImageToolkitManager $toolkit_manager) {
    parent::__construct('Plugin/ImageToolkit/Operation', $namespaces, $module_handler, 'Drupal\Core\ImageToolkit\ImageToolkitOperationInterface', 'Drupal\Core\ImageToolkit\Annotation\ImageToolkitOperation');

    $this->alterInfo('image_toolkit_operation');
    $this->setCacheBackend($cache_backend, 'image_toolkit_operation_plugins');
    $this->logger = $logger;
    $this->toolkitManager = $toolkit_manager;
  }

  /**
   * Returns the plugin ID for a given toolkit and operation.
   *
   * @param \Drupal\Core\ImageToolkit\ImageToolkitInterface $toolkit
   *   The toolkit instance.
   * @param string $operation
   *   The operation (e.g. "crop").
   *
   * @return string
   *   The plugin ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   When no plugin is available.
   */
  protected function getToolkitOperationPluginId(ImageToolkitInterface $toolkit, $operation) {
    $toolkit_id = $toolkit->getPluginId();
    $definitions = $this->getDefinitions();

    $definitions = array_filter($definitions,
      function ($definition) use ($toolkit_id, $operation) {
        return $definition['toolkit'] == $toolkit_id && $definition['operation'] == $operation;
      }
    );

    if (!$definitions) {
      // If this image toolkit plugin is a derivative and returns no operation,
      // try once again with its base plugin.
      $base_toolkit_id = $toolkit->getBaseId();
      if (($toolkit_id != $base_toolkit_id) && !empty($base_toolkit_id)) {
        $base_toolkit = $this->toolkitManager->createInstance($base_toolkit_id);
        return $this->getToolkitOperationPluginId($base_toolkit, $operation);
      }

      $message = SafeMarkup::format("No image operation plugin for '@toolkit' toolkit and '@operation' operation.", ['@toolkit' => $toolkit_id, '@operation' => $operation]);
      throw new PluginNotFoundException($toolkit_id . '.' . $operation, $message);
    }
    else {
      // Pickup the first plugin found.
      // @todo In https://www.drupal.org/node/2110591 we'll return here the UI
      //   selected plugin or the first found if missed.
      $definition = reset($definitions);
      return $definition['id'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = [], ImageToolkitInterface $toolkit = NULL) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    return new $plugin_class($configuration, $plugin_id, $plugin_definition, $toolkit, $this->logger);
  }

  /**
   * {@inheritdoc}
   */
  public function getToolkitOperation(ImageToolkitInterface $toolkit, $operation) {
    $plugin_id = $this->getToolkitOperationPluginId($toolkit, $operation);
    return $this->createInstance($plugin_id, [], $toolkit);
  }

}
