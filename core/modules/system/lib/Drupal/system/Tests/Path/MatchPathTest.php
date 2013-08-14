<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Path\MatchPathTest.
 */

namespace Drupal\system\Tests\Path;

use Drupal\simpletest\WebTestBase;

/**
 * Unit tests for the drupal_match_path() function in path.inc.
 *
 * @see drupal_match_path().
 */
class MatchPathTest extends WebTestBase {
  protected $front;

  public static function getInfo() {
    return array(
      'name' => 'Drupal match path',
      'description' => 'Tests the drupal_match_path() function to make sure it works properly.',
      'group' => 'Path API',
    );
  }

  function setUp() {
    // Set up the database and testing environment.
    parent::setUp();

    // Set up a random site front page to test the '<front>' placeholder.
    $this->front = $this->randomName();
    \Drupal::config('system.site')->set('page.front', $this->front)->save();
    // Refresh our static variables from the database.
    $this->refreshVariables();
  }

  /**
   * Run through our test cases, making sure each one works as expected.
   */
  function testDrupalMatchPath() {
    // Set up our test cases.
    $tests = $this->drupalMatchPathTests();
    foreach ($tests as $patterns => $cases) {
      foreach ($cases as $path => $expected_result) {
        $actual_result = drupal_match_path($path, $patterns);
        $this->assertIdentical($actual_result, $expected_result, format_string('Tried matching the path <code>@path</code> to the pattern <pre>@patterns</pre> - expected @expected, got @actual.', array('@path' => $path, '@patterns' => $patterns, '@expected' => var_export($expected_result, TRUE), '@actual' => var_export($actual_result, TRUE))));
      }
    }
  }

  /**
   * Helper function for testDrupalMatchPath(): set up an array of test cases.
   *
   * @return
   *   An array of test cases to cycle through.
   */
  private function drupalMatchPathTests() {
    return array(
      // Single absolute paths.
      'example/1' => array(
        'example/1' => TRUE,
        'example/2' => FALSE,
        'test' => FALSE,
      ),
      // Single paths with wildcards.
      'example/*' => array(
        'example/1' => TRUE,
        'example/2' => TRUE,
        'example/3/edit' => TRUE,
        'example/' => TRUE,
        'example' => FALSE,
        'test' => FALSE,
      ),
      // Single paths with multiple wildcards.
      'node/*/revisions/*' => array(
        'node/1/revisions/3' => TRUE,
        'node/345/revisions/test' => TRUE,
        'node/23/edit' => FALSE,
        'test' => FALSE,
      ),
      // Single paths with '<front>'.
      '<front>' => array(
        $this->front => TRUE,
        "$this->front/" => FALSE,
        "$this->front/edit" => FALSE,
        'node' => FALSE,
        '' => FALSE,
      ),
      // Paths with both '<front>' and wildcards (should not work).
      '<front>/*' => array(
        $this->front => FALSE,
        "$this->front/" => FALSE,
        "$this->front/edit" => FALSE,
        'node/12' => FALSE,
        '' => FALSE,
      ),
      // Multiple paths with the \n delimiter.
      "node/*\nnode/*/edit" => array(
        'node/1' => TRUE,
        'node/view' => TRUE,
        'node/32/edit' => TRUE,
        'node/delete/edit' => TRUE,
        'node/50/delete' => TRUE,
        'test/example' => FALSE,
      ),
      // Multiple paths with the \r delimiter.
      "user/*\rexample/*" => array(
        'user/1' => TRUE,
        'example/1' => TRUE,
        'user/1/example/1' => TRUE,
        'user/example' => TRUE,
        'test/example' => FALSE,
        'user' => FALSE,
        'example' => FALSE,
      ),
      // Multiple paths with the \r\n delimiter.
      "test\r\n<front>" => array(
        'test' => TRUE,
        $this->front => TRUE,
        'example' => FALSE,
      ),
      // Test existing regular expressions (should be escaped).
      '[^/]+?/[0-9]' => array(
        'test/1' => FALSE,
        '[^/]+?/[0-9]' => TRUE,
      ),
    );
  }
}
