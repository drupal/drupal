<?php

namespace Drupal\Tests\system\Functional\Menu;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests menu router and default menu link functionality.
 *
 * @group Menu
 */
class MenuRouterTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'menu_test', 'test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Name of the administrative theme to use for tests.
   *
   * @var string
   */
  protected $adminTheme;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Enable dummy module that implements hook_menu.
    parent::setUp();

    $this->drupalPlaceBlock('system_menu_block:tools');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests menu integration.
   */
  public function testMenuIntegration() {
    $this->doTestTitleMenuCallback();
    $this->doTestMenuOptionalPlaceholders();
    $this->doTestMenuHierarchy();
    $this->doTestMenuOnRoute();
    $this->doTestMenuName();
    $this->doTestMenuLinksDiscoveredAlter();
    $this->doTestHookMenuIntegration();
    $this->doTestExoticPath();
  }

  /**
   * Tests local tasks with route placeholders.
   */
  protected function doTestHookMenuIntegration() {
    // Generate base path with random argument.
    $machine_name = $this->randomMachineName(8);
    $base_path = 'foo/' . $machine_name;
    $this->drupalGet($base_path);
    // Confirm correct controller activated.
    $this->assertSession()->pageTextContains('test1');
    // Confirm local task links are displayed.
    $this->assertSession()->linkExists('Local task A');
    $this->assertSession()->linkExists('Local task B');
    $this->assertSession()->linkNotExists('Local task C');
    $this->assertSession()->assertEscaped("<script>alert('Welcome to the jungle!')</script>");
    // Confirm correct local task href.
    $this->assertSession()->linkByHrefExists(Url::fromRoute('menu_test.router_test1', ['bar' => $machine_name])->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('menu_test.router_test2', ['bar' => $machine_name])->toString());
  }

  /**
   * Tests title callback set to FALSE.
   */
  protected function doTestTitleCallbackFalse() {
    $this->drupalGet('test-page');
    $this->assertSession()->pageTextContains('A title with @placeholder');
    $this->assertSession()->pageTextNotContains('A title with some other text');
  }

  /**
   * Tests page title of MENU_CALLBACKs.
   */
  protected function doTestTitleMenuCallback() {
    // Verify that the menu router item title is not visible.
    $this->drupalGet('');
    $this->assertSession()->pageTextNotContains('Menu Callback Title');
    // Verify that the menu router item title is output as page title.
    $this->drupalGet('menu_callback_title');
    $this->assertSession()->pageTextContains('Menu Callback Title');
  }

  /**
   * Tests menu item descriptions.
   */
  protected function doTestDescriptionMenuItems() {
    // Verify that the menu router item title is output as page title.
    $this->drupalGet('menu_callback_description');
    $this->assertSession()->pageTextContains('Menu item description text');
  }

  /**
   * Tests for menu_name parameter for default menu links.
   */
  protected function doTestMenuName() {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $menu_links = $menu_link_manager->loadLinksByRoute('menu_test.menu_name_test');
    $menu_link = reset($menu_links);
    $this->assertEquals('original', $menu_link->getMenuName(), 'Menu name is "original".');

    // Change the menu_name parameter in menu_test.module, then force a menu
    // rebuild.
    menu_test_menu_name('changed');
    $menu_link_manager->rebuild();

    $menu_links = $menu_link_manager->loadLinksByRoute('menu_test.menu_name_test');
    $menu_link = reset($menu_links);
    $this->assertEquals('changed', $menu_link->getMenuName(), 'Menu name was successfully changed after rebuild.');
  }

  /**
   * Tests menu links added in hook_menu_links_discovered_alter().
   */
  protected function doTestMenuLinksDiscoveredAlter() {
    // Check that machine name does not need to be defined since it is already
    // set as the key of each menu link.
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $menu_links = $menu_link_manager->loadLinksByRoute('menu_test.custom');
    $menu_link = reset($menu_links);
    $this->assertEquals('menu_test.custom', $menu_link->getPluginId(), 'Menu links added at hook_menu_links_discovered_alter() obtain the machine name from the $links key.');
    // Make sure that rebuilding the menu tree does not produce duplicates of
    // links added by hook_menu_links_discovered_alter().
    $this->drupalGet('menu-test');
    $this->assertSession()->pageTextContainsOnce('Custom link');
  }

  /**
   * Tests for menu hierarchy.
   */
  protected function doTestMenuHierarchy() {
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $menu_links = $menu_link_manager->loadLinksByRoute('menu_test.hierarchy_parent');
    $parent_link = reset($menu_links);
    $menu_links = $menu_link_manager->loadLinksByRoute('menu_test.hierarchy_parent_child');
    $child_link = reset($menu_links);
    $menu_links = $menu_link_manager->loadLinksByRoute('menu_test.hierarchy_parent_child2');
    $unattached_child_link = reset($menu_links);
    $this->assertEquals($parent_link->getPluginId(), $child_link->getParent(), 'The parent of a directly attached child is correct.');
    $this->assertEquals($child_link->getPluginId(), $unattached_child_link->getParent(), 'The parent of a non-directly attached child is correct.');
  }

  /**
   * Tests menu links that have optional placeholders.
   */
  protected function doTestMenuOptionalPlaceholders() {
    $this->drupalGet('menu-test/optional');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Sometimes there is no placeholder.');

    $this->drupalGet('menu-test/optional/foobar');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("Sometimes there is a placeholder: 'foobar'.");
  }

  /**
   * Tests a menu on a router page.
   */
  protected function doTestMenuOnRoute() {
    \Drupal::service('module_installer')->install(['router_test']);
    $this->resetAll();

    $this->drupalGet('router_test/test2');
    $this->assertSession()->linkByHrefExists('menu_no_title_callback');
    $this->assertSession()->linkByHrefExists('menu-title-test/case1');
    $this->assertSession()->linkByHrefExists('menu-title-test/case2');
    $this->assertSession()->linkByHrefExists('menu-title-test/case3');
  }

  /**
   * Tests path containing "exotic" characters.
   */
  protected function doTestExoticPath() {
    // "Special" ASCII characters.
    $path =
      "menu-test/ -._~!$'\"()*@[]?&+%#,;=:" .
      // Characters that look like a percent-escaped string.
      "%23%25%26%2B%2F%3F" .
      // Characters from various non-ASCII alphabets.
      // cSpell:disable-next-line
      "éøïвβ中國書۞";
    $this->drupalGet($path);
    $this->assertSession()->pageTextContains('This is the menuTestCallback content.');
    $this->assertSession()->pageTextNotContains('The website encountered an unexpected error. Try again later.');
  }

  /**
   * Make sure the maintenance mode can be bypassed using an EventSubscriber.
   *
   * @see \Drupal\menu_test\EventSubscriber\MaintenanceModeSubscriber::onKernelRequestMaintenance()
   */
  public function testMaintenanceModeLoginPaths() {
    $this->container->get('state')->set('system.maintenance_mode', TRUE);

    $offline_message = $this->config('system.site')->get('name') . ' is currently under maintenance. We should be back shortly. Thank you for your patience.';
    $this->drupalGet('test-page');
    $this->assertSession()->pageTextContains($offline_message);
    $this->drupalGet('menu_login_callback');
    $this->assertSession()->pageTextContains('This is TestControllers::testLogin.');

    $this->container->get('state')->set('system.maintenance_mode', FALSE);
  }

  /**
   * Tests authenticated user login redirects.
   *
   * An authenticated user hitting 'user/login' should be redirected to 'user',
   * and 'user/register' should be redirected to the user edit page.
   */
  public function testAuthUserUserLogin() {
    $web_user = $this->drupalCreateUser([]);
    $this->drupalLogin($web_user);

    $this->drupalGet('user/login');
    // Check that we got to 'user'.
    $this->assertSession()->addressEquals($this->loggedInUser->toUrl('canonical'));

    // user/register should redirect to user/UID/edit.
    $this->drupalGet('user/register');
    $this->assertSession()->addressEquals($this->loggedInUser->toUrl('edit-form'));
  }

  /**
   * Tests theme integration.
   */
  public function testThemeIntegration() {
    $this->defaultTheme = 'olivero';
    $this->adminTheme = 'claro';

    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = $this->container->get('theme_installer');
    $theme_installer->install([$this->defaultTheme, $this->adminTheme]);
    $this->config('system.theme')
      ->set('default', $this->defaultTheme)
      ->set('admin', $this->adminTheme)
      ->save();

    $this->doTestThemeCallbackMaintenanceMode();

    $this->doTestThemeCallbackFakeTheme();

    $this->doTestThemeCallbackAdministrative();

    $this->doTestThemeCallbackNoThemeRequested();

    $this->doTestThemeCallbackOptionalTheme();
  }

  /**
   * Tests theme negotiation for an administrative theme.
   */
  protected function doTestThemeCallbackAdministrative() {
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertSession()->pageTextContains('Active theme: claro. Actual theme: claro.');
    $this->assertSession()->responseContains('claro/css/base/elements.css');
  }

  /**
   * Tests the theme negotiation when the site is in maintenance mode.
   */
  protected function doTestThemeCallbackMaintenanceMode() {
    $this->container->get('state')->set('system.maintenance_mode', TRUE);

    // For a regular user, the fact that the site is in maintenance mode means
    // we expect the theme callback system to be bypassed entirely.
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    // Check that the maintenance theme's CSS appears on the page.
    $this->assertSession()->responseContains('olivero/css/base/base.css');

    // An administrator, however, should continue to see the requested theme.
    $admin_user = $this->drupalCreateUser(['access site in maintenance mode']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertSession()->pageTextContains('Active theme: claro. Actual theme: claro.');
    // Check that the administrative theme's CSS appears on the page.
    $this->assertSession()->responseContains('claro/css/base/elements.css');

    $this->container->get('state')->set('system.maintenance_mode', FALSE);
  }

  /**
   * Tests the theme negotiation when it is set to use an optional theme.
   */
  protected function doTestThemeCallbackOptionalTheme() {
    // Request a theme that is not installed.
    $this->drupalGet('menu-test/theme-callback/use-test-theme');
    $this->assertSession()->pageTextContains('Active theme: olivero. Actual theme: olivero.');
    // Check that the default theme's CSS appears on the page.
    $this->assertSession()->responseContains('olivero/css/base/base.css');

    // Now install the theme and request it again.
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = $this->container->get('theme_installer');
    $theme_installer->install(['test_theme']);

    $this->drupalGet('menu-test/theme-callback/use-test-theme');
    $this->assertSession()->pageTextContains('Active theme: test_theme. Actual theme: test_theme.');
    // Check that the optional theme's CSS appears on the page.
    $this->assertSession()->responseContains('test_theme/kitten.css');

    $theme_installer->uninstall(['test_theme']);
  }

  /**
   * Tests the theme negotiation when it is set to use a theme that does not exist.
   */
  protected function doTestThemeCallbackFakeTheme() {
    $this->drupalGet('menu-test/theme-callback/use-fake-theme');
    $this->assertSession()->pageTextContains('Active theme: olivero. Actual theme: olivero.');
    // Check that the default theme's CSS appears on the page.
    $this->assertSession()->responseContains('olivero/css/base/base.css');
  }

  /**
   * Tests the theme negotiation when no theme is requested.
   */
  protected function doTestThemeCallbackNoThemeRequested() {
    $this->drupalGet('menu-test/theme-callback/no-theme-requested');
    $this->assertSession()->pageTextContains('Active theme: olivero. Actual theme: olivero.');
    // Check that the default theme's CSS appears on the page.
    $this->assertSession()->responseContains('olivero/css/base/base.css');
  }

}
