<?php

namespace Drupal\rest;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides rest module permissions.
 */
class RestPermissions implements ContainerInjectionInterface {

  /**
   * The rest resource plugin manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $restPluginManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new RestPermissions instance.
   *
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $rest_plugin_manager
   *   The rest resource plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ResourcePluginManager $rest_plugin_manager, ConfigFactoryInterface $config_factory) {
    $this->restPluginManager = $rest_plugin_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.rest'), $container->get('config.factory'));
  }

  /**
   * Returns an array of REST permissions.
   *
   * @return array
   */
  public function permissions() {
    $permissions = [];
    $resources = $this->configFactory->get('rest.settings')->get('resources');
    if ($resources && $enabled = array_intersect_key($this->restPluginManager->getDefinitions(), $resources)) {
      foreach ($enabled as $key => $resource) {
        $plugin = $this->restPluginManager->getInstance(['id' => $key]);
        $permissions = array_merge($permissions, $plugin->permissions());
      }
    }
    return $permissions;
  }

}
