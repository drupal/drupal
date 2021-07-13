<?php

namespace Drupal\user;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Provides module permissions link.
 */
class ModulePermissionsLink {

  use StringTranslationTrait;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The user permissions handler service.
   *
   * @var \Drupal\user\PermissionHandlerInterface|null
   */
  protected $permissionHandler;

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Memory cache of processed menu tree elements.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeElement[]
   */
  protected $menuTree;

  /**
   * Constructs a new service instance.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The user permissions handler service.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(PermissionHandlerInterface $permission_handler, AccessManagerInterface $access_manager, ModuleHandlerInterface $module_handler) {
    $this->permissionHandler = $permission_handler;
    $this->accessManager = $access_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Generates a link pointing to a given module's permissions page section.
   *
   * @param string $module_name
   *   The module name.
   *
   * @return array
   *   A module permissions link as a render array or NULL if the module doesn't
   *   expose any permission or the current user cannot access it.
   */
  public function getModulePermissionsLink(string $module_name): ?array {
    if ($this->permissionHandler->moduleProvidesPermissions($module_name)) {
      if ($this->accessManager->checkNamedRoute('user.admin_permissions')) {
        $url = new Url('user.admin_permissions');
        $url->setOption('fragment', "module-{$module_name}");
        return [
          'title' => $this->t('Configure @module permissions', [
            '@module' => $this->moduleHandler->getName($module_name),
          ]),
          'description' => '',
          'url' => $url,
        ];
      }
    }
    return NULL;
  }

}
