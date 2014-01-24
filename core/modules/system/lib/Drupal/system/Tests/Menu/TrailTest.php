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
