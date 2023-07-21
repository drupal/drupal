<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the ModuleAdminLinksHelper.
 *
 * @coversDefaultClass \Drupal\system\ModuleAdminLinksHelper
 * @group system
 */
class ModuleAdminLinksHelperTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
    'menu_link_content',
    'menu_test',
    'router_test',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->setUpCurrentUser([], [
      'access administration pages',
    ]);
    $this->installEntitySchema('menu_link_content');
  }

  /**
   * @covers ::getModuleAdminLinks
   */
  public function testGetModuleAdminLinks(): void {
    // Rebuild the menu links.
    $this->container->get('plugin.manager.menu.link')->rebuild();

    $adminLinksHelper = $this->container->get('system.module_admin_links_helper');

    // Test a module that has admin links.
    $links = $adminLinksHelper->getModuleAdminLinks('menu_test');

    $this->assertCount(1, $links);
    $this->assertEquals('menu_test.menu_name_test', $links[0]['url']->getRouteName());

    // Test a module that has no admin links.
    $links = $adminLinksHelper->getModuleAdminLinks('link');

    $this->assertCount(0, $links);
  }

}
