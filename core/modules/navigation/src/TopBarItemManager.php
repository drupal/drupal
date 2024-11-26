<?php

declare(strict_types=1);

namespace Drupal\navigation;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\navigation\Attribute\TopBarItem;

/**
 * Top bar item plugin manager.
 */
final class TopBarItemManager extends DefaultPluginManager implements TopBarItemManagerInterface {

  /**
   * Constructs the object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/TopBarItem', $namespaces, $module_handler, TopBarItemPluginInterface::class, TopBarItem::class);
    $this->alterInfo('top_bar_item');
    $this->setCacheBackend($cache_backend, 'top_bar_item_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionsByRegion(TopBarRegion $region): array {
    return array_filter($this->getDefinitions(), fn (array $definition) => $definition['region'] === $region);
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderedTopBarItemsByRegion(TopBarRegion $region): array {
    $instances = [];
    foreach ($this->getDefinitionsByRegion($region) as $plugin_id => $plugin_definition) {
      $instances[$plugin_id] = $this->createInstance($plugin_id)->build();
    }

    return $instances;
  }

}
