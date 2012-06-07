<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Path\UrlAlterFunctionalTest.
 */

namespace Drupal\system\Tests\Path;

use Drupal\simpletest\WebTestBase;

/**
 * Tests hook_url_alter functions.
 */
class UrlAlterFunctionalTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => t('URL altering'),
      'description' => t('Tests hook_url_inbound_alter() and hook_url_outbound_alter().'),
      'group' => t('Path API'),
    );
  }

  function setUp() {
    parent::setUp('path', 'forum', 'url_alter_test');
  }

  /**
   * Test that URL altering works and that it occurs in the correct order.
   */
  function testUrlAlter() {
    $account = $this->drupalCreateUser(array('administer url aliases'));
    $this->drupalLogin($account);

    $uid = $account->uid;
    $name = $account->name;

    // Test a single altered path.
    $this->assertUrlInboundAlter("user/$name", "user/$uid");
    $this->assertUrlOutboundAlter("user/$uid", "user/$name");

    // Test that a path always uses its alias.
    $path = array('source' => "user/$uid/test1", 'alias' => 'alias/test1');
    path_save($path);
    $this->assertUrlInboundAlter('alias/test1', "user/$uid/test1");
    $this->assertUrlOutboundAlter("user/$uid/test1", 'alias/test1');

    // Test that alias source paths are normalized in the interface.
    $edit = array('source' => "user/$name/edit", 'alias' => 'alias/test2');
    $this->drupalPost('admin/config/search/path/add', $edit, t('Save'));
    $this->assertText(t('The alias has been saved.'));

    // Test that a path always uses its alias.
    $this->assertUrlInboundAlter('alias/test2', "user/$uid/edit");
    $this->assertUrlOutboundAlter("user/$uid/edit", 'alias/test2');

    // Test a non-existent user is not altered.
    $uid++;
    $this->assertUrlInboundAlter("user/$uid", "user/$uid");
    $this->assertUrlOutboundAlter("user/$uid", "user/$uid");

    // Test that 'forum' is altered to 'community' correctly, both at the root
    // level and for a specific existing forum.
    $this->assertUrlInboundAlter('community', 'forum');
    $this->assertUrlOutboundAlter('forum', 'community');
    $forum_vid = variable_get('forum_nav_vocabulary');
    $tid = db_insert('taxonomy_term_data')
      ->fields(array(
        'name' => $this->randomName(),
        'vid' => $forum_vid,
      ))
      ->execute();
    $this->assertUrlInboundAlter("community/$tid", "forum/$tid");
    $this->assertUrlOutboundAlter("forum/$tid", "community/$tid");
  }

  /**
   * Test current_path() and request_path().
   */
  function testCurrentUrlRequestedPath() {
    $this->drupalGet('url-alter-test/bar');
    $this->assertRaw('request_path=url-alter-test/bar', t('request_path() returns the requested path.'));
    $this->assertRaw('current_path=url-alter-test/foo', t('current_path() returns the internal path.'));
  }

  /**
   * Tests that current_path() is initialized when the request path is empty.
   */
  function testGetQInitialized() {
    $this->drupalGet('');
    $this->assertText("current_path() is non-empty with an empty request path.", "current_path() is initialized with an empty request path.");
  }

  /**
   * Assert that an outbound path is altered to an expected value.
   *
   * @param $original
   *   A string with the original path that is run through url().
   * @param $final
   *   A string with the expected result after url().
   * @return
   *   TRUE if $original was correctly altered to $final, FALSE otherwise.
   */
  protected function assertUrlOutboundAlter($original, $final) {
    // Test outbound altering.
    $result = url($original);
    $base_path = base_path() . $GLOBALS['script_path'];
    $result = substr($result, strlen($base_path));
    $this->assertIdentical($result, $final, t('Altered outbound URL %original, expected %final, and got %result.', array('%original' => $original, '%final' => $final, '%result' => $result)));
  }

  /**
   * Assert that a inbound path is altered to an expected value.
   *
   * @param $original
   *   A string with the aliased or un-normal path that is run through
   *   drupal_get_normal_path().
   * @param $final
   *   A string with the expected result after url().
   * @return
   *   TRUE if $original was correctly altered to $final, FALSE otherwise.
   */
  protected function assertUrlInboundAlter($original, $final) {
    // Test inbound altering.
    $result = drupal_get_normal_path($original);
    $this->assertIdentical($result, $final, t('Altered inbound URL %original, expected %final, and got %result.', array('%original' => $original, '%final' => $final, '%result' => $result)));
  }
}
