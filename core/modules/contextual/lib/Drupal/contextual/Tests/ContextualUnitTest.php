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
    // - one module.
    // - one dynamic path argument.
    // - no metadata.
    $tests[] = array(
      'links' => array(
        'node' => array(
          'node',
          array('14031991'),
          array()
        ),
      ),
      'id' => 'node:node:14031991:',
    );

    // Test branch conditions:
    // - one module.
    // - multiple dynamic path arguments.
    // - no metadata.
    $tests[] = array(
      'links' => array(
        'foo' => array(
          'baz/in/ga',
          array('bar', 'baz', 'qux'),
          array()
        ),
      ),
      'id' => 'foo:baz/in/ga:bar/baz/qux:',
    );

    // Test branch conditions:
    // - one module.
    // - one dynamic path argument.
    // - metadata.
    $tests[] = array(
      'links' => array(
        'views_ui' => array(
          'admin/structure/views/view',
          array('frontpage'),
          array(
            'location' => 'page',
            'display' => 'page_1',
          )
        ),
      ),
      'id' => 'views_ui:admin/structure/views/view:frontpage:location=page&display=page_1',
    );

    // Test branch conditions:
    // - multiple modules.
    // - multiple dynamic path arguments.
    $tests[] = array(
      'links' => array(
        'node' => array(
          'node',
          array('14031991'),
          array()
        ),
        'foo' => array(
          'baz/in/ga',
          array('bar', 'baz', 'qux'),
          array()
        ),
        'edge' => array(
          'edge',
          array('20011988'),
          array()
        ),
      ),
      'id' => 'node:node:14031991:|foo:baz/in/ga:bar/baz/qux:|edge:edge:20011988:',
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
