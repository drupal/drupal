<?php

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\CssOptimizer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CSS asset optimizer.
 *
 * @group Asset
 */
class CssOptimizerUnitTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected $backupGlobals = FALSE;

  /**
   * A CSS asset optimizer.
   *
   * @var \Drupal\Core\Asset\CssOptimizer
   */
  protected $optimizer;

  protected function setUp() {
    parent::setUp();

    $this->optimizer = new CssOptimizer();
  }

  /**
   * Provides data for the CSS asset optimizing test.
   */
  public function providerTestOptimize() {
    $path = 'core/tests/Drupal/Tests/Core/Asset/css_test_files/';
    $absolute_path = dirname(__FILE__) . '/css_test_files/';
    return [
      // File. Tests:
      // - Stripped comments and white-space.
      // - Retain white-space in selectors. (https://www.drupal.org/node/472820)
      // - Retain pseudo-selectors. (https://www.drupal.org/node/460448)
      [
        [
          'group' => -100,
          'type' => 'file',
          'weight' => 0.012,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'css_input_without_import.css',
          'browsers' => ['IE' => TRUE, '!IE' => TRUE],
          'basename' => 'css_input_without_import.css',
        ],
        file_get_contents($absolute_path . 'css_input_without_import.css.optimized.css'),
      ],
      // File. Tests:
      // - Proper URLs in imported files. (https://www.drupal.org/node/265719)
      // - A background image with relative paths, which must be rewritten.
      // - The rewritten background image path must also be passed through
      //   file_create_url(). (https://www.drupal.org/node/1961340)
      // - Imported files that are external (protocol-relative URL or not)
      //   should not be expanded. (https://www.drupal.org/node/2014851)
      [
        [
          'group' => -100,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'css_input_with_import.css',
          'browsers' => ['IE' => TRUE, '!IE' => TRUE],
          'basename' => 'css_input_with_import.css',
        ],
        str_replace('url(images/icon.png)', 'url(' . file_url_transform_relative(file_create_url($path . 'images/icon.png')) . ')', file_get_contents($absolute_path . 'css_input_with_import.css.optimized.css')),
      ],
      // File. Tests:
      // - Retain comment hacks.
      [
        [
          'group' => -100,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'comment_hacks.css',
          'browsers' => ['IE' => TRUE, '!IE' => TRUE],
          'basename' => 'comment_hacks.css',
        ],
        file_get_contents($absolute_path . 'comment_hacks.css.optimized.css'),
      ],
      // File in subfolder. Tests:
      // - CSS import path is properly interpreted.
      //   (https://www.drupal.org/node/1198904)
      // - Don't adjust data URIs (https://www.drupal.org/node/2142441)
      [
        [
          'group' => -100,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'css_subfolder/css_input_with_import.css',
          'browsers' => ['IE' => TRUE, '!IE' => TRUE],
          'basename' => 'css_input_with_import.css',
        ],
        str_replace('url(../images/icon.png)', 'url(' . file_url_transform_relative(file_create_url($path . 'images/icon.png')) . ')', file_get_contents($absolute_path . 'css_subfolder/css_input_with_import.css.optimized.css')),
      ],
      // File. Tests:
      // - Any @charset declaration at the beginning of a file should be
      //   removed without breaking subsequent CSS.
      [
        [
          'group' => -100,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'charset_sameline.css',
          'browsers' => ['IE' => TRUE, '!IE' => TRUE],
          'basename' => 'charset_sameline.css',
        ],
        file_get_contents($absolute_path . 'charset.css.optimized.css'),
      ],
      [
        [
          'group' => -100,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'charset_newline.css',
          'browsers' => ['IE' => TRUE, '!IE' => TRUE],
          'basename' => 'charset_newline.css',
        ],
        file_get_contents($absolute_path . 'charset.css.optimized.css'),
      ],
      [
        [
          'group' => -100,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'css_input_with_bom.css',
          'browsers' => ['IE' => TRUE, '!IE' => TRUE],
          'basename' => 'css_input_with_bom.css',
        ],
        '.byte-order-mark-test{content:"☃";}' . "\n",
      ],
      [
        [
          'group' => -100,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'css_input_with_charset.css',
          'browsers' => ['IE' => TRUE, '!IE' => TRUE],
          'basename' => 'css_input_with_charset.css',
        ],
        '.charset-test{content:"€";}' . "\n",
      ],
      [
        [
          'group' => -100,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'css_input_with_bom_and_charset.css',
          'browsers' => ['IE' => TRUE, '!IE' => TRUE],
          'basename' => 'css_input_with_bom_and_charset.css',
        ],
        '.byte-order-mark-charset-test{content:"☃";}' . "\n",
      ],
      [
        [
          'group' => -100,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'css_input_with_utf16_bom.css',
          'browsers' => ['IE' => TRUE, '!IE' => TRUE],
          'basename' => 'css_input_with_utf16_bom.css',
        ],
        '.utf16-byte-order-mark-test{content:"☃";}' . "\n",
      ],
      [
        [
          'group' => -100,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => $path . 'quotes.css',
          'browsers' => ['IE' => TRUE, '!IE' => TRUE],
          'basename' => 'quotes.css',
        ],
        file_get_contents($absolute_path . 'quotes.css.optimized.css'),
      ],
    ];
  }

  /**
   * Tests optimizing a CSS asset group containing 'type' => 'file'.
   *
   * @dataProvider providerTestOptimize
   */
  public function testOptimize($css_asset, $expected) {
    global $base_path;
    $original_base_path = $base_path;
    $base_path = '/';

    // \Drupal\Core\Asset\CssOptimizer::loadFile() relies on the current working
    // directory being the one that is used when index.php is the entry point.
    // Note: PHPUnit automatically restores the original working directory.
    chdir(realpath(__DIR__ . '/../../../../../../'));

    $this->assertEquals($expected, $this->optimizer->optimize($css_asset), 'Group of file CSS assets optimized correctly.');

    $base_path = $original_base_path;
  }

  /**
   * Tests a file CSS asset with preprocessing disabled.
   */
  public function testTypeFilePreprocessingDisabled() {
    $this->setExpectedException('Exception', 'Only file CSS assets with preprocessing enabled can be optimized.');

    $css_asset = [
      'group' => -100,
      'type' => 'file',
      'weight' => 0.012,
      'media' => 'all',
      // Preprocessing disabled.
      'preprocess' => FALSE,
      'data' => 'tests/Drupal/Tests/Core/Asset/foo.css',
      'browsers' => ['IE' => TRUE, '!IE' => TRUE],
      'basename' => 'foo.css',
    ];
    $this->optimizer->optimize($css_asset);
  }

  /**
   * Tests a CSS asset with 'type' => 'external'.
   */
  public function testTypeExternal() {
    $this->setExpectedException('Exception', 'Only file CSS assets can be optimized.');

    $css_asset = [
      'group' => -100,
      // Type external.
      'type' => 'external',
      'weight' => 0.012,
      'media' => 'all',
      'preprocess' => TRUE,
      'data' => 'http://example.com/foo.js',
      'browsers' => ['IE' => TRUE, '!IE' => TRUE],
    ];
    $this->optimizer->optimize($css_asset);
  }

}

