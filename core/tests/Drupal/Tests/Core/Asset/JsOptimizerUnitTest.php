<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Asset\JsOptimizerUnitTest.
 */

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\JsOptimizer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the JS asset optimizer.
 *
 * @group Asset
 */
class JsOptimizerUnitTest extends UnitTestCase {

  /**
   * A JS asset optimizer.
   *
   * @var \Drupal\Core\Asset\JsOptimizer object.
   */
  protected $optimizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->optimizer = new JsOptimizer();
  }

  /**
   * Provides data for the JS asset cleaning test.
   *
   * @see \Drupal\Core\Asset\JsOptimizer::clean().
   *
   * @returns array
   *   An array of test data.
   */
  function providerTestClean() {
    $path = dirname(__FILE__)  . '/js_test_files/';
    return array(
      // File. Tests:
      // - Stripped sourceMappingURL with comment # syntax.
      0 => array(
        file_get_contents($path . 'source_mapping_url.min.js'),
        file_get_contents($path . 'source_mapping_url.min.js.optimized.js'),
      ),
      // File. Tests:
      // - Stripped sourceMappingURL with comment @ syntax.
      1 => array(
        file_get_contents($path . 'source_mapping_url_old.min.js'),
        file_get_contents($path . 'source_mapping_url_old.min.js.optimized.js'),
      ),
      // File. Tests:
      // - Stripped sourceURL with comment # syntax.
      2 => array(
        file_get_contents($path . 'source_url.min.js'),
        file_get_contents($path . 'source_url.min.js.optimized.js'),
      ),
      // File. Tests:
      // - Stripped sourceURL with comment @ syntax.
      3 => array(
        file_get_contents($path . 'source_url_old.min.js'),
        file_get_contents($path . 'source_url_old.min.js.optimized.js'),
      ),
    );
  }

  /**
   * Tests cleaning of a JS asset group containing 'type' => 'file'.
   *
   * @dataProvider providerTestClean
   */
  function testClean($js_asset, $expected) {
    $this->assertEquals($expected, $this->optimizer->clean($js_asset));
  }

  /**
   * Provides data for the JS asset optimize test.
   *
   * @see \Drupal\Core\Asset\JsOptimizer::optimize().
   *
   * @returns array
   *   An array of test data.
   */
  function providerTestOptimize() {
    $path = dirname(__FILE__)  . '/js_test_files/';
    return array(
      0 => array(
        array(
          'type' => 'file',
          'preprocess' => TRUE,
          'data' => $path . 'utf8_bom.js',
        ),
        file_get_contents($path . 'utf8_bom.js.optimized.js'),
      ),
      1 => array(
        array(
          'type' => 'file',
          'preprocess' => TRUE,
          'data' => $path . 'utf16_bom.js',
        ),
        file_get_contents($path . 'utf16_bom.js.optimized.js'),
      ),
      2 => array(
        array(
          'type' => 'file',
          'preprocess' => TRUE,
          'data' => $path . 'latin_9.js',
          'attributes' => array('charset' => 'ISO-8859-15'),
        ),
        file_get_contents($path . 'latin_9.js.optimized.js'),
      ),
    );
  }

  /**
   * Tests cleaning of a JS asset group containing 'type' => 'file'.
   *
   * @dataProvider providerTestOptimize
   */
  function testOptimize($js_asset, $expected) {
    $this->assertEquals($expected, $this->optimizer->optimize($js_asset));
  }

}
