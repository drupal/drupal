<?php

namespace Drupal\Tests\node\Functional;

use Drupal\user\RoleInterface;

/**
 * Tests the interaction of the node access system with menu links.
 *
 * @group node
 */
class NodeAccessMenuLinkTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['menu_ui', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to manage menu links and create nodes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $contentAdminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('system_menu_block:main');

    $this->contentAdminUser = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'bypass node access',
      'administer menu',
    ]);

    $this->config('user.role.' . RoleInterface::ANONYMOUS_ID)->set('permissions', [])->save();
  }

  /**
   * SA-CORE-2015-003: Tests menu links to nodes when node access is restricted.
   */
  public function testNodeAccessMenuLink() {

    $menu_link_title = $this->randomString();

    $this->drupalLogin($this->contentAdminUser);
    $edit = [
      'title[0][value]' => $this->randomString(),
      'body[0][value]' => $this->randomString(),
      'menu[enabled]' => 1,
      'menu[title]' => $menu_link_title,
    ];
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->linkExists($menu_link_title);

    // Ensure anonymous users without "access content" permission do not see
    // this menu link.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertSession()->linkNotExists($menu_link_title);

    // Ensure anonymous users with "access content" permission see this menu
    // link.
    $this->config('user.role.' . RoleInterface::ANONYMOUS_ID)->set('permissions', ['access content'])->save();
    $this->drupalGet('');
    $this->assertSession()->linkExists($menu_link_title);
  }

}