/**
 * Temporary mock for file_create_url(), until that is moved into
 * Component/Utility.
 */
if (!function_exists('Drupal\Tests\Core\Asset\file_create_url')) {

  function file_create_url($uri) {
    return 'file_create_url:' . $uri;
  }

}

/**
 * Temporary mock of file_url_transform_relative, until that is moved into
 * Component/Utility.
 */
if (!function_exists('Drupal\Tests\Core\Asset\file_url_transform_relative')) {

  function file_url_transform_relative($uri) {
    return 'file_url_transform_relative:' . $uri;
  }

}

/**
 * CssCollectionRenderer uses file_create_url() & file_url_transform_relative(),
 * which *are* available when using the Simpletest test runner, but not when
 * using the PHPUnit test runner; hence this hack.
 */
namespace Drupal\Core\Asset;

if (!function_exists('Drupal\Core\Asset\file_create_url')) {

  /**
   * Temporary mock for file_create_url(), until that is moved into
   * Component/Utility.
   */
  function file_create_url($uri) {
    return \Drupal\Tests\Core\Asset\file_create_url($uri);
  }

}
if (!function_exists('Drupal\Core\Asset\file_url_transform_relative')) {

  /**
   * Temporary mock of file_url_transform_relative, until that is moved into
   * Component/Utility.
   */
  function file_url_transform_relative($uri) {
    return \Drupal\Tests\Core\Asset\file_url_transform_relative($uri);
  }

}
if (!function_exists('Drupal\Core\Asset\file_uri_scheme')) {

  function file_uri_scheme($uri) {
    return FALSE;
  }

}
