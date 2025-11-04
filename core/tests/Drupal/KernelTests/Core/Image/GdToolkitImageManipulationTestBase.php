<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Image;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Image\ImageInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;

// cspell:ignore IMG_NEAREST_NEIGHBOUR

/**
 * GD image toolkit image manipulation test base class.
 */
abstract class GdToolkitImageManipulationTestBase extends KernelTestBase {

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
   */
  protected ImageFactory $imageFactory;

  /**
   * A directory where test image files can be saved to.
   */
  protected string $directory;

  /**
   * The file name of the image under test.
   */
  protected string $sourceTestImage;

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
    $this->directory = 'public://image_test';
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
    $color_index = imagecolorat($toolkit->getImage(), $x, $y);

    $transparent_index = imagecolortransparent($toolkit->getImage());
    if ($color_index == $transparent_index) {
      return [0, 0, 0, 127];
    }

    return array_values(imagecolorsforindex($toolkit->getImage(), $color_index));
  }

  /**
   * Data provider for ::testManipulations().
   */
  public static function providerOperationTestCases(): array {
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
      'convert_avif' => [
        'operation' => 'convert',
        'width' => 40,
        'height' => 20,
        'arguments' => ['extension' => 'avif'],
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
          'width' => 40,
          'height' => 23,
          'corners' => PHP_VERSION_ID < 80500 ? array_fill(0, 4, static::FUCHSIA) : [
            [255, 0, 93, 0],
            static::FUCHSIA,
            static::FUCHSIA,
            static::FUCHSIA,
          ],
        ],
        'rotate_transparent_5' => [
          'operation' => 'rotate',
          'arguments' => ['degrees' => 5],
          'width' => 40,
          'height' => 23,
          'corners' => PHP_VERSION_ID < 80500 ? array_fill(0, 4, static::ROTATE_TRANSPARENT) : [
            [255, 93, 93, 46],
            static::ROTATE_TRANSPARENT,
            static::ROTATE_TRANSPARENT,
            static::ROTATE_TRANSPARENT,
          ],
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
          // Grayscale corners are a bit funky. Each of the corners are a shade
          // of gray. The values of these were determined simply by looking at
          // the final image to see what desaturated colors end up being.
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
    foreach ($test_cases as $test_case => $values) {
      $operation = $values['operation'];
      $arguments = $values['arguments'];
      unset($values['operation'], $values['arguments']);
      $ret[$test_case] = [$test_case, $operation, $arguments, $values];
    }

    return $ret;
  }

  /**
   * Tests height, width and color for the corners for the final images.
   *
   * Since PHP can't visually check that our images have been manipulated
   * properly, build a list of expected color values for each of the corners and
   * the expected height and widths for the final images.
   */
  #[DataProvider('providerOperationTestCases')]
  public function testManipulations(string $test_case, string $operation, array $arguments, array $expected): void {
    // Load up a fresh image.
    $image = $this->imageFactory->get('core/tests/fixtures/files/' . $this->sourceTestImage);
    $toolkit = $image->getToolkit();
    $this->assertTrue($image->isValid());
    $image_original_type = $image->getToolkit()->getType();

    $this->assertTrue(imageistruecolor($toolkit->getImage()), "Image '$this->sourceTestImage' after load should be a truecolor image, but it is not.");

    // Perform our operation.
    $image->apply($operation, $arguments);

    // Flush Image object to disk storage.
    $file_path = $this->directory . '/' . $test_case . image_type_to_extension($image->getToolkit()->getType());
    $image->save($file_path);

    // Check that the both the GD object and the Image object have an accurate
    // record of the dimensions.
    if (isset($expected['height']) && isset($expected['width'])) {
      $this->assertSame($expected['height'], imagesy($toolkit->getImage()), "Image '$this->sourceTestImage' after '$test_case' should have a proper height.");
      $this->assertSame($expected['width'], imagesx($toolkit->getImage()), "Image '$this->sourceTestImage' after '$test_case' should have a proper width.");
      $this->assertSame($expected['height'], $image->getHeight(), "Image '$this->sourceTestImage' after '$test_case' should have a proper height.");
      $this->assertSame($expected['width'], $image->getWidth(), "Image '$this->sourceTestImage' after '$test_case' should have a proper width.");
    }

    // Now check each of the corners to ensure color correctness.
    foreach ($expected['corners'] as $key => $expected_color) {
      // Get the location of the corner.
      [$x, $y] = match ($key) {
        0 => [0, 0],
        1 => [$image->getWidth() - 1, 0],
        2 => [$image->getWidth() - 1, $image->getHeight() - 1],
        3 => [0, $image->getHeight() - 1],
      };

      $actual_color = $this->getPixelColor($image, $x, $y);

      // If image cannot handle transparent colors, skip the pixel color test.
      if ($actual_color[3] === 0 && $expected_color[3] === 127) {
        continue;
      }

      // JPEG and AVIF have small differences in color after processing.
      $tolerance = match($image_original_type) {
        IMAGETYPE_JPEG, IMAGETYPE_AVIF => 3,
        default => 1,
      };

      $this->assertColorsAreEqual($expected_color, $actual_color, $tolerance, "Image '$this->sourceTestImage' object after '$test_case' action has the correct color placement at corner '$key'");
    }

    // Check that saved image reloads without raising PHP errors.
    $image_reloaded = $this->imageFactory->get($file_path);
    $this->assertInstanceOf(\GdImage::class, $image_reloaded->getToolkit()->getImage());
  }

}
