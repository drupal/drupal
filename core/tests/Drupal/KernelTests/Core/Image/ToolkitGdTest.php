<?php

namespace Drupal\KernelTests\Core\Image;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the GD image toolkit.
 *
 * @coversDefaultClass \Drupal\system\Plugin\ImageToolkit\GDToolkit
 * @group Image
 * @requires extension gd
 */
class ToolkitGdTest extends KernelTestBase {

  /**
   * Colors that are used in testing.
   */
  protected const BLACK              = [0, 0, 0, 0];
  protected const RED                = [255, 0, 0, 0];
  protected const GREEN              = [0, 255, 0, 0];
  protected const BLUE               = [0, 0, 255, 0];
  protected const YELLOW             = [255, 255, 0, 0];
  protected const WHITE              = [255, 255, 255, 0];
  protected const TRANSPARENT        = [0, 0, 0, 127];
  protected const FUCHSIA            = [255, 0, 255, 0];
  protected const ROTATE_TRANSPARENT = [255, 255, 255, 127];

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * A directory where test image files can be saved to.
   *
   * @var string
   */
  protected $directory;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system']);

    // Set the image factory service.
    $this->imageFactory = $this->container->get('image.factory');
    $this->assertEquals('gd', $this->imageFactory->getToolkitId(), 'The image factory is set to use the \'gd\' image toolkit.');

