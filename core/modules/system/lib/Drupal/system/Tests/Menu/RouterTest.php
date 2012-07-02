<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Menu\RouterTest.
 */

namespace Drupal\system\Tests\Menu;

use PDO;
use Drupal\simpletest\WebTestBase;

/**
 * Tests menu router and hook_menu() functionality.
 */
class RouterTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Menu router',
      'description' => 'Tests menu router and hook_menu() functionality.',
      'group' => 'Menu',
    );
  }

  function setUp() {
    // Enable dummy module that implements hook_menu.
    parent::setUp(array('block', 'menu_test'));

    // Make the tests below more robust by explicitly setting the default theme
    // and administrative theme that they expect.
    theme_enable(array('bartik'));
    variable_set('theme_default', 'bartik');
    variable_set('admin_theme', 'seven');
    theme_disable(array('stark'));

    // Enable navigation menu block.
    db_merge('block')
      ->key(array(
        'module' => 'system',
        'delta' => 'navigation',
        'theme' => 'bartik',
      ))
      ->fields(array(
        'status' => 1,
        'weight' => 0,
        'region' => 'sidebar_first',
        'pages' => '',
        'cache' => -1,
      ))
      ->execute();
  }

  /**
   * Test title callback set to FALSE.
   */
  function testTitleCallbackFalse() {
    $this->drupalGet('node');
    $this->assertText('A title with @placeholder', t('Raw text found on the page'));
    $this->assertNoText(t('A title with @placeholder', array('@placeholder' => 'some other text')), t('Text with placeholder substitutions not found.'));
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
   * Test the theme callback when it is set to use an administrative theme.
   */
  function testThemeCallbackAdministrative() {
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertText('Custom theme: seven. Actual theme: seven.', t('The administrative theme can be correctly set in a theme callback.'));
    $this->assertRaw('seven/style.css', t("The administrative theme's CSS appears on the page."));
  }

  /**
   * Test that the theme callback is properly inherited.
   */
  function testThemeCallbackInheritance() {
    $this->drupalGet('menu-test/theme-callback/use-admin-theme/inheritance');
    $this->assertText('Custom theme: seven. Actual theme: seven. Theme callback inheritance is being tested.', t('Theme callback inheritance correctly uses the administrative theme.'));
    $this->assertRaw('seven/style.css', t("The administrative theme's CSS appears on the page."));
  }

  /**
   * Test that 'page callback', 'file' and 'file path' keys are properly
   * inherited from parent menu paths.
   */
  function testFileInheritance() {
    $this->drupalGet('admin/config/development/file-inheritance');
    $this->assertText('File inheritance test description', t('File inheritance works.'));
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
    variable_set('maintenance_mode', TRUE);

    // For a regular user, the fact that the site is in maintenance mode means
    // we expect the theme callback system to be bypassed entirely.
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertRaw('bartik/css/style.css', t("The maintenance theme's CSS appears on the page."));

    // An administrator, however, should continue to see the requested theme.
    $admin_user = $this->drupalCreateUser(array('access site in maintenance mode'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertText('Custom theme: seven. Actual theme: seven.', t('The theme callback system is correctly triggered for an administrator when the site is in maintenance mode.'));
    $this->assertRaw('seven/style.css', t("The administrative theme's CSS appears on the page."));
  }

  /**
   * Make sure the maintenance mode can be bypassed using hook_menu_site_status_alter().
   *
   * @see hook_menu_site_status_alter().
   */
  function testMaintenanceModeLoginPaths() {
    variable_set('maintenance_mode', TRUE);

    $offline_message = t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => config('system.site')->get('name')));
    $this->drupalGet('node');
    $this->assertText($offline_message);
    $this->drupalGet('menu_login_callback');
    $this->assertText('This is menu_login_callback().', t('Maintenance mode can be bypassed through hook_menu_site_status_alter().'));
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
    $this->assertTrue($this->url == url('user', array('absolute' => TRUE)), t("Logged-in user redirected to user on accessing user/login"));

    // user/register should redirect to user/UID/edit.
    $this->drupalGet('user/register');
    $this->assertTrue($this->url == url('user/' . $this->loggedInUser->uid . '/edit', array('absolute' => TRUE)), t("Logged-in user redirected to user/UID/edit on accessing user/register"));
  }

  /**
   * Test the theme callback when it is set to use an optional theme.
   */
  function testThemeCallbackOptionalTheme() {
    // Request a theme that is not enabled.
    $this->drupalGet('menu-test/theme-callback/use-stark-theme');
    $this->assertText('Custom theme: NONE. Actual theme: bartik.', t('The theme callback system falls back on the default theme when a theme that is not enabled is requested.'));
    $this->assertRaw('bartik/css/style.css', t("The default theme's CSS appears on the page."));

    // Now enable the theme and request it again.
    theme_enable(array('stark'));
    $this->drupalGet('menu-test/theme-callback/use-stark-theme');
    $this->assertText('Custom theme: stark. Actual theme: stark.', t('The theme callback system uses an optional theme once it has been enabled.'));
    $this->assertRaw('stark/css/layout.css', t("The optional theme's CSS appears on the page."));
  }

  /**
   * Test the theme callback when it is set to use a theme that does not exist.
   */
  function testThemeCallbackFakeTheme() {
    $this->drupalGet('menu-test/theme-callback/use-fake-theme');
    $this->assertText('Custom theme: NONE. Actual theme: bartik.', t('The theme callback system falls back on the default theme when a theme that does not exist is requested.'));
    $this->assertRaw('bartik/css/style.css', t("The default theme's CSS appears on the page."));
  }

  /**
   * Test the theme callback when no theme is requested.
   */
  function testThemeCallbackNoThemeRequested() {
    $this->drupalGet('menu-test/theme-callback/no-theme-requested');
    $this->assertText('Custom theme: NONE. Actual theme: bartik.', t('The theme callback system falls back on the default theme when no theme is requested.'));
    $this->assertRaw('bartik/css/style.css', t("The default theme's CSS appears on the page."));
  }

  /**
   * Test that hook_custom_theme() can control the theme of a page.
   */
  function testHookCustomTheme() {
    // Trigger hook_custom_theme() to dynamically request the Stark theme for
    // the requested page.
    variable_set('menu_test_hook_custom_theme_name', 'stark');
    theme_enable(array('stark'));

    // Visit a page that does not implement a theme callback. The above request
    // should be honored.
    $this->drupalGet('menu-test/no-theme-callback');
    $this->assertText('Custom theme: stark. Actual theme: stark.', t('The result of hook_custom_theme() is used as the theme for the current page.'));
    $this->assertRaw('stark/css/layout.css', t("The Stark theme's CSS appears on the page."));
  }

  /**
   * Test that the theme callback wins out over hook_custom_theme().
   */
  function testThemeCallbackHookCustomTheme() {
    // Trigger hook_custom_theme() to dynamically request the Stark theme for
    // the requested page.
    variable_set('menu_test_hook_custom_theme_name', 'stark');
    theme_enable(array('stark'));

    // The menu "theme callback" should take precedence over a value set in
    // hook_custom_theme().
    $this->drupalGet('menu-test/theme-callback/use-admin-theme');
    $this->assertText('Custom theme: seven. Actual theme: seven.', t('The result of hook_custom_theme() does not override what was set in a theme callback.'));
    $this->assertRaw('seven/style.css', t("The Seven theme's CSS appears on the page."));
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
      ->fields(array('menu_name' => 'main-menu'))
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

    $sql = "SELECT menu_name FROM {menu_links} WHERE router_path = 'menu_name_test'";
    $name = db_query($sql)->fetchField();
    $this->assertEqual($name, 'original', t('Menu name is "original".'));

    // Change the menu_name parameter in menu_test.module, then force a menu
    // rebuild.
    menu_test_menu_name('changed');
    menu_router_rebuild();

    $sql = "SELECT menu_name FROM {menu_links} WHERE router_path = 'menu_name_test'";
    $name = db_query($sql)->fetchField();
    $this->assertEqual($name, 'changed', t('Menu name was successfully changed after rebuild.'));
  }

  /**
   * Tests for menu hierarchy.
   */
  function testMenuHierarchy() {
    $parent_link = db_query('SELECT * FROM {menu_links} WHERE link_path = :link_path', array(':link_path' => 'menu-test/hierarchy/parent'))->fetchAssoc();
    $child_link = db_query('SELECT * FROM {menu_links} WHERE link_path = :link_path', array(':link_path' => 'menu-test/hierarchy/parent/child'))->fetchAssoc();
    $unattached_child_link = db_query('SELECT * FROM {menu_links} WHERE link_path = :link_path', array(':link_path' => 'menu-test/hierarchy/parent/child2/child'))->fetchAssoc();

    $this->assertEqual($child_link['plid'], $parent_link['mlid'], t('The parent of a directly attached child is correct.'));
    $this->assertEqual($unattached_child_link['plid'], $parent_link['mlid'], t('The parent of a non-directly attached child is correct.'));
  }

  /**
   * Tests menu link depth and parents of local tasks and menu callbacks.
   */
  function testMenuHidden() {
    // Verify links for one dynamic argument.
    $links = db_select('menu_links', 'ml')
      ->fields('ml')
      ->condition('ml.router_path', 'menu-test/hidden/menu%', 'LIKE')
      ->orderBy('ml.router_path')
      ->execute()
      ->fetchAllAssoc('router_path', PDO::FETCH_ASSOC);

    $parent = $links['menu-test/hidden/menu'];
    $depth = $parent['depth'] + 1;
    $plid = $parent['mlid'];

    $link = $links['menu-test/hidden/menu/list'];
    $this->assertEqual($link['depth'], $depth, t('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, t('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/menu/add'];
    $this->assertEqual($link['depth'], $depth, t('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, t('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/menu/settings'];
    $this->assertEqual($link['depth'], $depth, t('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, t('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/menu/manage/%'];
    $this->assertEqual($link['depth'], $depth, t('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, t('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $parent = $links['menu-test/hidden/menu/manage/%'];
    $depth = $parent['depth'] + 1;
    $plid = $parent['mlid'];

    $link = $links['menu-test/hidden/menu/manage/%/list'];
    $this->assertEqual($link['depth'], $depth, t('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, t('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/menu/manage/%/add'];
    $this->assertEqual($link['depth'], $depth, t('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, t('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/menu/manage/%/edit'];
    $this->assertEqual($link['depth'], $depth, t('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, t('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/menu/manage/%/delete'];
    $this->assertEqual($link['depth'], $depth, t('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, t('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    // Verify links for two dynamic arguments.
    $links = db_select('menu_links', 'ml')
      ->fields('ml')
      ->condition('ml.router_path', 'menu-test/hidden/block%', 'LIKE')
      ->orderBy('ml.router_path')
      ->execute()
      ->fetchAllAssoc('router_path', PDO::FETCH_ASSOC);

    $parent = $links['menu-test/hidden/block'];
    $depth = $parent['depth'] + 1;
    $plid = $parent['mlid'];

    $link = $links['menu-test/hidden/block/list'];
    $this->assertEqual($link['depth'], $depth, t('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, t('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/block/add'];
    $this->assertEqual($link['depth'], $depth, t('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, t('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/block/manage/%/%'];
    $this->assertEqual($link['depth'], $depth, t('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, t('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $parent = $links['menu-test/hidden/block/manage/%/%'];
    $depth = $parent['depth'] + 1;
    $plid = $parent['mlid'];

    $link = $links['menu-test/hidden/block/manage/%/%/configure'];
    $this->assertEqual($link['depth'], $depth, t('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, t('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));

    $link = $links['menu-test/hidden/block/manage/%/%/delete'];
    $this->assertEqual($link['depth'], $depth, t('%path depth @link_depth is equal to @depth.', array('%path' => $link['router_path'], '@link_depth' => $link['depth'], '@depth' => $depth)));
    $this->assertEqual($link['plid'], $plid, t('%path plid @link_plid is equal to @plid.', array('%path' => $link['router_path'], '@link_plid' => $link['plid'], '@plid' => $plid)));
  }

  /**
   * Test menu_get_item() with empty ancestors.
   */
  function testMenuGetItemNoAncestors() {
    variable_set('menu_masks', array());
    $this->drupalGet('');
  }

  /**
   * Test menu_set_item().
   */
  function testMenuSetItem() {
    $item = menu_get_item('node');

    $this->assertEqual($item['path'], 'node', t("Path from menu_get_item('node') is equal to 'node'"), 'menu');

    // Modify the path for the item then save it.
    $item['path'] = 'node_test';
    $item['href'] = 'node_test';

    menu_set_item('node', $item);
    $compare_item = menu_get_item('node');
    $this->assertEqual($compare_item, $item, t('Modified menu item is equal to newly retrieved menu item.'), 'menu');
  }

  /**
   * Test menu maintenance hooks.
   */
  function testMenuItemHooks() {
    // Create an item.
    menu_link_maintain('menu_test', 'insert', 'menu_test_maintain/4', 'Menu link #4');
    $this->assertEqual(menu_test_static_variable(), 'insert', t('hook_menu_link_insert() fired correctly'));
    // Update the item.
    menu_link_maintain('menu_test', 'update', 'menu_test_maintain/4', 'Menu link updated');
    $this->assertEqual(menu_test_static_variable(), 'update', t('hook_menu_link_update() fired correctly'));
    // Delete the item.
    menu_link_maintain('menu_test', 'delete', 'menu_test_maintain/4', '');
    $this->assertEqual(menu_test_static_variable(), 'delete', t('hook_menu_link_delete() fired correctly'));
  }

  /**
   * Test menu link 'options' storage and rendering.
   */
  function testMenuLinkOptions() {
    // Create a menu link with options.
    $menu_link = array(
      'link_title' => 'Menu link options test',
      'link_path' => 'node',
      'module' => 'menu_test',
      'options' => array(
        'attributes' => array(
          'title' => 'Test title attribute',
        ),
        'query' => array(
          'testparam' => 'testvalue',
        ),
      ),
    );
    menu_link_save($menu_link);

    // Load front page.
    $this->drupalGet('node');
    $this->assertRaw('title="Test title attribute"', t('Title attribute of a menu link renders.'));
    $this->assertRaw('testparam=testvalue', t('Query parameter added to menu link.'));
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
   * Get a url and assert the title given a case number. If override is true,
   * the title is asserted to begin with "Alternative".
   */
  private function menuItemTitlesCasesHelper($case_no, $override = FALSE) {
    $this->drupalGet('menu-title-test/case' . $case_no);
    $this->assertResponse(200);
    $asserted_title = $override ? 'Alternative example title - Case ' . $case_no : 'Example title - Case ' . $case_no;
    $this->assertTitle($asserted_title . ' | Drupal', t('Menu title is') . ': ' . $asserted_title, 'Menu');
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
      $this->assertIdentical(unserialize($router_item['load_functions']), $load_functions, t('Expected load functions for router %router_path' , array('%router_path' => $router_path)));
    }
  }
}
