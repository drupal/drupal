<?php

namespace Drupal\Core\Http;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Provides a default plugin manager for link relation types.
 *
 * @see \Drupal\Core\Http\LinkRelationTypeInterface
 */
class LinkRelationTypeManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'class' => LinkRelationType::class,
  ];

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Constructs a new LinkRelationTypeManager.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct($root, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache) {
    $this->root = $root;
    $this->pluginInterface = LinkRelationTypeInterface::class;
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache, 'link_relation_type_plugins', ['link_relation_type']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!$this->discovery) {
      $directories = ['core' => $this->root . '/core'];
      $directories += array_map(function (Extension $extension) {
        return $this->root . '/' . $extension->getPath();
      }, $this->moduleHandler->getModuleList());
      $this->discovery = new YamlDiscovery('link_relation_types', $directories);
    }
    return $this->discovery;
  }

}