    // Prepare a directory for test file results.
    $this->directory = 'public://imagetest';
    \Drupal::service('file_system')->prepareDirectory($this->directory, FileSystemInterface::CREATE_DIRECTORY);
  }

  /**
   * Assert two colors are equal by RGBA, net of full transparency.
   *
   * @param int[] $expected
   *   The expected RGBA array.
   * @param int[] $actual
   *   The actual RGBA array.
   * @param int $tolerance
   *   The acceptable difference between the colors.
   * @param string $message
   *   The assertion message.
   */
  protected function assertColorsAreEqual(array $expected, array $actual, int $tolerance, string $message = ''): void {
    // Fully transparent colors are equal, regardless of RGB.
    if ($actual[3] == 127 && $expected[3] == 127) {
      return;
    }
    $distance = pow(($actual[0] - $expected[0]), 2) + pow(($actual[1] - $expected[1]), 2) + pow(($actual[2] - $expected[2]), 2) + pow(($actual[3] - $expected[3]), 2);
    $this->assertLessThanOrEqual($tolerance, $distance, $message . " - Actual: {" . implode(',', $actual) . "}, Expected: {" . implode(',', $expected) . "}, Distance: " . $distance . ", Tolerance: " . $tolerance);
  }

  /**
   * Function for finding a pixel's RGBa values.
   */
  public function getPixelColor(ImageInterface $image, int $x, int $y): array {
    $toolkit = $image->getToolkit();
    $color_index = imagecolorat($toolkit->getResource(), $x, $y);

    $transparent_index = imagecolortransparent($toolkit->getResource());
    if ($color_index == $transparent_index) {
      return [0, 0, 0, 127];
    }

    return array_values(imagecolorsforindex($toolkit->getResource(), $color_index));
  }

  /**
   * Data provider for ::testManipulations().
   */
  public function providerTestImageFiles(): array {
    // Typically the corner colors will be unchanged. These colors are in the
    // order of top-left, top-right, bottom-right, bottom-left.
    $default_corners = [static::RED, static::GREEN, static::BLUE, static::TRANSPARENT];

    // Setup a list of tests to perform on each type.
    $test_cases = [
      'resize' => [
        'operation' => 'resize',
        'arguments' => ['width' => 20, 'height' => 10],
        'width' => 20,
        'height' => 10,
        'corners' => $default_corners,
      ],
      'scale_x' => [
        'operation' => 'scale',
        'arguments' => ['width' => 20],
        'width' => 20,
        'height' => 10,
        'corners' => $default_corners,
      ],
      'scale_y' => [
        'operation' => 'scale',
        'arguments' => ['height' => 10],
        'width' => 20,
        'height' => 10,
        'corners' => $default_corners,
      ],
      'upscale_x' => [
        'operation' => 'scale',
        'arguments' => ['width' => 80, 'upscale' => TRUE],
        'width' => 80,
        'height' => 40,
        'corners' => $default_corners,
      ],
      'upscale_y' => [
        'operation' => 'scale',
        'arguments' => ['height' => 40, 'upscale' => TRUE],
        'width' => 80,
        'height' => 40,
        'corners' => $default_corners,
      ],
      'crop' => [
        'operation' => 'crop',
        'arguments' => ['x' => 12, 'y' => 4, 'width' => 16, 'height' => 12],
        'width' => 16,
        'height' => 12,
        'corners' => array_fill(0, 4, static::WHITE),
      ],
      'scale_and_crop' => [
        'operation' => 'scale_and_crop',
        'arguments' => ['width' => 10, 'height' => 8],
        'width' => 10,
        'height' => 8,
        'corners' => array_fill(0, 4, static::BLACK),
      ],
      'convert_jpg' => [
        'operation' => 'convert',
        'width' => 40,
        'height' => 20,
        'arguments' => ['extension' => 'jpeg'],
        'corners' => $default_corners,
      ],
      'convert_gif' => [
        'operation' => 'convert',
        'width' => 40,
        'height' => 20,
        'arguments' => ['extension' => 'gif'],
        'corners' => $default_corners,
      ],
      'convert_png' => [
        'operation' => 'convert',
        'width' => 40,
        'height' => 20,
        'arguments' => ['extension' => 'png'],
        'corners' => $default_corners,
      ],
      'convert_webp' => [
        'operation' => 'convert',
        'width' => 40,
        'height' => 20,
        'arguments' => ['extension' => 'webp'],
        'corners' => $default_corners,
      ],
    ];

    // Systems using non-bundled GD2 may miss imagerotate(). Test if available.
    if (function_exists('imagerotate')) {
      $test_cases += [
        'rotate_5' => [
          'operation' => 'rotate',
          // Fuchsia background.
          'arguments' => ['degrees' => 5, 'background' => '#FF00FF'],
          // @todo Re-enable dimensions' check once
          //   https://www.drupal.org/project/drupal/issues/2921123 is resolved.
          // 'width' => 41,
          // 'height' => 23,
          'corners' => array_fill(0, 4, static::FUCHSIA),
        ],
        'rotate_transparent_5' => [
          'operation' => 'rotate',
          'arguments' => ['degrees' => 5],
          // @todo Re-enable dimensions' check once
          //   https://www.drupal.org/project/drupal/issues/2921123 is resolved.
          // 'width' => 41,
          // 'height' => 23,
          'corners' => array_fill(0, 4, static::ROTATE_TRANSPARENT),
        ],
        'rotate_90' => [
          'operation' => 'rotate',
          // Fuchsia background.
          'arguments' => ['degrees' => 90, 'background' => '#FF00FF'],
          'width' => 20,
          'height' => 40,
          'corners' => [static::TRANSPARENT, static::RED, static::GREEN, static::BLUE],
        ],
        'rotate_transparent_90' => [
          'operation' => 'rotate',
          'arguments' => ['degrees' => 90],
          'width' => 20,
          'height' => 40,
          'corners' => [static::TRANSPARENT, static::RED, static::GREEN, static::BLUE],
        ],
      ];
    }

    // Systems using non-bundled GD2 may miss imagefilter(). Test if available.
    if (function_exists('imagefilter')) {
      $test_cases += [
        'desaturate' => [
          'operation' => 'desaturate',
          'arguments' => [],
          'height' => 20,
          'width' => 40,
          // Grayscale corners are a bit funky. Each of the corners are a shade of
          // gray. The values of these were determined simply by looking at the
          // final image to see what desaturated colors end up being.
          'corners' => [
            array_fill(0, 3, 76) + [3 => 0],
            array_fill(0, 3, 149) + [3 => 0],
            array_fill(0, 3, 29) + [3 => 0],
            array_fill(0, 3, 225) + [3 => 127],
          ],
        ],
      ];
    }

    $ret = [];
    foreach ([
      'image-test.png',
      'image-test.gif',
      'image-test-no-transparency.gif',
      'image-test.jpg',
      'img-test.webp',
    ] as $file_name) {
      foreach ($test_cases as $test_case => $values) {
        $operation = $values['operation'];
        $arguments = $values['arguments'];
        unset($values['operation'], $values['arguments']);
        $ret[] = [$file_name, $test_case, $operation, $arguments, $values];
      }
    }

    return $ret;
  }

  /**
   * Tests height, width and color for the corners for the final images.
   *
   * Since PHP can't visually check that our images have been manipulated
   * properly, build a list of expected color values for each of the corners and
   * the expected height and widths for the final images.
   *
   * @dataProvider providerTestImageFiles
   */
  public function testManipulations(string $file_name, string $test_case, string $operation, array $arguments, array $expected): void {
    // Load up a fresh image.
    $image = $this->imageFactory->get('core/tests/fixtures/files/' . $file_name);
    $toolkit = $image->getToolkit();
    $this->assertTrue($image->isValid());
    $image_original_type = $image->getToolkit()->getType();

    $this->assertTrue(imageistruecolor($toolkit->getResource()), "Image '$file_name' after load should be a truecolor image, but it is not.");

    // Perform our operation.
    $image->apply($operation, $arguments);

    // Flush Image object to disk storage.
    $file_path = $this->directory . '/' . $test_case . image_type_to_extension($image->getToolkit()->getType());
    $image->save($file_path);

    // Check that the both the GD object and the Image object have an accurate
    // record of the dimensions.
    if (isset($expected['height']) && isset($expected['width'])) {
      $this->assertSame($expected['height'], imagesy($toolkit->getResource()), "Image '$file_name' after '$test_case' should have a proper height.");
      $this->assertSame($expected['width'], imagesx($toolkit->getResource()), "Image '$file_name' after '$test_case' should have a proper width.");
      $this->assertSame($expected['height'], $image->getHeight(), "Image '$file_name' after '$test_case' should have a proper height.");
      $this->assertSame($expected['width'], $image->getWidth(), "Image '$file_name' after '$test_case' should have a proper width.");
    }

    // Now check each of the corners to ensure color correctness.
    foreach ($expected['corners'] as $key => $expected_color) {
      // The test gif that does not have transparency color set is a
      // special case.
      if ($file_name === 'image-test-no-transparency.gif') {
        if ($test_case == 'desaturate') {
          // For desaturating, keep the expected color from the test
          // data, but set alpha channel to fully opaque.
          $expected_color[3] = 0;
        }
        elseif ($expected_color === static::TRANSPARENT) {
          // Set expected pixel to yellow where the others have
          // transparent.
          $expected_color = static::YELLOW;
        }
      }

      // Get the location of the corner.
      switch ($key) {
        case 0:
          $x = 0;
          $y = 0;
          break;

        case 1:
          $x = $image->getWidth() - 1;
          $y = 0;
          break;

        case 2:
          $x = $image->getWidth() - 1;
          $y = $image->getHeight() - 1;
          break;

        case 3:
          $x = 0;
          $y = $image->getHeight() - 1;
          break;
      }
      $actual_color = $this->getPixelColor($image, $x, $y);

      // If image cannot handle transparent colors, skip the pixel color test.
      if ($actual_color[3] === 0 && $expected_color[3] === 127) {
        continue;
      }

      // JPEG has small differences in color after processing.
      $tolerance = $image_original_type === IMAGETYPE_JPEG ? 3 : 0;

      $this->assertColorsAreEqual($expected_color, $actual_color, $tolerance, "Image '$file_name' object after '$test_case' action has the correct color placement at corner '$key'");
    }

    // Check that saved image reloads without raising PHP errors.
    $image_reloaded = $this->imageFactory->get($file_path);
    if (PHP_VERSION_ID >= 80000) {
      $this->assertInstanceOf(\GDImage::class, $image_reloaded->getToolkit()->getResource());
    }
    else {
      $this->assertIsResource($image_reloaded->getToolkit()->getResource());
      $this->assertSame(get_resource_type($image_reloaded->getToolkit()->getResource()), 'gd');
    }
  }

  /**
   * @covers ::getSupportedExtensions
   * @covers ::extensionToImageType
   */
  public function testSupportedExtensions(): void {
    // Test the list of supported extensions.
    $expected_extensions = ['png', 'gif', 'jpeg', 'jpg', 'jpe', 'webp'];
    $this->assertEqualsCanonicalizing($expected_extensions, $this->imageFactory->getSupportedExtensions());

    // Test that the supported extensions map to correct internal GD image
    // types.
    $expected_image_types = [
      'png' => IMAGETYPE_PNG,
      'gif' => IMAGETYPE_GIF,
      'jpeg' => IMAGETYPE_JPEG,
      'jpg' => IMAGETYPE_JPEG,
      'jpe' => IMAGETYPE_JPEG,
      'webp' => IMAGETYPE_WEBP,
    ];
    $image = $this->imageFactory->get();
    foreach ($expected_image_types as $extension => $expected_image_type) {
      $this->assertSame($expected_image_type, $image->getToolkit()->extensionToImageType($extension));
    }
  }

  /**
   * Data provider for ::testCreateImageFromScratch().
   */
  public function providerSupportedImageTypes(): array {
    return [
      [IMAGETYPE_PNG],
      [IMAGETYPE_GIF],
      [IMAGETYPE_JPEG],
      [IMAGETYPE_WEBP],
    ];
  }

  /**
   * Tests that GD functions for the image type are available.
   *
   * @dataProvider providerSupportedImageTypes
   */
  public function testGdFunctionsExist(int $type): void {
    $extension = image_type_to_extension($type, FALSE);
    $this->assertTrue(function_exists("imagecreatefrom$extension"), "imagecreatefrom$extension should exist.");
    $this->assertTrue(function_exists("image$extension"), "image$extension should exist.");
  }

  /**
   * Tests creation of image from scratch, and saving to storage.
   *
   * @dataProvider providerSupportedImageTypes
   */
  public function testCreateImageFromScratch(int $type): void {
    // Build an image from scratch.
    $image = $this->imageFactory->get();
    $image->createNew(50, 20, image_type_to_extension($type, FALSE), '#ffff00');
    $file = 'from_null' . image_type_to_extension($type);
    $file_path = $this->directory . '/' . $file;
    $this->assertSame(50, $image->getWidth());
    $this->assertSame(20, $image->getHeight());
    $this->assertSame(image_type_to_mime_type($type), $image->getMimeType());
    $this->assertTrue($image->save($file_path), "Image '$file' should have been saved successfully, but it has not.");

    // Reload and check saved image.
    $image_reloaded = $this->imageFactory->get($file_path);
    $this->assertTrue($image_reloaded->isValid());
    $this->assertSame(50, $image_reloaded->getWidth());
    $this->assertSame(20, $image_reloaded->getHeight());
    $this->assertSame(image_type_to_mime_type($type), $image_reloaded->getMimeType());
    if ($image_reloaded->getToolkit()->getType() == IMAGETYPE_GIF) {
      $this->assertSame('#ffff00', $image_reloaded->getToolkit()->getTransparentColor(), "Image '$file' after reload should have color channel set to #ffff00, but it has not.");
    }
    else {
      $this->assertNull($image_reloaded->getToolkit()->getTransparentColor(), "Image '$file' after reload should have no color channel set, but it has.");
    }
  }

  /**
   * Tests failures of the 'create_new' operation.
   */
  public function testCreateNewFailures(): void {
    $image = $this->imageFactory->get();
    $image->createNew(-50, 20);
    $this->assertFalse($image->isValid(), 'CreateNew with negative width fails.');
    $image->createNew(50, 20, 'foo');
    $this->assertFalse($image->isValid(), 'CreateNew with invalid extension fails.');
    $image->createNew(50, 20, 'gif', '#foo');
    $this->assertFalse($image->isValid(), 'CreateNew with invalid color hex string fails.');
    $image->createNew(50, 20, 'gif', '#ff0000');
    $this->assertTrue($image->isValid(), 'CreateNew with valid arguments validates the Image.');
  }

  /**
   * Tests that GD resources are freed from memory.
   *
   * @todo Remove the method for PHP 8.0+ https://www.drupal.org/node/3179058
   */
  public function testResourceDestruction() {
    if (PHP_VERSION_ID >= 80000) {
      $this->markTestSkipped('In PHP8 resources are no longer used. \GdImage objects are used instead. These will be garbage collected like the regular objects they are.');
    }
    // Test that an Image object going out of scope releases its GD resource.
    $image = $this->imageFactory->get('core/tests/fixtures/files/image-test.png');
    $res = $image->getToolkit()->getResource();
    $this->assertIsResource($res);
    $image = NULL;
    // @todo In https://www.drupal.org/node/3133236 convert this to
    //   $this->assertIsNotResource($res).
    $this->assertFalse(is_resource($res), 'Image resource was destroyed after losing scope.');

    // Test that 'create_new' operation does not leave orphaned GD resources.
    $image = $this->imageFactory->get('core/tests/fixtures/files/image-test.png');
    $old_res = $image->getToolkit()->getResource();
    // Check if resource has been created successfully.
    $this->assertIsResource($old_res);
    $image->createNew(20, 20);
    $new_res = $image->getToolkit()->getResource();
    // Check if the original resource has been destroyed.
    // @todo In https://www.drupal.org/node/3133236 convert this to
    //   $this->assertIsNotResource($old_res).
    $this->assertFalse(is_resource($old_res));
    // Check if a new resource has been created successfully.
    $this->assertIsResource($new_res);
  }

  /**
   * Tests for GIF images with transparency.
   */
  public function testGifTransparentImages(): void {
    // Test loading an indexed GIF image with transparent color set.
    // Color at top-right pixel should be fully transparent.
    $file = 'image-test-transparent-indexed.gif';
    $image = $this->imageFactory->get('core/tests/fixtures/files/' . $file);
    $resource = $image->getToolkit()->getResource();
    $color_index = imagecolorat($resource, $image->getWidth() - 1, 0);
    $color = array_values(imagecolorsforindex($resource, $color_index));
    $this->assertEquals(static::ROTATE_TRANSPARENT, $color, "Image {$file} after load has full transparent color at corner 1.");

    // Test deliberately creating a GIF image with no transparent color set.
    // Color at top-right pixel should be fully transparent while in memory,
    // fully opaque after flushing image to file.
    $file = 'image-test-no-transparent-color-set.gif';
    $file_path = $this->directory . '/' . $file;
    // Create image.
    $image = $this->imageFactory->get();
    $image->createNew(50, 20, 'gif', NULL);
    $resource = $image->getToolkit()->getResource();
    $color_index = imagecolorat($resource, $image->getWidth() - 1, 0);
    $color = array_values(imagecolorsforindex($resource, $color_index));
    $this->assertEquals(static::ROTATE_TRANSPARENT, $color, "New GIF image with no transparent color set after creation has full transparent color at corner 1.");
    // Save image.
    $this->assertTrue($image->save($file_path), "New GIF image {$file} was saved.");
    // Reload image.
    $image_reloaded = $this->imageFactory->get($file_path);
    $resource = $image_reloaded->getToolkit()->getResource();
    $color_index = imagecolorat($resource, $image_reloaded->getWidth() - 1, 0);
    $color = array_values(imagecolorsforindex($resource, $color_index));
    // Check explicitly for alpha == 0 as the rest of the color has been
    // compressed and may have slight difference from full white.
    $this->assertEquals(0, $color[3], "New GIF image {$file} after reload has no transparent color at corner 1.");

    // Test loading an image whose transparent color index is out of range.
    // This image was generated by taking an initial image with a palette size
    // of 6 colors, and setting the transparent color index to 6 (one higher
    // than the largest allowed index), as follows:
    // @code
    // $image = imagecreatefromgif('core/tests/fixtures/files/image-test.gif');
    // imagecolortransparent($image, 6);
    // imagegif($image, 'core/tests/fixtures/files/image-test-transparent-out-of-range.gif');
    // @endcode
    // This allows us to test that an image with an out-of-range color index
    // can be loaded correctly.
    $file = 'image-test-transparent-out-of-range.gif';
    $image = $this->imageFactory->get('core/tests/fixtures/files/' . $file);
    $this->assertTrue($image->isValid(), "Image '$file' after load should be valid, but it is not.");
    $this->assertTrue(imageistruecolor($image->getToolkit()->getResource()), "Image '$file' after load should be a truecolor image, but it is not.");
  }

  /**
   * Tests calling a missing image operation plugin.
   */
  public function testMissingOperation(): void {
    // Load up a fresh image.
    $image = $this->imageFactory->get('core/tests/fixtures/files/image-test.png');
    $this->assertTrue($image->isValid(), "Image 'image-test.png' after load should be valid, but it is not.");

    // Try perform a missing toolkit operation.
    $this->assertFalse($image->apply('missing_op', []), 'Calling a missing image toolkit operation plugin should fail, but it did not.');
  }

}
