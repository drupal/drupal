<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Menu\TrailTest.
 */

namespace Drupal\system\Tests\Menu;

/**
 * Tests active menu trails.
 */
class TrailTest extends MenuTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'menu_test');

  public static function getInfo() {
    return array(
      'name' => 'Active trail',
      'description' => 'Tests active menu trails and alteration functionality.',
      'group' => 'Menu',
    );
  }

  function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer site configuration', 'access administration pages', 'administer blocks'));
    $this->drupalLogin($this->admin_user);

    // This test puts menu links in the Tools and Administration menus and then
    // tests for their presence on the page.
    $this->drupalPlaceBlock('system_menu_block:tools');
    $this->drupalPlaceBlock('system_menu_block:admin');
  }

  /**
   * Tests active trails are properly affected by menu_tree_set_path().
   */
  function testMenuTreeSetPath() {
    $home = array('<front>' => 'Home');
    $config_tree = array(
      'admin' => t('Administration'),
      'admin/config' => t('Configuration'),
    );
    $config = $home + $config_tree;

    // The menu_test_menu_tree_set_path system variable controls whether or not
    // the menu_test_menu_trail_callback() callback (used by all paths in these
    // tests) issues an overriding call to menu_trail_set_path().
    $test_menu_path = array(
      'menu_name' => 'admin',
      'path' => 'admin/config/system/site-information',
    );

    $breadcrumb = $home + array(
      'menu-test' => t('Menu test root'),
    );
    $tree = array(
      'menu-test' => t('Menu test root'),
      'menu-test/menu-trail' => t('Menu trail - Case 1'),
    );

    // Test the tree generation for the Tools menu.
    \Drupal::state()->delete('menu_test.menu_tree_set_path');
    $this->assertBreadcrumb('menu-test/menu-trail', $breadcrumb, t('Menu trail - Case 1'), $tree);

    // Override the active trail for the Administration tree; it should not
    // affect the Tools tree.
    \Drupal::state()->set('menu_test.menu_tree_set_path', $test_menu_path);
    $this->assertBreadcrumb('menu-test/menu-trail', $breadcrumb, t('Menu trail - Case 1'), $tree);

    $breadcrumb = $config + array(
      'admin/config/development' => t('Development'),
    );
    $tree = $config_tree + array(
      'admin/config/development' => t('Development'),
      'admin/config/development/menu-trail' => t('Menu trail - Case 2'),
    );

    $override_breadcrumb = $config + array(
      'admin/config/system' => t('System'),
      'admin/config/system/site-information' => t('Site information'),
    );
    $override_tree = $config_tree + array(
      'admin/config/system' => t('System'),
      'admin/config/system/site-information' => t('Site information'),
    );

    // Test the tree generation for the Administration menu.
    \Drupal::state()->delete('menu_test.menu_tree_set_path');
    $this->assertBreadcrumb('admin/config/development/menu-trail', $breadcrumb, t('Menu trail - Case 2'), $tree);

    // Override the active trail for the Administration tree; it should affect
    // the breadcrumbs and Administration tree.
    \Drupal::state()->set('menu_test.menu_tree_set_path', $test_menu_path);
    $this->assertBreadcrumb('admin/config/development/menu-trail', $override_breadcrumb, t('Menu trail - Case 2'), $override_tree);
  }

  /**
   * Tests that the active trail works correctly on custom 403 and 404 pages.
   */
  function testCustom403And404Pages() {
    // Set the custom 403 and 404 pages we will use.
    \Drupal::config('system.site')
      ->set('page.403', 'menu-test/custom-403-page')
      ->set('page.404', 'menu-test/custom-404-page')
      ->save();

    // Define the paths we'll visit to trigger 403 and 404 responses during
    // this test, and the expected active trail for each case.
    $paths = array(
      403 => 'admin/config',
      404 => $this->randomName(),
    );
    // For the 403 page, the initial trail during the Drupal bootstrap should
    // include the page that the user is trying to visit, while the final trail
    // should reflect the custom 403 page that the user was redirected to.
    $expected_trail[403]['initial'] = array(
      '<front>' => 'Home',
      'admin/config' => 'Configuration',
    );
    $expected_trail[403]['final'] = array(
      '<front>' => 'Home',
      'menu-test' => 'Menu test root',
      'menu-test/custom-403-page' => 'Custom 403 page',
    );
    // For the 404 page, the initial trail during the Drupal bootstrap should
    // only contain the link back to "Home" (since the page the user is trying
    // to visit doesn't have any menu items associated with it), while the
    // final trail should reflect the custom 404 page that the user was
    // redirected to.
    $expected_trail[404]['initial'] = array(
      '<front>' => 'Home',
    );
    $expected_trail[404]['final'] = array(
      '<front>' => 'Home',
      'menu-test' => 'Menu test root',
      'menu-test/custom-404-page' => 'Custom 404 page',
    );

    // Visit each path as an anonymous user so that we will actually get a 403
    // on admin/config.
    $this->drupalLogout();
    foreach (array(403, 404) as $status_code) {
      // Before visiting the page, trigger the code in the menu_test module
      // that will record the active trail (so we can check it in this test).
      \Drupal::state()->set('menu_test.record_active_trail', TRUE);
      $this->drupalGet($paths[$status_code]);
      $this->assertResponse($status_code);

      // Check that the initial trail (during the Drupal bootstrap) matches
      // what we expect.
      $initial_trail = \Drupal::state()->get('menu_test.active_trail_initial') ?: array();
      $this->assertEqual(count($initial_trail), count($expected_trail[$status_code]['initial']), format_string('The initial active trail for a @status_code page contains the expected number of items (expected: @expected, found: @found).', array(
        '@status_code' => $status_code,
        '@expected' => count($expected_trail[$status_code]['initial']),
        '@found' => count($initial_trail),
      )));
      foreach (array_keys($expected_trail[$status_code]['initial']) as $index => $path) {
        $this->assertEqual($initial_trail[$index]['href'], $path, format_string('Element number @number of the initial active trail for a @status_code page contains the correct path (expected: @expected, found: @found)', array(
          '@number' => $index + 1,
          '@status_code' => $status_code,
          '@expected' => $path,
          '@found' => $initial_trail[$index]['href'],
        )));
      }

      // Check that the final trail (after the user has been redirected to the
      // custom 403/404 page) matches what we expect.
      $final_trail = \Drupal::state()->get('menu_test.active_trail_final') ?: array();
      $this->assertEqual(count($final_trail), count($expected_trail[$status_code]['final']), format_string('The final active trail for a @status_code page contains the expected number of items (expected: @expected, found: @found).', array(
        '@status_code' => $status_code,
        '@expected' => count($expected_trail[$status_code]['final']),
        '@found' => count($final_trail),
      )));
      foreach (array_keys($expected_trail[$status_code]['final']) as $index => $path) {
        $this->assertEqual($final_trail[$index]['href'], $path, format_string('Element number @number of the final active trail for a @status_code page contains the correct path (expected: @expected, found: @found)', array(
          '@number' => $index + 1,
          '@status_code' => $status_code,
          '@expected' => $path,
          '@found' => $final_trail[$index]['href'],
        )));
      }

      // Check that the breadcrumb displayed on the final custom 403/404 page
      // matches what we expect. (The last item of the active trail represents
      // the current page, which is not supposed to appear in the breadcrumb,
      // so we need to remove it from the array before checking.)
      array_pop($expected_trail[$status_code]['final']);
      $this->assertBreadcrumb(NULL, $expected_trail[$status_code]['final']);
    }
  }
}
