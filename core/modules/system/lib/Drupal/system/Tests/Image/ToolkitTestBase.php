<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Image\ToolkitTestBase.
 */

namespace Drupal\system\Tests\Image;

use Drupal\simpletest\WebTestBase;
use stdClass;

/**
 * Base class for image manipulation testing.
 */
class ToolkitTestBase extends WebTestBase {
  protected $toolkit;
  protected $file;
  protected $image;

  function setUp() {
    parent::setUp('image_test');

    // Use the image_test.module's test toolkit.
    $this->toolkit = 'test';

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
    image_test_reset();
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
    $actual = array_keys(array_filter(image_test_get_all_calls()));

    // Determine if there were any expected that were not called.
    $uncalled = array_diff($expected, $actual);
    if (count($uncalled)) {
      $this->assertTrue(FALSE, t('Expected operations %expected to be called but %uncalled was not called.', array('%expected' => implode(', ', $expected), '%uncalled' => implode(', ', $uncalled))));
    }
    else {
      $this->assertTrue(TRUE, t('All the expected operations were called: %expected', array('%expected' => implode(', ', $expected))));
    }

    // Determine if there were any unexpected calls.
    $unexpected = array_diff($actual, $expected);
    if (count($unexpected)) {
      $this->assertTrue(FALSE, t('Unexpected operations were called: %unexpected.', array('%unexpected' => implode(', ', $unexpected))));
    }
    else {
      $this->assertTrue(TRUE, t('No unexpected operations were called.'));
    }
  }
}
