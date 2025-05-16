<?php

declare(strict_types=1);

namespace Drupal\Tests\toolbar\Nightwatch;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\TestSite\TestSetupInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Sets up the site for testing the toolbar module.
 */
class ToolbarTestSetup implements TestSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function setup(): void {
    $module_installer = \Drupal::service('module_installer');
    assert($module_installer instanceof ModuleInstallerInterface);
    $module_installer->install(['toolbar']);

    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    foreach ([
      'access toolbar',
      'access administration pages',
      'administer modules',
      'administer site configuration',
      'administer account settings',
    ] as $permission) {
      $role->grantPermission($permission);
    }
    $role->save();
  }

}
