<?php

declare(strict_types=1);

namespace Drupal\Tests\Traits\Core\Image;

use Drupal\Core\Image\ImageInterface;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Provides common methods for image toolkit kernel tests.
 *
 * The testing class must ensure that image_test.module is enabled.
 */
trait ToolkitTestTrait {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * Resets/initializes the history of calls to the test toolkit functions.
   */
  protected function imageTestReset(): void {
    \Drupal::state()->delete('image_test.results');
  }

  /**
   * Assert that all of the specified image toolkit operations were called once.
   *
   * @param string[] $expected
   *   String array containing the operation names, e.g. load, save, crop, etc.
   */
  public function assertToolkitOperationsCalled(array $expected): void {
    // If one of the image operations is expected, 'apply' should be expected as
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
      'failing',
    ];
    if (count(array_intersect($expected, $operations)) > 0 && !in_array('apply', $expected)) {
      $expected[] = 'apply';
    }

    // Determine which operations were called.
    $actual = array_keys(array_filter($this->imageTestGetAllCalls()));

    // Determine if there were any expected that were not called.
    $uncalled = array_diff($expected, $actual);
    $this->assertEmpty($uncalled);

    // Determine if there were any unexpected calls. If all unexpected calls are
    // operations and apply was expected, we do not count it as an error.
    $unexpected = array_diff($actual, $expected);
    $assert = !(count($unexpected) && (!in_array('apply', $expected) || count(array_intersect($unexpected, $operations)) !== count($unexpected)));
    $this->assertTrue($assert);
  }

  /**
   * Gets an array of calls to the 'test' toolkit.
   *
   * @return array
   *   An array keyed by operation name ('parseFile', 'save', 'settings',
   *   'resize', 'rotate', 'crop', 'desaturate') with values being arrays of
   *   parameters passed to each call.
   */
  protected function imageTestGetAllCalls(): array {
    return \Drupal::state()->get('image_test.results', []);
  }

  /**
   * Sets up an image with the custom toolkit.
   *
   * @return \Drupal\Core\Image\ImageInterface
   *   The image object.
   */
  protected function getImage(): ImageInterface {
    $image_factory = \Drupal::service('image.factory');
    $file = current($this->drupalGetTestFiles('image'));
    $image = $image_factory->get($file->uri, 'test');
    $this->assertTrue($image->isValid());
    return $image;
  }

  /**
   * Asserts the effect processing of an image effect plugin.
   *
   * @param string[] $expected_operations
   *   String array containing the operation names, e.g. load, save, crop, etc.
   * @param string $effect_name
   *   The name of the image effect to test.
   * @param array $data
   *   The data to be passed to the image effect.
   */
  protected function assertImageEffect(array $expected_operations, string $effect_name, array $data): void {
    $effect = $this->imageEffectPluginManager->createInstance($effect_name, ['data' => $data]);
    $image = $this->getImage();
    $this->imageTestReset();
    // The test toolkit does not actually implement the operation plugins,
    // therefore the calls to TestToolkit::apply() will fail. That's not a
    // problem here, we are not testing the actual operations.
    $this->assertFalse($effect->applyEffect($image));
    $this->assertToolkitOperationsCalled($expected_operations);
  }

}
