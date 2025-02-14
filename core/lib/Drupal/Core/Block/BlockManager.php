<?php

namespace Drupal\Core\Block;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\FilteredPluginManagerTrait;
use Psr\Log\LoggerInterface;

/**
 * Manages discovery and instantiation of block plugins.
 *
 * @todo Add documentation to this class.
 *
 * @see \Drupal\Core\Block\BlockPluginInterface
 */
class BlockManager extends DefaultPluginManager implements BlockManagerInterface, FallbackPluginManagerInterface {

  use CategorizingPluginManagerTrait {
    getSortedDefinitions as traitGetSortedDefinitions;
  }
  use FilteredPluginManagerTrait;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new \Drupal\Core\Block\BlockManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, LoggerInterface $logger) {
    parent::__construct('Plugin/Block', $namespaces, $module_handler, BlockPluginInterface::class, Block::class, 'Drupal\Core\Block\Annotation\Block');

    $this->alterInfo($this->getType());
    $this->setCacheBackend($cache_backend, 'block_plugins');
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  protected function getType() {
    return 'block';
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    $this->processDefinitionCategory($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(?array $definitions = NULL) {
    // Sort the plugins first by category, then by admin label.
    $definitions = $this->traitGetSortedDefinitions($definitions, 'admin_label');
    // Do not display the 'broken' plugin in the UI.
    unset($definitions['broken']);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'broken';
  }

  /**
   * {@inheritdoc}
   */
  protected function handlePluginNotFound($plugin_id, array $configuration) {
    $this->logger->warning('The "%plugin_id" block plugin was not found', ['%plugin_id' => $plugin_id]);
    return parent::handlePluginNotFound($plugin_id, $configuration);
  }

}
