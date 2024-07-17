<?php

declare(strict_types=1);

namespace Drupal\user;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Provides a helper for generating module permissions links.
 */
class ModulePermissionsLinkHelper {

  use StringTranslationTrait;

  /**
   * Constructs a new service instance.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permissionHandler
   *   The user permissions handler service.
   * @param \Drupal\Core\Access\AccessManagerInterface $accessManager
   *   The access manager service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module handler service.
   */
  public function __construct(
    protected PermissionHandlerInterface $permissionHandler,
    protected AccessManagerInterface $accessManager,
    protected ModuleExtensionList $moduleExtensionList,
  ) {}

  /**
   * Generates a link pointing to a given module's permissions page section.
   *
   * @param string $module
   *   The module name.
   * @param string $name
   *   The module display name.
   *
   * @return array|null
   *   A module permissions link as a render array or NULL if the module doesn't
   *   expose any permission or the current user cannot access it.
   */
  public function getModulePermissionsLink(string $module, string $name): ?array {
    if ($this->permissionHandler->moduleProvidesPermissions($module)) {
      if ($this->accessManager->checkNamedRoute('user.admin_permissions.module', ['modules' => $module])) {
        $url = new Url('user.admin_permissions.module', ['modules' => $module]);
        return [
          'title' => $this->t('Configure @module permissions', ['@module' => $name]),
          'description' => '',
          'url' => $url,
        ];
      }
    }
    return NULL;
  }

}
