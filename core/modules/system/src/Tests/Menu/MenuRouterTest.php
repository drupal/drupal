<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Menu\MenuRouterTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\simpletest\WebTestBase;

/**
 * Tests menu router and default menu link functionality.
 */
class MenuRouterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'menu_test', 'test_page_test');

  /**
   * Name of the administrative theme to use for tests.
   *
   * @var string
   */
  protected $admin_theme;

  /**
   * Name of the default theme to use for tests.
   *
   * @var string
   */
  protected $default_theme;

  public static function getInfo() {
    return array(
      'name' => 'Menu router',
      'description' => 'Tests menu router and default menu links functionality.',
      'group' => 'Menu',
    );
  }

  function setUp() {
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
    $this->doTestMenuOnRoute();
    $this->doTestMenuName();
    $this->doTestMenuLinkDefaultsAlter();
    $this->doTestMenuItemTitlesCases();
    $this->doTestMenuLinkMaintain();
    $this->doTestMenuLinkOptions();
    $this->doTestMenuItemHooks();
    $this->doTestHookMenuIntegration();
    $this->doTestExoticPath();
  }

  /**
   * Test local tasks with route placeholders.
   */
  protected function doTestHookMenuIntegration() {
    // Generate base path with random argument.
    $base_path = 'foo/' . $this->randomName(8);
    $this->drupalGet($base_path);
    // Confirm correct controller activated.
    $this->assertText('test1');
    // Confirm local task links are displayed.
    $this->assertLink('Local task A');
    $this->assertLink('Local task B');
    // Confirm correct local task href.
    $this->assertLinkByHref(url($base_path));
    $this->assertLinkByHref(url($base_path . '/b'));
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
   * Tests for menu_link_maintain().
   */
  protected function doTestMenuLinkMaintain() {
    $admin_user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($admin_user);

    // Create three menu items.
    menu_link_maintain('menu_test', 'insert', 'menu_test_maintain/1', 'Menu link #1');
    menu_link_maintain('menu_test', 'insert', 'menu_test_maintain/1', 'Menu link #1-main');
    menu_link_maintain('menu_test', 'insert', 'menu_test_maintain/2', 'Menu link #2');

    // Move second link to the main-menu, to test caching later on.
    $menu_links_to_update = entity_load_multiple_by_properties('menu_link', array('link_title' => 'Menu link #1-main', 'customized' => 0, 'module' => 'menu_test'));
    foreach ($menu_links_to_update as $menu_link) {
      $menu_link->menu_name = 'main';
      $menu_link->save();
    }

    // Load front page.
    $this->drupalGet('');
    $this->assertLink('Menu link #1');
    $this->assertLink('Menu link #1-main');
    $this->assertLink('Menu link #2');

    // Rename all links for the given path.
    menu_link_maintain('menu_test', 'update', 'menu_test_maintain/1', 'Menu link updated');
    // Load a different page to be sure that we have up to date information.
    $this->drupalGet('menu_test_maintain/1');
    $this->assertLink('Menu link updated');
    $this->assertNoLink('Menu link #1');
    $this->assertNoLink('Menu link #1-main');
    $this->assertLink('Menu link #2');

    // Delete all links for the given path.
    menu_link_maintain('menu_test', 'delete', 'menu_test_maintain/1', '');
    // Load a different page to be sure that we have up to date information.
    $this->drupalGet('menu_test_maintain/2');
    $this->assertNoLink('Menu link updated');
    $this->assertNoLink('Menu link #1');
    $this->assertNoLink('Menu link #1-main');
    $this->assertLink('Menu link #2');
  }

  /**
   * Tests for menu_name parameter for default menu links.
   */
  protected function doTestMenuName() {
    $admin_user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($admin_user);

    $menu_links = entity_load_multiple_by_properties('menu_link', array('link_path' => 'menu_name_test'));
    $menu_link = reset($menu_links);
    $this->assertEqual($menu_link->menu_name, 'original', 'Menu name is "original".');

    // Change the menu_name parameter in menu_test.module, then force a menu
    // rebuild.
    menu_test_menu_name('changed');
    \Drupal::service('router.builder')->rebuild();

    $menu_links = entity_load_multiple_by_properties('menu_link', array('link_path' => 'menu_name_test'));
    $menu_link = reset($menu_links);
    $this->assertEqual($menu_link->menu_name, 'changed', 'Menu name was successfully changed after rebuild.');
  }

  /**
   * Tests menu links added in hook_menu_link_defaults_alter().
   */
  protected function doTestMenuLinkDefaultsAlter() {
    // Check that machine name does not need to be defined since it is already
    // set as the key of each menu link.
    $menu_links = entity_load_multiple_by_properties('menu_link', array('route_name' => 'menu_test.custom'));
    $menu_link = reset($menu_links);
    $this->assertEqual($menu_link->machine_name, 'menu_test.custom', 'Menu links added at hook_menu_link_defaults_alter() obtain the machine name from the $links key.');
    // Make sure that rebuilding the menu tree does not produce duplicates of
    // links added by hook_menu_link_defaults_alter().
    \Drupal::service('router.builder')->rebuild();
    $this->drupalGet('menu-test');
    $this->assertUniqueText('Custom link', 'Menu links added by hook_menu_link_defaults_alter() do not duplicate after a menu rebuild.');
  }

  /**
   * Tests for menu hierarchy.
   */
  protected function doTestMenuHierarchy() {
    $parent_links = entity_load_multiple_by_properties('menu_link', array('link_path' => 'menu-test/hierarchy/parent'));
    $parent_link = reset($parent_links);
    $child_links = entity_load_multiple_by_properties('menu_link', array('link_path' => 'menu-test/hierarchy/parent/child'));
    $child_link = reset($child_links);
    $unattached_child_links = entity_load_multiple_by_properties('menu_link', array('link_path' => 'menu-test/hierarchy/parent/child2/child'));
    $unattached_child_link = reset($unattached_child_links);

    $this->assertEqual($child_link['plid'], $parent_link['mlid'], 'The parent of a directly attached child is correct.');
    $this->assertEqual($unattached_child_link['plid'], $parent_link['mlid'], 'The parent of a non-directly attached child is correct.');
  }

  /**
   * Test menu maintenance hooks.
   */
  protected function doTestMenuItemHooks() {
    // Create an item.
    menu_link_maintain('menu_test', 'insert', 'menu_test_maintain/4', 'Menu link #4');
    $this->assertEqual(menu_test_static_variable(), 'insert', 'hook_menu_link_insert() fired correctly');
    // Update the item.
    menu_link_maintain('menu_test', 'update', 'menu_test_maintain/4', 'Menu link updated');
    $this->assertEqual(menu_test_static_variable(), 'update', 'hook_menu_link_update() fired correctly');
    // Delete the item.
    menu_link_maintain('menu_test', 'delete', 'menu_test_maintain/4', '');
    $this->assertEqual(menu_test_static_variable(), 'delete', 'hook_menu_link_delete() fired correctly');
  }

  /**
   * Test menu link 'options' storage and rendering.
   */
  protected function doTestMenuLinkOptions() {
    // Create a menu link with options.
    $menu_link = entity_create('menu_link', array(
      'link_title' => 'Menu link options test',
      'link_path' => 'test-page',
      'module' => 'menu_test',
      'options' => array(
        'attributes' => array(
          'title' => 'Test title attribute',
        ),
        'query' => array(
          'testparam' => 'testvalue',
        ),
      ),
    ));
    menu_link_save($menu_link);

    // Load front page.
    $this->drupalGet('test-page');
    $this->assertRaw('title="Test title attribute"', 'Title attribute of a menu link renders.');
    $this->assertRaw('testparam=testvalue', 'Query parameter added to menu link.');
  }

  /**
   * Tests the possible ways to set the title for menu items.
   * Also tests that menu item titles work with string overrides.
   */
  protected function doTestMenuItemTitlesCases() {

    // Build array with string overrides.
    $test_data = array(
      1 => array('Example title - Case 1' => 'Alternative example title - Case 1'),
      2 => array('Example title' => 'Alternative example title'),
      3 => array('Example title' => 'Alternative example title'),
    );

    foreach ($test_data as $case_no => $override) {
      $this->menuItemTitlesCasesHelper($case_no);
      $this->addCustomTranslations('en', array('' => $override));
      $this->writeCustomTranslations();

      $this->menuItemTitlesCasesHelper($case_no, TRUE);
      $this->addCustomTranslations('en', array());
      $this->writeCustomTranslations();
    }
  }

  /**
   * Get a URL and assert the title given a case number. If override is true,
   * the title is asserted to begin with "Alternative".
   */
  protected function menuItemTitlesCasesHelper($case_no, $override = FALSE) {
    $this->drupalGet('menu-title-test/case' . $case_no);
    $this->assertResponse(200);
    $asserted_title = $override ? 'Alternative example title - Case ' . $case_no : 'Example title - Case ' . $case_no;
    $this->assertTitle($asserted_title . ' | Drupal', format_string('Menu title is: %title.', array('%title' => $asserted_title)), 'Menu');
  }

  /**
   * Load the router for a given path.
   */
  protected function menuLoadRouter($router_path) {
    return db_query('SELECT * FROM {menu_router} WHERE path = :path', array(':path' => $router_path))->fetchAssoc();
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
    \Drupal::moduleHandler()->install(array('router_test'));
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
    $this->assertRaw('This is menu_test_callback().');
  }

  /**
   * Make sure the maintenance mode can be bypassed using an EventSubscriber.
   *
   * @see \Drupal\menu_test\EventSubscriber\MaintenanceModeSubscriber::onKernelRequestMaintenance().
   */
  public function testMaintenanceModeLoginPaths() {
    $this->container->get('state')->set('system.maintenance_mode', TRUE);

    $offline_message = t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => \Drupal::config('system.site')->get('name')));
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
    $this->assertTrue($this->url == url('user/' . $this->loggedInUser->id(), array('absolute' => TRUE)), "Logged-in user redirected to user on accessing user/login");

    // user/register should redirect to user/UID/edit.
    $this->drupalGet('user/register');
    $this->assertTrue($this->url == url('user/' . $this->loggedInUser->id() . '/edit', array('absolute' => TRUE)), "Logged-in user redirected to user/UID/edit on accessing user/register");
  }

  /**
   * Tests theme integration.
   */
  public function testThemeIntegration() {
    $this->default_theme = 'bartik';
    $this->admin_theme = 'seven';

    $theme_handler = $this->container->get('theme_handler');
    $theme_handler->enable(array($this->default_theme, $this->admin_theme));
    $this->container->get('config.factory')->get('system.theme')
      ->set('default', $this->default_theme)
      ->set('admin', $this->admin_theme)
      ->save();
    $theme_handler->disable(array('stark'));

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
    $this->assertRaw('seven/style.css', "The administrative theme's CSS appears on the page.");
  }

  /**
   * Test the theme negotiation when the site is in maintenance mode.
   */
  protected function doTestThemeCallbackMaintenanceMode() {
    $this->container->get('state')->set('system.maintenance_mode', TRUE);

    // For a regular user, the fact that the site is in maintenance mode means
    // we expect the theme callback system to be bypassed entirely.
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertRaw('bartik/css/style.css', "The maintenance theme's CSS appears on the page.");

    // An administrator, however, should continue to see the requested theme.
    $admin_user = $this->drupalCreateUser(array('access site in maintenance mode'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertText('Active theme: seven. Actual theme: seven.', 'The theme negotiation system is correctly triggered for an administrator when the site is in maintenance mode.');
    $this->assertRaw('seven/style.css', "The administrative theme's CSS appears on the page.");

    $this->container->get('state')->set('system.maintenance_mode', FALSE);
  }

  /**
   * Test the theme negotiation when it is set to use an optional theme.
   */
  protected function doTestThemeCallbackOptionalTheme() {
    // Request a theme that is not enabled.
    $this->drupalGet('menu-test/theme-callback/use-stark-theme');
    $this->assertText('Active theme: bartik. Actual theme: bartik.', 'The theme negotiation system falls back on the default theme when a theme that is not enabled is requested.');
    $this->assertRaw('bartik/css/style.css', "The default theme's CSS appears on the page.");

    // Now enable the theme and request it again.
    $theme_handler = $this->container->get('theme_handler');
    $theme_handler->enable(array('stark'));

    $this->drupalGet('menu-test/theme-callback/use-stark-theme');
    $this->assertText('Active theme: stark. Actual theme: stark.', 'The theme negotiation system uses an optional theme once it has been enabled.');
    $this->assertRaw('stark/css/layout.css', "The optional theme's CSS appears on the page.");

    $theme_handler->disable(array('stark'));
  }

  /**
   * Test the theme negotiation when it is set to use a theme that does not exist.
   */
  protected function doTestThemeCallbackFakeTheme() {
    $this->drupalGet('menu-test/theme-callback/use-fake-theme');
    $this->assertText('Active theme: bartik. Actual theme: bartik.', 'The theme negotiation system falls back on the default theme when a theme that does not exist is requested.');
    $this->assertRaw('bartik/css/style.css', "The default theme's CSS appears on the page.");
  }

  /**
   * Test the theme negotiation when no theme is requested.
   */
  protected function doTestThemeCallbackNoThemeRequested() {
    $this->drupalGet('menu-test/theme-callback/no-theme-requested');
    $this->assertText('Active theme: bartik. Actual theme: bartik.', 'The theme negotiation system falls back on the default theme when no theme is requested.');
    $this->assertRaw('bartik/css/style.css', "The default theme's CSS appears on the page.");
  }

}
