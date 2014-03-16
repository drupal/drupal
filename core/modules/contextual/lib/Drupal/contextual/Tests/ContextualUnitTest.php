<?php

/**
 * @file
 * Contains \Drupal\contextual\Tests\ContextualUnitTest.
 */

namespace Drupal\contextual\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests _contextual_links_to_id() & _contextual_id_to_links().
 */
class ContextualUnitTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('contextual');

  public static function getInfo() {
    return array(
      'name' => 'Conversion to and from "contextual id"s (for placeholders)',
      'description' => 'Tests all edge cases of converting from #contextual_links to ids and vice versa.',
      'group' => 'Contextual',
    );
  }

  /**
   * Provides testcases for testContextualLinksToId() and
   */
  function _contextual_links_id_testcases() {
    // Test branch conditions:
    // - one group.
    // - one dynamic path argument.
    // - no metadata.
    $tests[] = array(
      'links' => array(
        'node' => array(
          'route_parameters' => array(
            'node' => '14031991',
          ),
          'metadata' => array()
        ),
      ),
      'id' => 'node:node=14031991:',
    );

    // Test branch conditions:
    // - one group.
    // - multiple dynamic path arguments.
    // - no metadata.
    $tests[] = array(
      'links' => array(
        'foo' => array(
          'route_parameters'=> array(
            'bar',
            'key' => 'baz',
            'qux',
          ),
          'metadata' => array(),
        ),
      ),
      'id' => 'foo:0=bar&key=baz&1=qux:',
    );

    // Test branch conditions:
    // - one group.
    // - one dynamic path argument.
    // - metadata.
    $tests[] = array(
      'links' => array(
        'views_ui_edit' => array(
          'route_parameters' => array(
            'view' => 'frontpage'
          ),
          'metadata' => array(
            'location' => 'page',
            'display' => 'page_1',
          ),
        ),
      ),
      'id' => 'views_ui_edit:view=frontpage:location=page&display=page_1',
    );

    // Test branch conditions:
    // - multiple groups.
    // - multiple dynamic path arguments.
    $tests[] = array(
      'links' => array(
        'node' => array(
          'route_parameters' => array(
            'node' => '14031991',
          ),
          'metadata' => array(),
        ),
        'foo' => array(
          'route_parameters' => array(
            'bar',
            'key' => 'baz',
            'qux',
          ),
          'metadata' => array(),
        ),
        'edge' => array(
          'route_parameters' => array('20011988'),
          'metadata' => array(),
        ),
      ),
      'id' => 'node:node=14031991:|foo:0=bar&key=baz&1=qux:|edge:0=20011988:',
    );

    return $tests;
  }

  /**
   * Tests _contextual_links_to_id().
   */
  function testContextualLinksToId() {
    $tests = $this->_contextual_links_id_testcases();
    foreach ($tests as $test) {
      $this->assertIdentical(_contextual_links_to_id($test['links']), $test['id']);
    }
  }

  /**
   * Tests _contextual_id_to_links().
   */
  function testContextualIdToLinks() {
    $tests = $this->_contextual_links_id_testcases();
    foreach ($tests as $test) {
      $this->assertIdentical(_contextual_id_to_links($test['id']), $test['links']);
    }
  }
}
