<?php

/**
 * @file
 * Contains \Drupal\user\PermissionHandler.
 */

namespace Drupal\user;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides the available permissions based on hook_permission and yml files.
 *
 * To define permissions you can use a $module.permissions.yml file:
 *
 * @code
 * 'access all views':
 *   title: 'Bypass views access control'
 *   description: 'Bypass access control when accessing views.'
 *   'restrict access': TRUE
 * @encode
 *
 * @see hook_permission()
 */
class PermissionHandler implements PermissionHandlerInterface {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The YAML discovery class to find all .permissions.yml files.
   *
   * @var \Drupal\Component\Discovery\YamlDiscovery
   */
  protected $yamlDiscovery;

  /**
   * Constructs a new PermissionHandler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(ModuleHandlerInterface $module_handler, TranslationInterface $string_translation) {
    // @todo It would be nice if you could pull all module directories from the
    //   container.
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Gets the YAML discovery.
   *
   * @return \Drupal\Component\Discovery\YamlDiscovery
   *   The YAML discovery.
   */
  protected function getYamlDiscovery() {
    if (!isset($this->yamlDiscovery)) {
      $this->yamlDiscovery = new YamlDiscovery('permissions', $this->moduleHandler->getModuleDirectories());
    }
    return $this->yamlDiscovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $all_permissions = $this->buildPermissionsYaml();

    $all_permissions += $this->buildPermissionsModules();

    return $this->sortPermissions($all_permissions);
  }

  /**
   * Builds all permissions provided by .permissions.yml files.
   *
   * @return array[]
   *   Each return permission is an array with the following keys:
   *   - title: The title of the permission.
   *   - description: The description of the permission, defaults to NULL.
   *   - provider: The provider of the permission.
   */
  protected function buildPermissionsYaml() {
    $all_permissions = array();
    foreach ($this->getYamlDiscovery()->findAll() as $provider => $permissions) {
      foreach ($permissions as &$permission) {
        if (!is_array($permission)) {
          $permission = array(
            'title' => $permission,
          );
        }
        $permission['title'] = $this->t($permission['title']);
        $permission['description'] = isset($permission['description']) ? $this->t($permission['description']) : NULL;
        $permission['provider'] = $provider;
      }
      $all_permissions += $permissions;
    }
    return $all_permissions;
  }

  /**
   * Builds all permissions provided by .module files.
   *
   * @return array[]
   *   Each return permission is an array with the following keys:
   *   - title: The title of the permission.
   *   - description: The description of the permission, defaults to NULL.
   *   - provider: The provider of the permission.
   */
  protected function buildPermissionsModules() {
    $all_permissions = array();
    foreach ($this->moduleHandler->getImplementations('permission') as $provider) {
      $permissions = $this->moduleHandler->invoke($provider, 'permission');
      foreach ($permissions as &$permission) {
        if (!is_array($permission)) {
          $permission = array(
            'title' => $permission,
            'description' => NULL,
          );
        }
        $permission['provider'] = $provider;
      }
      $all_permissions += $permissions;
    }
    return $all_permissions;
  }

  /**
   * Sorts the given permissions by provider name and title.
   *
   * @param array $all_permissions
   *   The permissions to be sorted.
   *
   * @return array[]
   *   Each return permission is an array with the following keys:
   *   - title: The title of the permission.
   *   - description: The description of the permission, defaults to NULL.
   *   - provider: The provider of the permission.
   */
  protected function sortPermissions(array $all_permissions = array()) {
    // Get a list of all the modules providing permissions and sort by
    // display name.
    $modules = $this->getModuleNames();

    uasort($all_permissions, function (array $permission_a, array $permission_b) use ($modules) {
      if ($modules[$permission_a['provider']] == $modules[$permission_b['provider']]) {
        return $permission_a['title'] > $permission_b['title'];
      }
      else {
        return $modules[$permission_a['provider']] > $modules[$permission_b['provider']];
      }
    });
    return $all_permissions;
  }

  /**
   * Returns all module names.
   *
   * @return string[]
   *   Returns the human readable names of all modules keyed by machine name.
   */
  protected function getModuleNames() {
    $modules = array();
    $module_info = $this->systemRebuildModuleData();
    foreach (array_keys($this->moduleHandler->getModuleList()) as $module) {
      $modules[$module] = $module_info[$module]->info['name'];
    }
    asort($modules);
    return $modules;
  }

  /**
   * Wraps system_rebuild_module_data()
   *
   * @return \Drupal\Core\Extension\Extension[]
   */
  protected function systemRebuildModuleData() {
    return system_rebuild_module_data();
  }

}
