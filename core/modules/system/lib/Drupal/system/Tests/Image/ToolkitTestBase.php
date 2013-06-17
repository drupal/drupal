<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Image\ToolkitTestBase.
 */

namespace Drupal\system\Tests\Image;

use Drupal\simpletest\WebTestBase;
use Drupal\system\Plugin\ImageToolkitManager;
use stdClass;

/**
 * Base class for image manipulation testing.
 */
abstract class ToolkitTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('image_test');

  protected $toolkit;
  protected $file;
  protected $image;

  function setUp() {
    parent::setUp();

    // Use the image_test.module's test toolkit.
    $manager = new ImageToolkitManager($this->container->get('container.namespaces'), $this->container->get('cache.cache'), $this->container->get('language_manager'));
    $this->toolkit = $manager->createInstance('test');

    // Pick a file for testing.
    $file = current($this->drupalGetTestFiles('image'));
    $this->file = $file->uri;

    // Setup a dummy image to work with, this replicate image_load() so we
    // can avoid calling it.
    $this->image = new stdClass();
    $this->image->source = $this->file;
    $this->image->info = image_get_info($this->file);
    $this->image->toolkit = $this->toolkit;

    // Clear out any hook calls.
    $this->imageTestReset();
  }

  /**
   * Assert that all of the specified image toolkit operations were called
   * exactly once once, other values result in failure.
   *
   * @param $expected
   *   Array with string containing with the operation name, e.g. 'load',
   *   'save', 'crop', etc.
   */
  function assertToolkitOperationsCalled(array $expected) {
    // Determine which operations were called.
    $actual = array_keys(array_filter($this->imageTestGetAllCalls()));

    // Determine if there were any expected that were not called.
    $uncalled = array_diff($expected, $actual);
    if (count($uncalled)) {
      $this->assertTrue(FALSE, format_string('Expected operations %expected to be called but %uncalled was not called.', array('%expected' => implode(', ', $expected), '%uncalled' => implode(', ', $uncalled))));
    }
    else {
      $this->assertTrue(TRUE, format_string('All the expected operations were called: %expected', array('%expected' => implode(', ', $expected))));
    }

    // Determine if there were any unexpected calls.
    $unexpected = array_diff($actual, $expected);
    if (count($unexpected)) {
      $this->assertTrue(FALSE, format_string('Unexpected operations were called: %unexpected.', array('%unexpected' => implode(', ', $unexpected))));
    }
    else {
      $this->assertTrue(TRUE, 'No unexpected operations were called.');
    }
  }

  /**
   * Resets/initializes the history of calls to the test toolkit functions.
   */
  function imageTestReset() {
    // Keep track of calls to these operations
    $results = array(
      'load' => array(),
      'save' => array(),
      'settings' => array(),
      'resize' => array(),
      'rotate' => array(),
      'crop' => array(),
      'desaturate' => array(),
    );
    \Drupal::state()->set('image_test.results', $results);
  }

  /**
   * Gets an array of calls to the test toolkit.
   *
   * @return array
   *   An array keyed by operation name ('load', 'save', 'settings', 'resize',
   *   'rotate', 'crop', 'desaturate') with values being arrays of parameters
   *   passed to each call.
   */
  function imageTestGetAllCalls() {
    return \Drupal::state()->get('image_test.results') ?: array();
  }
}
