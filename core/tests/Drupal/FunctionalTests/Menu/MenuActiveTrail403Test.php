<?php

namespace Drupal\FunctionalTests\Menu;

use Drupal\Core\Url;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that 403 active trail behavior does not overwrite original entry.
 *
 * @group menu
 */
class MenuActiveTrail403Test extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user who can access both routes in our menu.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $privilegedUser;

  /**
   * The menu which active trail is tested.
   *
   * @var string
   */
  protected $menu = 'footer';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'block', 'menu_link_content'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Place the menu block, so menu trail gets built and cached. Showing the
    // menu starting from the second level, so it is only shown when the active
    // trail matches.
    $this->drupalPlaceBlock(
      'system_menu_block:' . $this->menu,
      [
       'level' => 2,
      ]
    );

    // Menu content, where the privileged user has access and the anonymous does
    // not have access. This route will be visited by both users. The privileged
    // user should see the menu with the menu content item created below.
    $parent_menu_link_content = MenuLinkContent::create([
      'title' => 'Admin overview',
      'menu_name' => 'footer',
      'link' => ['uri' => 'route:system.admin'],
    ]);
    $parent_menu_link_content->save();

    // Menu content for the second level, which should be shown in the menu for
    // the privileged user.
    $menu_link_content = MenuLinkContent::create([
      'title' => 'Link to Status page',
      'menu_name' => 'footer',
      'link' => ['uri' => 'route:system.status'],
      'parent' => 'menu_link_content:' . $parent_menu_link_content->uuid(),
    ]);
    $menu_link_content->save();

    $privileged_permissions = [
      'access administration pages',
      'administer site configuration',
    ];
    // Create a user who can access system.status and system.admin.
    $this->privilegedUser = $this->drupalCreateUser($privileged_permissions);
  }

  /**
   * Tests that visiting 403 page does not override original active trail.
   */
  public function testMenuActiveTrail403Cache() {
    $this->drupalLogin($this->privilegedUser);
    $system_status_url = Url::fromRoute('system.status');
    $this->drupalGet($system_status_url);
    // Check status code.
    $this->assertSession()->statusCodeEquals(200);
    // Check that our menu item to the status page is there.
    $this->assertSession()->pageTextContains('Link to Status page');

    $this->drupalLogout();
    // Visit the same page as anonymous.
    $this->drupalGet($system_status_url);
    // Check that the anonymous user gets a 403 page.
    $this->assertSession()->statusCodeEquals(403);

    // Visit the page again as privileged user. And check that the menu is still
    // printed.
    $this->drupalLogin($this->privilegedUser);
    $system_status_url = Url::fromRoute('system.status');
    $this->drupalGet($system_status_url);
    // Check status code.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Link to Status page');
  }

}
