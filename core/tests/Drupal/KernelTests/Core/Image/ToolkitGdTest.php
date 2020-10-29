<?php

namespace Drupal\KernelTests\Core\Image;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that core image manipulations work properly: scale, resize, rotate,
 * crop, scale and crop, and desaturate.
 *
 * @group Image
 */
class ToolkitGdTest extends KernelTestBase {

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  // Colors that are used in testing.
  protected $black       = [0, 0, 0, 0];
  protected $red         = [255, 0, 0, 0];
  protected $green       = [0, 255, 0, 0];
  protected $blue        = [0, 0, 255, 0];
  protected $yellow      = [255, 255, 0, 0];
  protected $white       = [255, 255, 255, 0];
  protected $transparent = [0, 0, 0, 127];
  // Used as rotate background colors.
  protected $fuchsia           = [255, 0, 255, 0];
  protected $rotateTransparent = [255, 255, 255, 127];

  protected $width = 40;
  protected $height = 20;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set the image factory service.
    $this->imageFactory = $this->container->get('image.factory');
  }

  protected function checkRequirements() {
    // GD2 support is available.
    if (!function_exists('imagegd2')) {
      return [
        'Image manipulations for the GD toolkit cannot run because the GD toolkit is not available.',
      ];
    }
    return parent::checkRequirements();
  }

  /**
   * Function to compare two colors by RGBa.
   */
  public function colorsAreEqual($color_a, $color_b) {
    // Fully transparent pixels are equal, regardless of RGB.
    if ($color_a[3] == 127 && $color_b[3] == 127) {
      return TRUE;
    }

    foreach ($color_a as $key => $value) {
      if ($color_b[$key] != $value) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Function for finding a pixel's RGBa values.
   */
  public function getPixelColor(ImageInterface $image, $x, $y) {
    $toolkit = $image->getToolkit();
    $color_index = imagecolorat($toolkit->getResource(), $x, $y);

    $transparent_index = imagecolortransparent($toolkit->getResource());
    if ($color_index == $transparent_index) {
      return [0, 0, 0, 127];
    }

    return array_values(imagecolorsforindex($toolkit->getResource(), $color_index));
  }

  /**
   * Since PHP can't visually check that our images have been manipulated
   * properly, build a list of expected color values for each of the corners and
   * the expected height and widths for the final images.
   */
  public function testManipulations() {

    // Test that the image factory is set to use the GD toolkit.
    $this->assertEqual($this->imageFactory->getToolkitId(), 'gd', 'The image factory is set to use the \'gd\' image toolkit.');

    // Test the list of supported extensions.
    $expected_extensions = ['png', 'gif', 'jpeg', 'jpg', 'jpe'];
    $supported_extensions = $this->imageFactory->getSupportedExtensions();
    $this->assertEqual($expected_extensions, array_intersect($expected_extensions, $supported_extensions));

    // Test that the supported extensions map to correct internal GD image
    // types.
    $expected_image_types = [
      'png' => IMAGETYPE_PNG,
      'gif' => IMAGETYPE_GIF,
      'jpeg' => IMAGETYPE_JPEG,
      'jpg' => IMAGETYPE_JPEG,
      'jpe' => IMAGETYPE_JPEG,
    ];
    $image = $this->imageFactory->get();
    foreach ($expected_image_types as $extension => $expected_image_type) {
      $image_type = $image->getToolkit()->extensionToImageType($extension);
      $this->assertSame($expected_image_type, $image_type);
    }

    // Typically the corner colors will be unchanged. These colors are in the
    // order of top-left, top-right, bottom-right, bottom-left.
    $default_corners = [$this->red, $this->green, $this->blue, $this->transparent];

    // A list of files that will be tested.
    $files = [
      'image-test.png',
      'image-test.gif',
      'image-test-no-transparency.gif',
      'image-test.jpg',
    ];

    // Setup a list of tests to perform on each type.
    $operations = [
      'resize' => [
        'function' => 'resize',
        'arguments' => ['width' => 20, 'height' => 10],
        'width' => 20,
        'height' => 10,
        'corners' => $default_corners,
      ],
      'scale_x' => [
        'function' => 'scale',
        'arguments' => ['width' => 20],
        'width' => 20,
        'height' => 10,
        'corners' => $default_corners,
      ],
      'scale_y' => [
        'function' => 'scale',
        'arguments' => ['height' => 10],
        'width' => 20,
        'height' => 10,
        'corners' => $default_corners,
      ],
      'upscale_x' => [
        'function' => 'scale',
        'arguments' => ['width' => 80, 'upscale' => TRUE],
        'width' => 80,
        'height' => 40,
        'corners' => $default_corners,
      ],
      'upscale_y' => [
        'function' => 'scale',
        'arguments' => ['height' => 40, 'upscale' => TRUE],
        'width' => 80,
        'height' => 40,
        'corners' => $default_corners,
      ],
      'crop' => [
        'function' => 'crop',
        'arguments' => ['x' => 12, 'y' => 4, 'width' => 16, 'height' => 12],
        'width' => 16,
        'height' => 12,
        'corners' => array_fill(0, 4, $this->white),
      ],
      'scale_and_crop' => [
        'function' => 'scale_and_crop',
        'arguments' => ['width' => 10, 'height' => 8],
        'width' => 10,
        'height' => 8,
        'corners' => array_fill(0, 4, $this->black),
      ],
      'convert_jpg' => [
        'function' => 'convert',
        'width' => 40,
        'height' => 20,
        'arguments' => ['extension' => 'jpeg'],
        'corners' => $default_corners,
      ],
      'convert_gif' => [
        'function' => 'convert',
        'width' => 40,
        'height' => 20,
        'arguments' => ['extension' => 'gif'],
        'corners' => $default_corners,
      ],
      'convert_png' => [
        'function' => 'convert',
        'width' => 40,
        'height' => 20,
        'arguments' => ['extension' => 'png'],
        'corners' => $default_corners,
      ],
    ];

    // Systems using non-bundled GD2 don't have imagerotate. Test if available.
    // @todo Remove the version check once
    //   https://www.drupal.org/project/drupal/issues/2670966 is resolved.
    if (function_exists('imagerotate') && (version_compare(phpversion(), '7.0.26') < 0)) {
      $operations += [
        'rotate_5' => [
          'function' => 'rotate',
          // Fuchsia background.
          'arguments' => ['degrees' => 5, 'background' => '#FF00FF'],
          'width' => 41,
          'height' => 23,
          'corners' => array_fill(0, 4, $this->fuchsia),
        ],
        'rotate_90' => [
          'function' => 'rotate',
          // Fuchsia background.
          'arguments' => ['degrees' => 90, 'background' => '#FF00FF'],
          'width' => 20,
          'height' => 40,
          'corners' => [$this->transparent, $this->red, $this->green, $this->blue],
        ],
        'rotate_transparent_5' => [
          'function' => 'rotate',
          'arguments' => ['degrees' => 5],
          'width' => 41,
          'height' => 23,
          'corners' => array_fill(0, 4, $this->rotateTransparent),
        ],
        'rotate_transparent_90' => [
          'function' => 'rotate',
          'arguments' => ['degrees' => 90],
          'width' => 20,
          'height' => 40,
          'corners' => [$this->transparent, $this->red, $this->green, $this->blue],
        ],
      ];
    }

    // Systems using non-bundled GD2 don't have imagefilter. Test if available.
    if (function_exists('imagefilter')) {
      $operations += [
        'desaturate' => [
          'function' => 'desaturate',
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

    // Prepare a directory for test file results.
    $directory = Settings::get('file_public_path') . '/imagetest';
    \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    foreach ($files as $file) {
      foreach ($operations as $op => $values) {
        // Load up a fresh image.
        $image = $this->imageFactory->get('core/tests/fixtures/files/' . $file);
        $toolkit = $image->getToolkit();
        if (!$image->isValid()) {
          $this->fail(new FormattableMarkup('Could not load image %file.', ['%file' => $file]));
          continue 2;
        }
        $image_original_type = $image->getToolkit()->getType();

        // All images should be converted to truecolor when loaded.
        $image_truecolor = imageistruecolor($toolkit->getResource());
        $this->assertTrue($image_truecolor, new FormattableMarkup('Image %file after load is a truecolor image.', ['%file' => $file]));

        // Store the original GD resource.
        $old_res = $toolkit->getResource();

        // Perform our operation.
        $image->apply($values['function'], $values['arguments']);

        // If the operation replaced the resource, check that the old one has
        // been destroyed.
        $new_res = $toolkit->getResource();
        if ($new_res !== $old_res) {
          // @todo In https://www.drupal.org/node/3133236 convert this to
          //   $this->assertIsNotResource($old_res).
          $this->assertFalse(is_resource($old_res), new FormattableMarkup("'%operation' destroyed the original resource.", ['%operation' => $values['function']]));
        }

        // To keep from flooding the test with assert values, make a general
        // value for whether each group of values fail.
        $correct_dimensions_real = TRUE;
        $correct_dimensions_object = TRUE;

        if (imagesy($toolkit->getResource()) != $values['height'] || imagesx($toolkit->getResource()) != $values['width']) {
          $correct_dimensions_real = FALSE;
        }

        // Check that the image object has an accurate record of the dimensions.
        if ($image->getWidth() != $values['width'] || $image->getHeight() != $values['height']) {
          $correct_dimensions_object = FALSE;
        }

        $file_path = $directory . '/' . $op . image_type_to_extension($image->getToolkit()->getType());
        $image->save($file_path);

        $this->assertTrue($correct_dimensions_real, new FormattableMarkup('Image %file after %action action has proper dimensions.', ['%file' => $file, '%action' => $op]));
        $this->assertTrue($correct_dimensions_object, new FormattableMarkup('Image %file object after %action action is reporting the proper height and width values.', ['%file' => $file, '%action' => $op]));

        // JPEG colors will always be messed up due to compression. So we skip
        // these tests if the original or the result is in jpeg format.
        if ($image->getToolkit()->getType() != IMAGETYPE_JPEG && $image_original_type != IMAGETYPE_JPEG) {
          // Now check each of the corners to ensure color correctness.
          foreach ($values['corners'] as $key => $corner) {
            // The test gif that does not have transparency color set is a
            // special case.
            if ($file === 'image-test-no-transparency.gif') {
              if ($op == 'desaturate') {
                // For desaturating, keep the expected color from the test
                // data, but set alpha channel to fully opaque.
                $corner[3] = 0;
              }
              elseif ($corner === $this->transparent) {
                // Set expected pixel to yellow where the others have
                // transparent.
                $corner = $this->yellow;
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
            $color = $this->getPixelColor($image, $x, $y);
            // We also skip the color test for transparency for gif <-> png
            // conversion. The convert operation cannot handle that correctly.
            if ($image->getToolkit()->getType() == $image_original_type || $corner != $this->transparent) {
              $correct_colors = $this->colorsAreEqual($color, $corner);
              $this->assertTrue($correct_colors, new FormattableMarkup('Image %file object after %action action has the correct color placement at corner %corner.',
                ['%file' => $file, '%action' => $op, '%corner' => $key]));
            }
          }
        }

        // Check that saved image reloads without raising PHP errors.
        $image_reloaded = $this->imageFactory->get($file_path);
        $resource = $image_reloaded->getToolkit()->getResource();
      }
    }

    // Test creation of image from scratch, and saving to storage.
    foreach ([IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_JPEG] as $type) {
      $image = $this->imageFactory->get();
      $image->createNew(50, 20, image_type_to_extension($type, FALSE), '#ffff00');
      $file = 'from_null' . image_type_to_extension($type);
      $file_path = $directory . '/' . $file;
      $this->assertEqual(50, $image->getWidth(), new FormattableMarkup('Image file %file has the correct width.', ['%file' => $file]));
      $this->assertEqual(20, $image->getHeight(), new FormattableMarkup('Image file %file has the correct height.', ['%file' => $file]));
      $this->assertEqual(image_type_to_mime_type($type), $image->getMimeType(), new FormattableMarkup('Image file %file has the correct MIME type.', ['%file' => $file]));
      $this->assertTrue($image->save($file_path), new FormattableMarkup('Image %file created anew from a null image was saved.', ['%file' => $file]));

      // Reload saved image.
      $image_reloaded = $this->imageFactory->get($file_path);
      if (!$image_reloaded->isValid()) {
        $this->fail(new FormattableMarkup('Could not load image %file.', ['%file' => $file]));
        continue;
      }
      $this->assertEqual(50, $image_reloaded->getWidth(), new FormattableMarkup('Image file %file has the correct width.', ['%file' => $file]));
      $this->assertEqual(20, $image_reloaded->getHeight(), new FormattableMarkup('Image file %file has the correct height.', ['%file' => $file]));
      $this->assertEqual(image_type_to_mime_type($type), $image_reloaded->getMimeType(), new FormattableMarkup('Image file %file has the correct MIME type.', ['%file' => $file]));
      if ($image_reloaded->getToolkit()->getType() == IMAGETYPE_GIF) {
        $this->assertEqual('#ffff00', $image_reloaded->getToolkit()->getTransparentColor(), new FormattableMarkup('Image file %file has the correct transparent color channel set.', ['%file' => $file]));
      }
      else {
        $this->assertEqual(NULL, $image_reloaded->getToolkit()->getTransparentColor(), new FormattableMarkup('Image file %file has no color channel set.', ['%file' => $file]));
      }
    }

    // Test failures of the 'create_new' operation.
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
  public function testGifTransparentImages() {
    // Prepare a directory for test file results.
    $directory = Settings::get('file_public_path') . '/imagetest';
    \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    // Test loading an indexed GIF image with transparent color set.
    // Color at top-right pixel should be fully transparent.
    $file = 'image-test-transparent-indexed.gif';
    $image = $this->imageFactory->get('core/tests/fixtures/files/' . $file);
    $resource = $image->getToolkit()->getResource();
    $color_index = imagecolorat($resource, $image->getWidth() - 1, 0);
    $color = array_values(imagecolorsforindex($resource, $color_index));
    $this->assertEqual($this->rotateTransparent, $color, "Image {$file} after load has full transparent color at corner 1.");

    // Test deliberately creating a GIF image with no transparent color set.
    // Color at top-right pixel should be fully transparent while in memory,
    // fully opaque after flushing image to file.
    $file = 'image-test-no-transparent-color-set.gif';
    $file_path = $directory . '/' . $file;
    // Create image.
    $image = $this->imageFactory->get();
    $image->createNew(50, 20, 'gif', NULL);
    $resource = $image->getToolkit()->getResource();
    $color_index = imagecolorat($resource, $image->getWidth() - 1, 0);
    $color = array_values(imagecolorsforindex($resource, $color_index));
    $this->assertEqual($this->rotateTransparent, $color, "New GIF image with no transparent color set after creation has full transparent color at corner 1.");
    // Save image.
    $this->assertTrue($image->save($file_path), "New GIF image {$file} was saved.");
    // Reload image.
    $image_reloaded = $this->imageFactory->get($file_path);
    $resource = $image_reloaded->getToolkit()->getResource();
    $color_index = imagecolorat($resource, $image_reloaded->getWidth() - 1, 0);
    $color = array_values(imagecolorsforindex($resource, $color_index));
    // Check explicitly for alpha == 0 as the rest of the color has been
    // compressed and may have slight difference from full white.
    $this->assertEqual(0, $color[3], "New GIF image {$file} after reload has no transparent color at corner 1.");

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
    $toolkit = $image->getToolkit();

    if (!$image->isValid()) {
      $this->fail(new FormattableMarkup('Could not load image %file.', ['%file' => $file]));
    }
    else {
      // All images should be converted to truecolor when loaded.
      $image_truecolor = imageistruecolor($toolkit->getResource());
      $this->assertTrue($image_truecolor, new FormattableMarkup('Image %file after load is a truecolor image.', ['%file' => $file]));
    }
  }

  /**
   * Tests calling a missing image operation plugin.
   */
  public function testMissingOperation() {

    // Test that the image factory is set to use the GD toolkit.
    $this->assertEqual($this->imageFactory->getToolkitId(), 'gd', 'The image factory is set to use the \'gd\' image toolkit.');

    // An image file that will be tested.
    $file = 'image-test.png';

    // Load up a fresh image.
    $image = $this->imageFactory->get('core/tests/fixtures/files/' . $file);
    if (!$image->isValid()) {
      $this->fail(new FormattableMarkup('Could not load image %file.', ['%file' => $file]));
    }

    // Try perform a missing toolkit operation.
    $this->assertFalse($image->apply('missing_op', []), 'Calling a missing image toolkit operation plugin fails.');
  }

}
