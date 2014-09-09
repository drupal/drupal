<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Image\ToolkitGdTest.
 */

namespace Drupal\system\Tests\Image;

use Drupal\Core\Image\ImageInterface;
use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Component\Utility\String;

/**
 * Tests that core image manipulations work properly: scale, resize, rotate,
 * crop, scale and crop, and desaturate.
 *
 * @group Image
 */
class ToolkitGdTest extends DrupalUnitTestBase {

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  // Colors that are used in testing.
  protected $black       = array(0, 0, 0, 0);
  protected $red         = array(255, 0, 0, 0);
  protected $green       = array(0, 255, 0, 0);
  protected $blue        = array(0, 0, 255, 0);
  protected $yellow      = array(255, 255, 0, 0);
  protected $fuchsia     = array(255, 0, 255, 0); // Used as background colors.
  protected $transparent = array(0, 0, 0, 127);
  protected $white       = array(255, 255, 255, 0);

  protected $width = 40;
  protected $height = 20;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'simpletest');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set the image factory service.
    $this->imageFactory = $this->container->get('image.factory');
  }

  protected function checkRequirements() {
    // GD2 support is available.
    if (!function_exists('imagegd2')) {
      return array(
        'Image manipulations for the GD toolkit cannot run because the GD toolkit is not available.',
      );
    }
    return parent::checkRequirements();
  }

  /**
   * Function to compare two colors by RGBa.
   */
  function colorsAreEqual($color_a, $color_b) {
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
  function getPixelColor(ImageInterface $image, $x, $y) {
    $toolkit = $image->getToolkit();
    $color_index = imagecolorat($toolkit->getResource(), $x, $y);

    $transparent_index = imagecolortransparent($toolkit->getResource());
    if ($color_index == $transparent_index) {
      return array(0, 0, 0, 127);
    }

    return array_values(imagecolorsforindex($toolkit->getResource(), $color_index));
  }

  /**
   * Since PHP can't visually check that our images have been manipulated
   * properly, build a list of expected color values for each of the corners and
   * the expected height and widths for the final images.
   */
  function testManipulations() {

    // Test that the image factory is set to use the GD toolkit.
    $this->assertEqual($this->imageFactory->getToolkitId(), 'gd', 'The image factory is set to use the \'gd\' image toolkit.');

    // Typically the corner colors will be unchanged. These colors are in the
    // order of top-left, top-right, bottom-right, bottom-left.
    $default_corners = array($this->red, $this->green, $this->blue, $this->transparent);

    // A list of files that will be tested.
    $files = array(
      'image-test.png',
      'image-test.gif',
      'image-test.jpg',
    );

    // Setup a list of tests to perform on each type.
    $operations = array(
      'resize' => array(
        'function' => 'resize',
        'arguments' => array('width' => 20, 'height' => 10),
        'width' => 20,
        'height' => 10,
        'corners' => $default_corners,
      ),
      'scale_x' => array(
        'function' => 'scale',
        'arguments' => array('width' => 20),
        'width' => 20,
        'height' => 10,
        'corners' => $default_corners,
      ),
      'scale_y' => array(
        'function' => 'scale',
        'arguments' => array('height' => 10),
        'width' => 20,
        'height' => 10,
        'corners' => $default_corners,
      ),
      'upscale_x' => array(
        'function' => 'scale',
        'arguments' => array('width' => 80, 'upscale' => TRUE),
        'width' => 80,
        'height' => 40,
        'corners' => $default_corners,
      ),
      'upscale_y' => array(
        'function' => 'scale',
        'arguments' => array('height' => 40, 'upscale' => TRUE),
        'width' => 80,
        'height' => 40,
        'corners' => $default_corners,
      ),
      'crop' => array(
        'function' => 'crop',
        'arguments' => array('x' => 12, 'y' => 4, 'width' => 16, 'height' => 12),
        'width' => 16,
        'height' => 12,
        'corners' => array_fill(0, 4, $this->white),
      ),
      'scale_and_crop' => array(
        'function' => 'scale_and_crop',
        'arguments' => array('width' => 10, 'height' => 8),
        'width' => 10,
        'height' => 8,
        'corners' => array_fill(0, 4, $this->black),
      ),
    );

    // Systems using non-bundled GD2 don't have imagerotate. Test if available.
    if (function_exists('imagerotate')) {
      $operations += array(
        'rotate_5' => array(
          'function' => 'rotate',
          'arguments' => array('degrees' => 5, 'background' => 0xFF00FF), // Fuchsia background.
          'width' => 42,
          'height' => 24,
          'corners' => array_fill(0, 4, $this->fuchsia),
        ),
        'rotate_90' => array(
          'function' => 'rotate',
          'arguments' => array('degrees' => 90, 'background' => 0xFF00FF), // Fuchsia background.
          'width' => 20,
          'height' => 40,
          'corners' => array($this->transparent, $this->red, $this->green, $this->blue),
        ),
        'rotate_transparent_5' => array(
          'function' => 'rotate',
          'arguments' => array('degrees' => 5),
          'width' => 42,
          'height' => 24,
          'corners' => array_fill(0, 4, $this->transparent),
        ),
        'rotate_transparent_90' => array(
          'function' => 'rotate',
          'arguments' => array('degrees' => 90),
          'width' => 20,
          'height' => 40,
          'corners' => array($this->transparent, $this->red, $this->green, $this->blue),
        ),
      );
    }

    // Systems using non-bundled GD2 don't have imagefilter. Test if available.
    if (function_exists('imagefilter')) {
      $operations += array(
        'desaturate' => array(
          'function' => 'desaturate',
          'arguments' => array(),
          'height' => 20,
          'width' => 40,
          // Grayscale corners are a bit funky. Each of the corners are a shade of
          // gray. The values of these were determined simply by looking at the
          // final image to see what desaturated colors end up being.
          'corners' => array(
            array_fill(0, 3, 76) + array(3 => 0),
            array_fill(0, 3, 149) + array(3 => 0),
            array_fill(0, 3, 29) + array(3 => 0),
            array_fill(0, 3, 225) + array(3 => 127)
          ),
        ),
      );
    }

    foreach ($files as $file) {
      foreach ($operations as $op => $values) {
        // Load up a fresh image.
        $image = $this->imageFactory->get(drupal_get_path('module', 'simpletest') . '/files/' . $file);
        $toolkit = $image->getToolkit();
        if (!$image->isValid()) {
          $this->fail(String::format('Could not load image %file.', array('%file' => $file)));
          continue 2;
        }

        // All images should be converted to truecolor when loaded.
        $image_truecolor = imageistruecolor($toolkit->getResource());
        $this->assertTrue($image_truecolor, String::format('Image %file after load is a truecolor image.', array('%file' => $file)));

        if ($image->getToolkit()->getType() == IMAGETYPE_GIF) {
          if ($op == 'desaturate') {
            // Transparent GIFs and the imagefilter function don't work together.
            $values['corners'][3][3] = 0;
          }
        }

        // Perform our operation.
        $image->apply($values['function'], $values['arguments']);

        // To keep from flooding the test with assert values, make a general
        // value for whether each group of values fail.
        $correct_dimensions_real = TRUE;
        $correct_dimensions_object = TRUE;

        // Check the real dimensions of the image first.
        if (imagesy($toolkit->getResource()) != $values['height'] || imagesx($toolkit->getResource()) != $values['width']) {
          $correct_dimensions_real = FALSE;
        }

        // Check that the image object has an accurate record of the dimensions.
        if ($image->getWidth() != $values['width'] || $image->getHeight() != $values['height']) {
          $correct_dimensions_object = FALSE;
        }

        $directory = $this->public_files_directory .'/imagetest';
        file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
        $file_path = $directory . '/' . $op . image_type_to_extension($image->getToolkit()->getType());
        $image->save($file_path);

        $this->assertTrue($correct_dimensions_real, String::format('Image %file after %action action has proper dimensions.', array('%file' => $file, '%action' => $op)));
        $this->assertTrue($correct_dimensions_object, String::format('Image %file object after %action action is reporting the proper height and width values.', array('%file' => $file, '%action' => $op)));

        // JPEG colors will always be messed up due to compression.
        if ($image->getToolkit()->getType() != IMAGETYPE_JPEG) {
          // Now check each of the corners to ensure color correctness.
          foreach ($values['corners'] as $key => $corner) {
            // Get the location of the corner.
            switch ($key) {
              case 0:
                $x = 0;
                $y = 0;
                break;
              case 1:
                $x = $values['width'] - 1;
                $y = 0;
                break;
              case 2:
                $x = $values['width'] - 1;
                $y = $values['height'] - 1;
                break;
              case 3:
                $x = 0;
                $y = $values['height'] - 1;
                break;
            }
            $color = $this->getPixelColor($image, $x, $y);
            $correct_colors = $this->colorsAreEqual($color, $corner);
            $this->assertTrue($correct_colors, String::format('Image %file object after %action action has the correct color placement at corner %corner.', array('%file' => $file, '%action' => $op, '%corner' => $key)));
          }
        }

        // Check that saved image reloads without raising PHP errors.
        $image_reloaded = $this->imageFactory->get($file_path);
        $resource = $image_reloaded->getToolkit()->getResource();
      }
    }
  }

  /**
   * Tests loading an image whose transparent color index is out of range.
   */
  function testTransparentColorOutOfRange() {
    // This image was generated by taking an initial image with a palette size
    // of 6 colors, and setting the transparent color index to 6 (one higher
    // than the largest allowed index), as follows:
    // @code
    // $image = imagecreatefromgif('core/modules/simpletest/files/image-test.gif');
    // imagecolortransparent($image, 6);
    // imagegif($image, 'core/modules/simpletest/files/image-test-transparent-out-of-range.gif');
    // @endcode
    // This allows us to test that an image with an out-of-range color index
    // can be loaded correctly.
    $file = 'image-test-transparent-out-of-range.gif';
    $image = $this->imageFactory->get(drupal_get_path('module', 'simpletest') . '/files/' . $file);
    $toolkit = $image->getToolkit();

    if (!$image->isValid()) {
      $this->fail(String::format('Could not load image %file.', array('%file' => $file)));
    }
    else {
      // All images should be converted to truecolor when loaded.
      $image_truecolor = imageistruecolor($toolkit->getResource());
      $this->assertTrue($image_truecolor, String::format('Image %file after load is a truecolor image.', array('%file' => $file)));
    }
  }

  /**
   * Tests calling a missing image operation plugin.
   */
  function testMissingOperation() {

    // Test that the image factory is set to use the GD toolkit.
    $this->assertEqual($this->imageFactory->getToolkitId(), 'gd', 'The image factory is set to use the \'gd\' image toolkit.');

    // An image file that will be tested.
    $file = 'image-test.png';

    // Load up a fresh image.
    $image = $this->imageFactory->get(drupal_get_path('module', 'simpletest') . '/files/' . $file);
    if (!$image->isValid()) {
      $this->fail(String::format('Could not load image %file.', array('%file' => $file)));
    }

    // Try perform a missing toolkit operation.
    $this->assertFalse($image->apply('missing_op', array()), 'Calling a missing image toolkit operation plugin fails.');
  }

}
