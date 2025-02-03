<?php

namespace Drupal\rest;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The REST resource config storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $resourceConfigStorage;

  /**
   * Constructs a new RestPermissions instance.
   *
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $rest_plugin_manager
   *   The rest resource plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ResourcePluginManager $rest_plugin_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->restPluginManager = $rest_plugin_manager;
    $this->resourceConfigStorage = $entity_type_manager->getStorage('rest_resource_config');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.rest'), $container->get('entity_type.manager'));
  }

  /**
   * Returns an array of REST permissions.
   *
   * @return array
   *   An array of permissions keyed by permission name.
   */
  public function permissions() {
    $permissions = [];
    /** @var \Drupal\rest\RestResourceConfigInterface[] $resource_configs */
    $resource_configs = $this->resourceConfigStorage->loadMultiple();
    foreach ($resource_configs as $resource_config) {
      $plugin = $resource_config->getResourcePlugin();

      // Add the rest resource configuration entity as a dependency to the
      // permissions.
      $permissions += array_map(function (array $permission_info) use ($resource_config) {
        $merge_info['dependencies'][$resource_config->getConfigDependencyKey()] = [
          $resource_config->getConfigDependencyName(),
        ];
        return NestedArray::mergeDeep($permission_info, $merge_info);
      }, $plugin->permissions());
    }
    return $permissions;
  }

}
