<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Menu\MenuRouterTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests menu router and default menu link functionality.
 *
 * @group Menu
 */
class MenuRouterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'menu_test', 'test_page_test'];

  /**
   * Name of the administrative theme to use for tests.
   *
   * @var string
   */
  protected $adminTheme;

  /**
   * Name of the default theme to use for tests.
   *
   * @var string
   */
  protected $defaultTheme;

  protected function setUp() {
    // Enable dummy module that implements hook_menu.
    parent::setUp();

    $this->drupalPlaceBlock('system_menu_block:tools');
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
   * Test local tasks with route placeholders.
   */
  protected function doTestHookMenuIntegration() {
    // Generate base path with random argument.
    $machine_name = $this->randomMachineName(8);
    $base_path = 'foo/' . $machine_name;
    $this->drupalGet($base_path);
    // Confirm correct controller activated.
    $this->assertText('test1');
    // Confirm local task links are displayed.
    $this->assertLink('Local task A');
    $this->assertLink('Local task B');
    // Confirm correct local task href.
    $this->assertLinkByHref(Url::fromRoute('menu_test.router_test1', ['bar' => $machine_name])->toString());
    $this->assertLinkByHref(Url::fromRoute('menu_test.router_test2', ['bar' => $machine_name])->toString());
  }

  /**
   * Test title callback set to FALSE.
   */
  protected function doTestTitleCallbackFalse() {
    $this->drupalGet('test-page');
    $this->assertText('A title with @placeholder', 'Raw text found on the page');
    $this->assertNoText(t('A title with @placeholder', array('@placeholder' => 'some other text')), 'Text with placeholder substitutions not found.');
  }

  /**
   * Tests page title of MENU_CALLBACKs.
   */
  protected function doTestTitleMenuCallback() {
    // Verify that the menu router item title is not visible.
    $this->drupalGet('');
    $this->assertNoText(t('Menu Callback Title'));
    // Verify that the menu router item title is output as page title.
    $this->drupalGet('menu_callback_title');
    $this->assertText(t('Menu Callback Title'));
  }

  /**
   * Tests menu item descriptions.
   */
  protected function doTestDescriptionMenuItems() {
    // Verify that the menu router item title is output as page title.
    $this->drupalGet('menu_callback_description');
    $this->assertText(t('Menu item description text'));
  }

  /**
   * Tests for menu_name parameter for default menu links.
   */
  protected function doTestMenuName() {
    $admin_user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($admin_user);
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $menu_links = $menu_link_manager->loadLinksByRoute('menu_test.menu_name_test');
    $menu_link = reset($menu_links);
    $this->assertEqual($menu_link->getMenuName(), 'original', 'Menu name is "original".');

    // Change the menu_name parameter in menu_test.module, then force a menu
    // rebuild.
    menu_test_menu_name('changed');
    $menu_link_manager->rebuild();

    $menu_links = $menu_link_manager->loadLinksByRoute('menu_test.menu_name_test');
    $menu_link = reset($menu_links);
    $this->assertEqual($menu_link->getMenuName(), 'changed', 'Menu name was successfully changed after rebuild.');
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
    $this->assertEqual($menu_link->getPluginId(), 'menu_test.custom', 'Menu links added at hook_menu_links_discovered_alter() obtain the machine name from the $links key.');
    // Make sure that rebuilding the menu tree does not produce duplicates of
    // links added by hook_menu_links_discovered_alter().
    \Drupal::service('router.builder')->rebuild();
    $this->drupalGet('menu-test');
    $this->assertUniqueText('Custom link', 'Menu links added by hook_menu_links_discovered_alter() do not duplicate after a menu rebuild.');
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
    $this->assertEqual($child_link->getParent(), $parent_link->getPluginId(), 'The parent of a directly attached child is correct.');
    $this->assertEqual($unattached_child_link->getParent(), $child_link->getPluginId(), 'The parent of a non-directly attached child is correct.');
  }

  /**
   * Test menu links that have optional placeholders.
   */
  protected function doTestMenuOptionalPlaceholders() {
    $this->drupalGet('menu-test/optional');
    $this->assertResponse(200);
    $this->assertText('Sometimes there is no placeholder.');

    $this->drupalGet('menu-test/optional/foobar');
    $this->assertResponse(200);
    $this->assertText("Sometimes there is a placeholder: 'foobar'.");
  }

  /**
   * Tests a menu on a router page.
   */
  protected function doTestMenuOnRoute() {
    \Drupal::service('module_installer')->install(array('router_test'));
    \Drupal::service('router.builder')->rebuild();
    $this->resetAll();

    $this->drupalGet('router_test/test2');
    $this->assertLinkByHref('menu_no_title_callback');
    $this->assertLinkByHref('menu-title-test/case1');
    $this->assertLinkByHref('menu-title-test/case2');
    $this->assertLinkByHref('menu-title-test/case3');
  }

  /**
   * Test path containing "exotic" characters.
   */
  protected function doTestExoticPath() {
    $path = "menu-test/ -._~!$'\"()*@[]?&+%#,;=:" . // "Special" ASCII characters.
      "%23%25%26%2B%2F%3F" . // Characters that look like a percent-escaped string.
      "éøïвβ中國書۞"; // Characters from various non-ASCII alphabets.
    $this->drupalGet($path);
    $this->assertRaw('This is the menuTestCallback content.');
  }

  /**
   * Make sure the maintenance mode can be bypassed using an EventSubscriber.
   *
   * @see \Drupal\menu_test\EventSubscriber\MaintenanceModeSubscriber::onKernelRequestMaintenance().
   */
  public function testMaintenanceModeLoginPaths() {
    $this->container->get('state')->set('system.maintenance_mode', TRUE);

    $offline_message = t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => $this->config('system.site')->get('name')));
    $this->drupalGet('test-page');
    $this->assertText($offline_message);
    $this->drupalGet('menu_login_callback');
    $this->assertText('This is TestControllers::testLogin.', 'Maintenance mode can be bypassed using an event subscriber.');

    $this->container->get('state')->set('system.maintenance_mode', FALSE);
  }

  /**
   * Test that an authenticated user hitting 'user/login' gets redirected to
   * 'user' and 'user/register' gets redirected to the user edit page.
   */
  public function testAuthUserUserLogin() {
    $web_user = $this->drupalCreateUser(array());
    $this->drupalLogin($web_user);

    $this->drupalGet('user/login');
    // Check that we got to 'user'.
    $this->assertUrl($this->loggedInUser->url('canonical', ['absolute' => TRUE]));

    // user/register should redirect to user/UID/edit.
    $this->drupalGet('user/register');
    $this->assertUrl($this->loggedInUser->url('edit-form', ['absolute' => TRUE]));
  }

  /**
   * Tests theme integration.
   */
  public function testThemeIntegration() {
    $this->defaultTheme = 'bartik';
    $this->adminTheme = 'seven';

    $theme_handler = $this->container->get('theme_handler');
    $theme_handler->install([$this->defaultTheme, $this->adminTheme]);
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
   * Test the theme negotiation when it is set to use an administrative theme.
   */
  protected function doTestThemeCallbackAdministrative() {
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertText('Active theme: seven. Actual theme: seven.', 'The administrative theme can be correctly set in a theme negotiation.');
    $this->assertRaw('seven/css/base/elements.css', "The administrative theme's CSS appears on the page.");
  }

  /**
   * Test the theme negotiation when the site is in maintenance mode.
   */
  protected function doTestThemeCallbackMaintenanceMode() {
    $this->container->get('state')->set('system.maintenance_mode', TRUE);

    // For a regular user, the fact that the site is in maintenance mode means
    // we expect the theme callback system to be bypassed entirely.
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertRaw('bartik/css/base/elements.css', "The maintenance theme's CSS appears on the page.");

    // An administrator, however, should continue to see the requested theme.
    $admin_user = $this->drupalCreateUser(array('access site in maintenance mode'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertText('Active theme: seven. Actual theme: seven.', 'The theme negotiation system is correctly triggered for an administrator when the site is in maintenance mode.');
    $this->assertRaw('seven/css/base/elements.css', "The administrative theme's CSS appears on the page.");

    $this->container->get('state')->set('system.maintenance_mode', FALSE);
  }

  /**
   * Test the theme negotiation when it is set to use an optional theme.
   */
  protected function doTestThemeCallbackOptionalTheme() {
    // Request a theme that is not installed.
    $this->drupalGet('menu-test/theme-callback/use-test-theme');
    $this->assertText('Active theme: bartik. Actual theme: bartik.', 'The theme negotiation system falls back on the default theme when a theme that is not installed is requested.');
    $this->assertRaw('bartik/css/base/elements.css', "The default theme's CSS appears on the page.");

    // Now install the theme and request it again.
    $theme_handler = $this->container->get('theme_handler');
    $theme_handler->install(array('test_theme'));

    $this->drupalGet('menu-test/theme-callback/use-test-theme');
    $this->assertText('Active theme: test_theme. Actual theme: test_theme.', 'The theme negotiation system uses an optional theme once it has been installed.');
    $this->assertRaw('test_theme/kitten.css', "The optional theme's CSS appears on the page.");

    $theme_handler->uninstall(array('test_theme'));
  }

  /**
   * Test the theme negotiation when it is set to use a theme that does not exist.
   */
  protected function doTestThemeCallbackFakeTheme() {
    $this->drupalGet('menu-test/theme-callback/use-fake-theme');
    $this->assertText('Active theme: bartik. Actual theme: bartik.', 'The theme negotiation system falls back on the default theme when a theme that does not exist is requested.');
    $this->assertRaw('bartik/css/base/elements.css', "The default theme's CSS appears on the page.");
  }

  /**
   * Test the theme negotiation when no theme is requested.
   */
  protected function doTestThemeCallbackNoThemeRequested() {
    $this->drupalGet('menu-test/theme-callback/no-theme-requested');
    $this->assertText('Active theme: bartik. Actual theme: bartik.', 'The theme negotiation system falls back on the default theme when no theme is requested.');
    $this->assertRaw('bartik/css/base/elements.css', "The default theme's CSS appears on the page.");
  }

}
