<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Asset\CssOptimizerUnitTest.
 */


namespace {

/**
 * CssOptimizer uses file_create_url(), which *is* available when using the
 * Simpletest test runner, but not when using the PHPUnit test runner; hence
 * this hack.
 */
if (!function_exists('file_create_url')) {

  /**
   * Temporary mock for file_create_url(), until that is moved into
   * Component/Utility.
   */
  function file_create_url($uri) {
    return 'file_create_url:' . $uri;
  }

}

if (!function_exists('file_uri_scheme')) {

  function file_uri_scheme($uri) {
    return FALSE;
  }

}

}


namespace Drupal\Tests\Core\Asset {

use Drupal\Core\Asset\CssOptimizer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CSS asset optimizer.
 *
 * @group Asset
 */
class CssOptimizerUnitTest extends UnitTestCase {

  /**
   * A CSS asset optimizer.
   *
   * @var \Drupal\Core\Asset\CssOptimizer object.
   */
  protected $optimizer;

  protected function setUp() {
    parent::setUp();

    $this->optimizer = new CssOptimizer();
  }

  /**
   * Provides data for the CSS asset optimizing test.
   */
  function providerTestOptimize() {
    $path = dirname(__FILE__)  . '/css_test_files/';
    return array(
      // File. Tests:
      // - Stripped comments and white-space.
      // - Retain white-space in selectors. (https://www.drupal.org/node/472820)
      // - Retain pseudo-selectors. (https://www.drupal.org/node/460448)
      0 => array(
        array(
          'group' => -100,
          'every_page' => TRUE,
          'type' => 'file',
          'weight' => 0.012,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'css_input_without_import.css',
          'browsers' => array('IE' => TRUE, '!IE' => TRUE),
          'basename' => 'css_input_without_import.css',
        ),
        file_get_contents($path . 'css_input_without_import.css.optimized.css'),
      ),
      // File. Tests:
      // - Proper URLs in imported files. (https://www.drupal.org/node/265719)
      // - A background image with relative paths, which must be rewritten.
      // - The rewritten background image path must also be passed through
      //   file_create_url(). (https://www.drupal.org/node/1961340)
      // - Imported files that are external (protocol-relative URL or not)
      //   should not be expanded. (https://www.drupal.org/node/2014851)
      1 => array(
        array(
          'group' => -100,
          'every_page' => TRUE,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'css_input_with_import.css',
          'browsers' => array('IE' => TRUE, '!IE' => TRUE),
          'basename' => 'css_input_with_import.css',
        ),
        str_replace('url(images/icon.png)', 'url(' . file_create_url($path . 'images/icon.png') . ')', file_get_contents($path . 'css_input_with_import.css.optimized.css')),
      ),
      // File. Tests:
      // - Retain comment hacks.
      2 => array(
        array(
          'group' => -100,
          'every_page' => TRUE,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'comment_hacks.css',
          'browsers' => array('IE' => TRUE, '!IE' => TRUE),
          'basename' => 'comment_hacks.css',
        ),
        file_get_contents($path . 'comment_hacks.css.optimized.css'),
      ),
      // File in subfolder. Tests:
      // - CSS import path is properly interpreted.
      //   (https://www.drupal.org/node/1198904)
      // - Don't adjust data URIs (https://www.drupal.org/node/2142441)
      5 => array(
        array(
          'group' => -100,
          'every_page' => TRUE,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'css_subfolder/css_input_with_import.css',
          'browsers' => array('IE' => TRUE, '!IE' => TRUE),
          'basename' => 'css_input_with_import.css',
        ),
        str_replace('url(../images/icon.png)', 'url(' . file_create_url($path . 'images/icon.png') . ')', file_get_contents($path . 'css_subfolder/css_input_with_import.css.optimized.css')),
      ),
      // File. Tests:
      // - Any @charaset declaration at the beginning of a file should be
      //   removed without breaking subsequent CSS.
      6 => array(
        array(
          'group' => -100,
          'every_page' => TRUE,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'charset_sameline.css',
          'browsers' => array('IE' => TRUE, '!IE' => TRUE),
          'basename' => 'charset_sameline.css',
        ),
        file_get_contents($path . 'charset.css.optimized.css'),
      ),
      7 => array(
        array(
          'group' => -100,
          'every_page' => TRUE,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'charset_newline.css',
          'browsers' => array('IE' => TRUE, '!IE' => TRUE),
          'basename' => 'charset_newline.css',
        ),
        file_get_contents($path . 'charset.css.optimized.css'),
      ),
      6 => array(
        array(
          'group' => -100,
          'every_page' => TRUE,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'css_input_with_bom.css',
          'browsers' => array('IE' => TRUE, '!IE' => TRUE),
          'basename' => 'css_input_with_bom.css',
        ),
        '.byte-order-mark-test{content:"☃";}'. "\n",
      ),
      7 => array(
        array(
          'group' => -100,
          'every_page' => TRUE,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'css_input_with_charset.css',
          'browsers' => array('IE' => TRUE, '!IE' => TRUE),
          'basename' => 'css_input_with_charset.css',
        ),
        '.charset-test{content:"€";}' . "\n",
      ),
      8 => array(
        array(
          'group' => -100,
          'every_page' => TRUE,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'css_input_with_bom_and_charset.css',
          'browsers' => array('IE' => TRUE, '!IE' => TRUE),
          'basename' => 'css_input_with_bom_and_charset.css',
        ),
        '.byte-order-mark-charset-test{content:"☃";}' . "\n",
      ),
      9 => array(
        array(
          'group' => -100,
          'every_page' => TRUE,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'css_input_with_utf16_bom.css',
          'browsers' => array('IE' => TRUE, '!IE' => TRUE),
          'basename' => 'css_input_with_utf16_bom.css',
        ),
        '.utf16-byte-order-mark-test{content:"☃";}' . "\n",
      ),
    );
  }

  /**
   * Tests optimizing a CSS asset group containing 'type' => 'file'.
   *
   * @dataProvider providerTestOptimize
   */
  function testOptimize($css_asset, $expected) {
    $this->assertEquals($expected, $this->optimizer->optimize($css_asset), 'Group of file CSS assets optimized correctly.');
  }

  /**
   * Tests a file CSS asset with preprocessing disabled.
   */
  function testTypeFilePreprocessingDisabled() {
    $this->setExpectedException('Exception', 'Only file CSS assets with preprocessing enabled can be optimized.');

    $css_asset = array(
      'group' => -100,
      'every_page' => TRUE,
      'type' => 'file',
      'weight' => 0.012,
      'media' => 'all',
      // Preprocessing disabled.
      'preprocess' => FALSE,
      'data' => 'tests/Drupal/Tests/Core/Asset/foo.css',
      'browsers' => array('IE' => TRUE, '!IE' => TRUE),
      'basename' => 'foo.css',
    );
    $this->optimizer->optimize($css_asset);
  }

  /**
   * Tests a CSS asset with 'type' => 'external'.
   */
  function testTypeExternal() {
    $this->setExpectedException('Exception', 'Only file or inline CSS assets can be optimized.');

    $css_asset = array(
      'group' => -100,
      'every_page' => TRUE,
      // Type external.
      'type' => 'external',
      'weight' => 0.012,
      'media' => 'all',
      'preprocess' => TRUE,
      'data' => 'http://example.com/foo.js',
      'browsers' => array('IE' => TRUE, '!IE' => TRUE),
    );
    $this->optimizer->optimize($css_asset);
  }

}
}
