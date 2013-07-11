<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Menu\MenuRouterTest.
 */

namespace Drupal\system\Tests\Menu;

use PDO;
use Drupal\simpletest\WebTestBase;

/**
 * Tests menu router and hook_menu() functionality.
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

  /**
   * Name of an alternate theme to use for tests.
   *
   * @var string
   */
  protected $alternate_theme;

  public static function getInfo() {
    return array(
      'name' => 'Menu router',
      'description' => 'Tests menu router and hook_menu() functionality.',
      'group' => 'Menu',
    );
  }

  function setUp() {
    // Enable dummy module that implements hook_menu.
    parent::setUp();

    // Explicitly set the default and admin themes.
    $this->default_theme = 'bartik';
    $this->admin_theme = 'seven';
    $this->alternate_theme = 'stark';
    theme_enable(array($this->default_theme));
    config('system.theme')
      ->set('default', $this->default_theme)
      ->set('admin', $this->admin_theme)
      ->save();
    theme_disable(array($this->alternate_theme));
    $this->drupalPlaceBlock('system_menu_block:menu-tools');
  }

  /**
   * Test local tasks with route placeholders.
   */
  public function testHookMenuIntegration() {
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
  function testTitleCallbackFalse() {
    $this->drupalGet('test-page');
    $this->assertText('A title with @placeholder', 'Raw text found on the page');
    $this->assertNoText(t('A title with @placeholder', array('@placeholder' => 'some other text')), 'Text with placeholder substitutions not found.');
  }

  /**
   * Tests page title of MENU_CALLBACKs.
   */
  function testTitleMenuCallback() {
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
  function testDescriptionMenuItems() {
    // Verify that the menu router item title is output as page title.
    $this->drupalGet('menu_callback_description');
    $this->assertText(t('Menu item description text'));
    $this->assertRaw(check_plain('<strong>Menu item description arguments</strong>'));
  }

  /**
   * Test the theme callback when it is set to use an administrative theme.
   */
  function testThemeCallbackAdministrative() {
    theme_enable(array($this->admin_theme));
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertText('Custom theme: seven. Actual theme: seven.', 'The administrative theme can be correctly set in a theme callback.');
    $this->assertRaw('seven/style.css', "The administrative theme's CSS appears on the page.");
  }

  /**
   * Test that the theme callback is properly inherited.
   */
  function testThemeCallbackInheritance() {
    theme_enable(array($this->admin_theme));
    $this->drupalGet('menu-test/theme-callback/use-admin-theme/inheritance');
    $this->assertText('Custom theme: seven. Actual theme: seven. Theme callback inheritance is being tested.', 'Theme callback inheritance correctly uses the administrative theme.');
    $this->assertRaw('seven/style.css', "The administrative theme's CSS appears on the page.");
  }

  /**
   * Test that 'page callback', 'file' and 'file path' keys are properly
   * inherited from parent menu paths.
   */
  function testFileInheritance() {
    $this->drupalGet('admin/config/development/file-inheritance');
    $this->assertText('File inheritance test description', 'File inheritance works.');
  }

  /**
   * Test path containing "exotic" characters.
   */
  function testExoticPath() {
    $path = "menu-test/ -._~!$'\"()*@[]?&+%#,;=:" . // "Special" ASCII characters.
      "%23%25%26%2B%2F%3F" . // Characters that look like a percent-escaped string.
      "éøïвβ中國書۞"; // Characters from various non-ASCII alphabets.
    $this->drupalGet($path);
    $this->assertRaw('This is menu_test_callback().');
  }

  /**
   * Test the theme callback when the site is in maintenance mode.
   */
  function testThemeCallbackMaintenanceMode() {
    config('system.maintenance')->set('enabled', 1)->save();
    theme_enable(array($this->admin_theme));

    // For a regular user, the fact that the site is in maintenance mode means
    // we expect the theme callback system to be bypassed entirely.
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertRaw('bartik/css/style.css', "The maintenance theme's CSS appears on the page.");

    // An administrator, however, should continue to see the requested theme.
    $admin_user = $this->drupalCreateUser(array('access site in maintenance mode'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertText('Custom theme: seven. Actual theme: seven.', 'The theme callback system is correctly triggered for an administrator when the site is in maintenance mode.');
    $this->assertRaw('seven/style.css', "The administrative theme's CSS appears on the page.");

    config('system.maintenance')->set('enabled', 0)->save();
  }

  /**
   * Make sure the maintenance mode can be bypassed using an EventSubscriber.
   *
   * @see \Drupal\menu_test\EventSubscriber\MaintenanceModeSubscriber::onKernelRequestMaintenance().
   */
  function testMaintenanceModeLoginPaths() {
    config('system.maintenance')->set('enabled', 1)->save();

    $offline_message = t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => config('system.site')->get('name')));
    $this->drupalGet('test-page');
    $this->assertText($offline_message);
    $this->drupalGet('menu_login_callback');
    $this->assertText('This is menu_login_callback().', 'Maintenance mode can be bypassed using an event subscriber.');

    config('system.maintenance')->set('enabled', 0)->save();
  }

  /**
   * Test that an authenticated user hitting 'user/login' gets redirected to
   * 'user' and 'user/register' gets redirected to the user edit page.
   */
  function testAuthUserUserLogin() {
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
   * Test the theme callback when it is set to use an optional theme.
   */
  function testThemeCallbackOptionalTheme() {
    // Request a theme that is not enabled.
    $this->drupalGet('menu-test/theme-callback/use-stark-theme');
    $this->assertText('Custom theme: NONE. Actual theme: bartik.', 'The theme callback system falls back on the default theme when a theme that is not enabled is requested.');
    $this->assertRaw('bartik/css/style.css', "The default theme's CSS appears on the page.");

    // Now enable the theme and request it again.
    theme_enable(array($this->alternate_theme));
    $this->drupalGet('menu-test/theme-callback/use-stark-theme');
    $this->assertText('Custom theme: stark. Actual theme: stark.', 'The theme callback system uses an optional theme once it has been enabled.');
    $this->assertRaw('stark/css/layout.css', "The optional theme's CSS appears on the page.");
  }

  /**
   * Test the theme callback when it is set to use a theme that does not exist.
   */
  function testThemeCallbackFakeTheme() {
    $this->drupalGet('menu-test/theme-callback/use-fake-theme');
    $this->assertText('Custom theme: NONE. Actual theme: bartik.', 'The theme callback system falls back on the default theme when a theme that does not exist is requested.');
    $this->assertRaw('bartik/css/style.css', "The default theme's CSS appears on the page.");
  }

  /**
   * Test the theme callback when no theme is requested.
   */
  function testThemeCallbackNoThemeRequested() {
    $this->drupalGet('menu-test/theme-callback/no-theme-requested');
    $this->assertText('Custom theme: NONE. Actual theme: bartik.', 'The theme callback system falls back on the default theme when no theme is requested.');
    $this->assertRaw('bartik/css/style.css', "The default theme's CSS appears on the page.");
  }

  /**
   * Test that hook_custom_theme() can control the theme of a page.
   */
  function testHookCustomTheme() {
    // Trigger hook_custom_theme() to dynamically request the Stark theme for
    // the requested page.
    \Drupal::state()->set('menu_test.hook_custom_theme_name', $this->alternate_theme);
    theme_enable(array($this->alternate_theme, $this->admin_theme));

    // Visit a page that does not implement a theme callback. The above request
    // should be honored.
    $this->drupalGet('menu-test/no-theme-callback');
    $this->assertText('Custom theme: stark. Actual theme: stark.', 'The result of hook_custom_theme() is used as the theme for the current page.');
    $this->assertRaw('stark/css/layout.css', "The Stark theme's CSS appears on the page.");
  }

  /**
   * Test that the theme callback wins out over hook_custom_theme().
   */
  function testThemeCallbackHookCustomTheme() {
    // Trigger hook_custom_theme() to dynamically request the Stark theme for
    // the requested page.
    \Drupal::state()->set('menu_test.hook_custom_theme_name', $this->alternate_theme);
    theme_enable(array($this->alternate_theme, $this->admin_theme));

    // The menu "theme callback" should take precedence over a value set in
    // hook_custom_theme().
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertText('Custom theme: seven. Actual theme: seven.', 'The result of hook_custom_theme() does not override what was set in a theme callback.');
    $this->assertRaw('seven/style.css', "The Seven theme's CSS appears on the page.");
  }

  /**
   * Tests for menu_link_maintain().
   */
  function testMenuLinkMaintain() {
    $admin_user = $this->drupalCreateUser(array('access content', 'administer site configuration'));
    $this->drupalLogin($admin_user);

    // Create three menu items.
    menu_link_maintain('menu_test', 'insert', 'menu_test_maintain/1', 'Menu link #1');
    menu_link_maintain('menu_test', 'insert', 'menu_test_maintain/1', 'Menu link #1-main');
    menu_link_maintain('menu_test', 'insert', 'menu_test_maintain/2', 'Menu link #2');

    // Move second link to the main-menu, to test caching later on.
    db_update('menu_links')
      ->fields(array('menu_name' => 'main'))
      ->condition('link_title', 'Menu link #1-main')
      ->condition('customized', 0)
      ->condition('module', 'menu_test')
      ->execute();
    menu_cache_clear_all();

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
   * Tests for menu_name parameter for hook_menu().
   */
  function testMenuName() {
    $admin_user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($admin_user);

    $menu_links = entity_load_multiple_by_properties('menu_link', array('router_path' => 'menu_name_test'));
    $menu_link = reset($menu_links);
    $this->assertEqual($menu_link->menu_name, 'original', 'Menu name is "original".');

    // Change the menu_name parameter in menu_test.module, then force a menu
    // rebuild.
    menu_test_menu_name('changed');
    \Drupal::service('router.builder')->rebuild();
    menu_router_rebuild();

    $menu_links = entity_load_multiple_by_properties('menu_link', array('router_path' => 'menu_name_test'));
    $menu_link = reset($menu_links);
    $this->assertEqual($menu_link->menu_name, 'changed', 'Menu name was successfully changed after rebuild.');
  }

  /**
   * Tests for menu hierarchy.
   */
  function testMenuHierarchy() {
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
   * Tests menu link depth and parents of local tasks and menu callbacks.
   */
  function testMenuHidden() {
    // Verify links for one dynamic argument.
    $query = \Drupal::entityQuery('menu_link')
      ->condition('router_path', 'menu-test/hidden/menu', 'STARTS_WITH')
      ->sort('router_path');
    $result = $query->execute();
    $menu_links = menu_link_load_multiple($result);

    $links = array();
    foreach ($menu_links as $menu_link) {
      $links[$menu_link->router_path] = $menu_link;
    }

    $parent = $links['menu-test/hidden/menu'];
    $depth = $parent['depth'] + 1;
    $plid = $parent['mlid'];

    $link = $links['menu-test/hidden/menu/list'];
    $this->assertEqual($link['depth'], $depth, format_string('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, format_string('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/menu/add'];
    $this->assertEqual($link['depth'], $depth, format_string('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, format_string('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/menu/settings'];
    $this->assertEqual($link['depth'], $depth, format_string('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, format_string('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/menu/manage/%'];
    $this->assertEqual($link['depth'], $depth, format_string('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, format_string('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $parent = $links['menu-test/hidden/menu/manage/%'];
    $depth = $parent['depth'] + 1;
    $plid = $parent['mlid'];

    $link = $links['menu-test/hidden/menu/manage/%/list'];
    $this->assertEqual($link['depth'], $depth, format_string('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, format_string('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/menu/manage/%/add'];
    $this->assertEqual($link['depth'], $depth, format_string('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, format_string('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/menu/manage/%/edit'];
    $this->assertEqual($link['depth'], $depth, format_string('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, format_string('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/menu/manage/%/delete'];
    $this->assertEqual($link['depth'], $depth, format_string('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, format_string('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    // Verify links for two dynamic arguments.
    $query = \Drupal::entityQuery('menu_link')
      ->condition('router_path', 'menu-test/hidden/block', 'STARTS_WITH')
      ->sort('router_path');
    $result = $query->execute();
    $menu_links = menu_link_load_multiple($result);

    $links = array();
    foreach ($menu_links as $menu_link) {
      $links[$menu_link->router_path] = $menu_link;
    }

    $parent = $links['menu-test/hidden/block'];
    $depth = $parent['depth'] + 1;
    $plid = $parent['mlid'];

    $link = $links['menu-test/hidden/block/list'];
    $this->assertEqual($link['depth'], $depth, format_string('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, format_string('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/block/add'];
    $this->assertEqual($link['depth'], $depth, format_string('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, format_string('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/block/manage/%/%'];
    $this->assertEqual($link['depth'], $depth, format_string('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, format_string('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $parent = $links['menu-test/hidden/block/manage/%/%'];
    $depth = $parent['depth'] + 1;
    $plid = $parent['mlid'];

    $link = $links['menu-test/hidden/block/manage/%/%/configure'];
    $this->assertEqual($link['depth'], $depth, format_string('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, format_string('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/block/manage/%/%/delete'];
    $this->assertEqual($link['depth'], $depth, format_string('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, format_string('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));
  }

  /**
   * Test menu_get_item() with empty ancestors.
   */
  function testMenuGetItemNoAncestors() {
    \Drupal::state()->set('menu.masks', array());
    $this->drupalGet('');
  }

  /**
   * Test menu_set_item().
   */
  function testMenuSetItem() {
    $item = menu_get_item('test-page');

    $this->assertEqual($item['path'], 'test-page', "Path from menu_get_item('test-page') is equal to 'test-page'", 'menu');

    // Modify the path for the item then save it.
    $item['path'] = 'test-page-test';
    $item['href'] = 'test-page-test';

    menu_set_item('test-page', $item);
    $compare_item = menu_get_item('test-page');
    $this->assertEqual($compare_item, $item, 'Modified menu item is equal to newly retrieved menu item.', 'menu');
  }

  /**
   * Test menu maintenance hooks.
   */
  function testMenuItemHooks() {
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
  function testMenuLinkOptions() {
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
  function testMenuItemTitlesCases() {

    // Build array with string overrides.
    $test_data = array(
      1 => array('Example title - Case 1' => 'Alternative example title - Case 1'),
      2 => array('Example @sub1 - Case @op2' => 'Alternative example @sub1 - Case @op2'),
      3 => array('Example title' => 'Alternative example title'),
      4 => array('Example title' => 'Alternative example title'),
    );

    foreach ($test_data as $case_no => $override) {
      $this->menuItemTitlesCasesHelper($case_no);
      variable_set('locale_custom_strings_en', array('' => $override));
      $this->menuItemTitlesCasesHelper($case_no, TRUE);
      variable_set('locale_custom_strings_en', array());
    }
  }

  /**
   * Get a URL and assert the title given a case number. If override is true,
   * the title is asserted to begin with "Alternative".
   */
  private function menuItemTitlesCasesHelper($case_no, $override = FALSE) {
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
   * Tests inheritance of 'load arguments'.
   */
  function testMenuLoadArgumentsInheritance() {
    $expected = array(
      'menu-test/arguments/%/%' => array(
        2 => array('menu_test_argument_load' => array(3)),
        3 => NULL,
      ),
      // Arguments are inherited to normal children.
      'menu-test/arguments/%/%/default' => array(
        2 => array('menu_test_argument_load' => array(3)),
        3 => NULL,
      ),
      // Arguments are inherited to tab children.
      'menu-test/arguments/%/%/task' => array(
        2 => array('menu_test_argument_load' => array(3)),
        3 => NULL,
      ),
      // Arguments are only inherited to the same loader functions.
      'menu-test/arguments/%/%/common-loader' => array(
        2 => array('menu_test_argument_load' => array(3)),
        3 => 'menu_test_other_argument_load',
      ),
      // Arguments are not inherited to children not using the same loader
      // function.
      'menu-test/arguments/%/%/different-loaders-1' => array(
        2 => NULL,
        3 => 'menu_test_argument_load',
      ),
      'menu-test/arguments/%/%/different-loaders-2' => array(
        2 => 'menu_test_other_argument_load',
        3 => NULL,
      ),
      'menu-test/arguments/%/%/different-loaders-3' => array(
        2 => NULL,
        3 => NULL,
      ),
      // Explicit loader arguments should not be overriden by parent.
      'menu-test/arguments/%/%/explicit-arguments' => array(
        2 => array('menu_test_argument_load' => array()),
        3 => NULL,
      ),
    );

    foreach ($expected as $router_path => $load_functions) {
      $router_item = $this->menuLoadRouter($router_path);
      $this->assertIdentical(unserialize($router_item['load_functions']), $load_functions, format_string('Expected load functions for router %router_path' , array('%router_path' => $router_path)));
    }
  }
}
