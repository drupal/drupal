<?php

namespace Drupal\FunctionalTests\Image;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Base class for image manipulation testing.
 */
abstract class ToolkitTestBase extends BrowserTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }
  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['image_test'];

  /**
   * The URI for the file.
   *
   * @var string
   */
  protected $file;

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The image object for the test file.
   *
   * @var \Drupal\Core\Image\ImageInterface
   */
  protected $image;

  protected function setUp() {
    parent::setUp();

    // Set the image factory service.
    $this->imageFactory = $this->container->get('image.factory');

    // Pick a file for testing.
    $file = current($this->drupalGetTestFiles('image'));
    $this->file = $file->uri;

    // Setup a dummy image to work with.
    $this->image = $this->getImage();

    // Clear out any hook calls.
    $this->imageTestReset();
  }

  /**
   * Sets up an image with the custom toolkit.
   *
   * @return \Drupal\Core\Image\ImageInterface
   *   The image object.
   */
  protected function getImage() {
    $image = $this->imageFactory->get($this->file, 'test');
    $this->assertTrue($image->isValid(), 'Image file was parsed.');
    return $image;
  }

  /**
   * Assert that all of the specified image toolkit operations were called
   * exactly once once, other values result in failure.
   *
   * @param $expected
   *   Array with string containing with the operation name, e.g. 'load',
   *   'save', 'crop', etc.
   */
  public function assertToolkitOperationsCalled(array $expected) {
    // If one of the image operations is expected, apply should be expected as
    // well.
    $operations = [
      'resize',
      'rotate',
      'crop',
      'desaturate',
      'create_new',
      'scale',
      'scale_and_crop',
      'my_operation',
      'convert',
    ];
    if (count(array_intersect($expected, $operations)) > 0 && !in_array('apply', $expected)) {
      $expected[] = 'apply';
    }

    // Determine which operations were called.
    $actual = array_keys(array_filter($this->imageTestGetAllCalls()));

    // Determine if there were any expected that were not called.
    $uncalled = array_diff($expected, $actual);
    if (count($uncalled)) {
      $this->assertTrue(FALSE, new FormattableMarkup('Expected operations %expected to be called but %uncalled was not called.', ['%expected' => implode(', ', $expected), '%uncalled' => implode(', ', $uncalled)]));
    }
    else {
      $this->assertTrue(TRUE, new FormattableMarkup('All the expected operations were called: %expected', ['%expected' => implode(', ', $expected)]));
    }

    // Determine if there were any unexpected calls.
    // If all unexpected calls are operations and apply was expected, we do not
    // count it as an error.
    $unexpected = array_diff($actual, $expected);
    if (count($unexpected) && (!in_array('apply', $expected) || count(array_intersect($unexpected, $operations)) !== count($unexpected))) {
      $this->assertTrue(FALSE, new FormattableMarkup('Unexpected operations were called: %unexpected.', ['%unexpected' => implode(', ', $unexpected)]));
    }
    else {
      $this->assertTrue(TRUE, 'No unexpected operations were called.');
    }
  }

  /**
   * Resets/initializes the history of calls to the test toolkit functions.
   */
  protected function imageTestReset() {
    // Keep track of calls to these operations
    $results = [
      'parseFile' => [],
      'save' => [],
      'settings' => [],
      'apply' => [],
      'resize' => [],
      'rotate' => [],
      'crop' => [],
      'desaturate' => [],
      'create_new' => [],
      'scale' => [],
      'scale_and_crop' => [],
      'convert' => [],
    ];
    \Drupal::state()->set('image_test.results', $results);
  }

  /**
   * Gets an array of calls to the test toolkit.
   *
   * @return array
   *   An array keyed by operation name ('parseFile', 'save', 'settings',
   *   'resize', 'rotate', 'crop', 'desaturate') with values being arrays of
   *   parameters passed to each call.
   */
  protected function imageTestGetAllCalls() {
    return \Drupal::state()->get('image_test.results', []);
  }

}
