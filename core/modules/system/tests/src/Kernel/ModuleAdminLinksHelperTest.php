<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\system\ModuleAdminLinksHelper;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the ModuleAdminLinksHelper.
 */
#[CoversClass(ModuleAdminLinksHelper::class)]
#[Group('system')]
#[RunTestsInSeparateProcesses]
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
   * Tests get module admin links.
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
