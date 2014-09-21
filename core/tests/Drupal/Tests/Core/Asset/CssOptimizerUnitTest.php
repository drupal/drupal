<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Asset\CssOptimizerUnitTest.
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

  /**
   * A valid file CSS asset group.
   *
   * @var array
   */
  protected $file_css_group;

  /**
   * A valid inline CSS asset group.
   *
   * @var array
   */
  protected $inline_css_group;

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
      // - Retain white-space in selectors. (http://drupal.org/node/472820)
      // - Retain pseudo-selectors. (http://drupal.org/node/460448)
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
      // - Proper URLs in imported files. (http://drupal.org/node/265719)
      // - A background image with relative paths, which must be rewritten.
      // - The rewritten background image path must also be passed through
      //   file_create_url(). (https://drupal.org/node/1961340)
      // - Imported files that are external (protocol-relative URL or not)
      //   should not be expanded. (https://drupal.org/node/2014851)
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
      // Inline. Preprocessing enabled.
      3 => array(
        array(
          'group' => 0,
          'every_page' => FALSE,
          'type' => 'inline',
          'weight' => 0.012,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => '.girlfriend { display: none; }',
          'browsers' => array('IE' => TRUE, '!IE' => TRUE),
        ),
        ".girlfriend{display:none;}\n",
      ),
      // Inline. Preprocessing disabled.
      4 => array(
        array(
          'group' => 0,
          'every_page' => FALSE,
          'type' => 'inline',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => FALSE,
          'data' => '#home body { position: fixed; }',
          'browsers' => array('IE' => TRUE, '!IE' => TRUE),
        ),
        '#home body { position: fixed; }',
      ),
      // File in subfolder. Tests:
      // - CSS import path is properly interpreted. (https://drupal.org/node/1198904)
      // - Don't adjust data URIs (https://drupal.org/node/2142441)
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
   * Tests optimizing a CSS asset containing charset declaration.
   */
  function testOptimizeRemoveCharset() {
    $cases = array(
      array(
        'asset' => array(
          'type' => 'inline', 
          'data' => '@charset "UTF-8";html{font-family:"sans-serif";}',
          'preprocess' => FALSE,
        ),
        'expected' => 'html{font-family:"sans-serif";}',
      ),
      array(
        // This asset contains extra \n character.
        'asset' => array(
          'type' => 'inline',
          'data' => "@charset 'UTF-8';\nhtml{font-family:'sans-serif';}",
          'preprocess' => FALSE,
        ),
        'expected' => "\nhtml{font-family:'sans-serif';}",
      ),
    );
    foreach ($cases as $case) {
      $this->assertEquals(
        $case['expected'],
        $this->optimizer->optimize($case['asset']),
        'CSS optimizing correctly removes the charset declaration.'
      );
    }
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
