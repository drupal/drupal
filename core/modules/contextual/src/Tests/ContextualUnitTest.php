<?php

/**
 * @file
 * Contains \Drupal\contextual\Tests\ContextualUnitTest.
 */

namespace Drupal\contextual\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests all edge cases of converting from #contextual_links to ids and vice
 * versa.
 *
 * @group contextual
 */
class ContextualUnitTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('contextual');

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
          'metadata' => array('langcode' => 'en'),
        ),
      ),
      'id' => 'node:node=14031991:langcode=en',
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
          'metadata' => array('langcode' => 'en'),
        ),
      ),
      'id' => 'foo:0=bar&key=baz&1=qux:langcode=en',
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
            'langcode' => 'en',
          ),
        ),
      ),
      'id' => 'views_ui_edit:view=frontpage:location=page&display=page_1&langcode=en',
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
          'metadata' => array('langcode' => 'en'),
        ),
        'foo' => array(
          'route_parameters' => array(
            'bar',
            'key' => 'baz',
            'qux',
          ),
          'metadata' => array('langcode' => 'en'),
        ),
        'edge' => array(
          'route_parameters' => array('20011988'),
          'metadata' => array('langcode' => 'en'),
        ),
      ),
      'id' => 'node:node=14031991:langcode=en|foo:0=bar&key=baz&1=qux:langcode=en|edge:0=20011988:langcode=en',
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
