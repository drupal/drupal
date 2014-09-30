<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Path\UrlAlterFunctionalTest.
 */

namespace Drupal\system\Tests\Path;

use Drupal\simpletest\WebTestBase;

/**
 * Tests altering the inbound path and the outbound path.
 *
 * @group Path
 */
class UrlAlterFunctionalTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('path', 'forum', 'url_alter_test');

  /**
   * Test that URL altering works and that it occurs in the correct order.
   */
  function testUrlAlter() {
    $account = $this->drupalCreateUser(array('administer url aliases'));
    $this->drupalLogin($account);

    $uid = $account->id();
    $name = $account->getUsername();

    // Test a single altered path.
    $this->drupalGet("user/$name");
    $this->assertResponse('200', 'The user/username path gets resolved correctly');
    $this->assertUrlOutboundAlter("user/$uid", "user/$name");

    // Test that a path always uses its alias.
    $path = array('source' => "user/$uid/test1", 'alias' => 'alias/test1');
    $this->container->get('path.alias_storage')->save($path['source'], $path['alias']);
    $this->rebuildContainer();
    $this->assertUrlInboundAlter('alias/test1', "user/$uid/test1");
    $this->assertUrlOutboundAlter("user/$uid/test1", 'alias/test1');

    // Test adding an alias via the UI.
    $edit = array('source' => "user/$uid/edit", 'alias' => 'alias/test2');
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));
    $this->assertText(t('The alias has been saved.'));
    $this->drupalGet('alias/test2');
    $this->assertResponse('200', 'The path alias gets resolved correctly');
    $this->assertUrlOutboundAlter("user/$uid/edit", 'alias/test2');

    // Test a non-existent user is not altered.
    $uid++;
    $this->assertUrlOutboundAlter("user/$uid", "user/$uid");

    // Test that 'forum' is altered to 'community' correctly, both at the root
    // level and for a specific existing forum.
    $this->drupalGet('community');
    $this->assertText('General discussion', 'The community path gets resolved correctly');
    $this->assertUrlOutboundAlter('forum', 'community');
    $forum_vid = \Drupal::config('forum.settings')->get('vocabulary');
    $term_name = $this->randomMachineName();
    $term = entity_create('taxonomy_term', array(
      'name' => $term_name,
      'vid' => $forum_vid,
    ));
    $term->save();
    $this->drupalGet("community/" . $term->id());
    $this->assertText($term_name, 'The community/{tid} path gets resolved correctly');
    $this->assertUrlOutboundAlter("forum/" . $term->id(), "community/" . $term->id());
  }

  /**
   * Test current_path() and request_path().
   */
  function testCurrentUrlRequestedPath() {
    $this->drupalGet('url-alter-test/bar');
    $this->assertRaw('request_path=url-alter-test/bar', 'request_path() returns the requested path.');
    $this->assertRaw('current_path=url-alter-test/foo', 'current_path() returns the internal path.');
  }

  /**
   * Assert that an outbound path is altered to an expected value.
   *
   * @param $original
   *   A string with the original path that is run through _url().
   * @param $final
   *   A string with the expected result after _url().
   * @return
   *   TRUE if $original was correctly altered to $final, FALSE otherwise.
   */
  protected function assertUrlOutboundAlter($original, $final) {
    // Test outbound altering.
    $result = $this->container->get('url_generator')->generateFromPath($original);
    $base_path = base_path() . $GLOBALS['script_path'];
    $result = substr($result, strlen($base_path));
    $this->assertIdentical($result, $final, format_string('Altered outbound URL %original, expected %final, and got %result.', array('%original' => $original, '%final' => $final, '%result' => $result)));
  }

  /**
   * Assert that a inbound path is altered to an expected value.
   *
   * @param $original
   *   The original path before it has been altered by inbound URL processing.
   * @param $final
   *   A string with the expected result after _url().
   * @return
   *   TRUE if $original was correctly altered to $final, FALSE otherwise.
   */
  protected function assertUrlInboundAlter($original, $final) {
    // Test inbound altering.
    $result = $this->container->get('path.alias_manager')->getPathByAlias($original);
    $this->assertIdentical($result, $final, format_string('Altered inbound URL %original, expected %final, and got %result.', array('%original' => $original, '%final' => $final, '%result' => $result)));
  }
}
