<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\ImageToolkit\Operation\gd\Rotate.
 */

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

use Drupal\Component\Utility\Color;

/**
 * Defines GD2 rotate operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_rotate",
 *   toolkit = "gd",
 *   operation = "rotate",
 *   label = @Translation("Rotate"),
 *   description = @Translation("Rotates an image by the given number of degrees.")
 * )
 */
class Rotate extends GDImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return array(
      'degrees' => array(
        'description' => 'The number of (clockwise) degrees to rotate the image',
      ),
      'background' => array(
        'description' => "A string specifying the hexadecimal color code to use as background for the uncovered area of the image after the rotation. E.g. '#000000' for black, '#ff00ff' for magenta, and '#ffffff' for white. For images that support transparency, this will default to transparent white",
        'required' => FALSE,
        'default' => NULL,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    // PHP 5.5 GD bug: https://bugs.php.net/bug.php?id=65148: To prevent buggy
    // behavior on negative multiples of 90 degrees we convert any negative
    // angle to a positive one between 0 and 360 degrees.
    $arguments['degrees'] -= floor($arguments['degrees'] / 360) * 360;

    // Validate or set background color argument.
    if (!empty($arguments['background'])) {
      // Validate the background color: Color::hexToRgb does so for us.
      $background = Color::hexToRgb($arguments['background']) + array( 'alpha' => 0 );
    }
    else {
      // Background color is not specified: use transparent white as background.
      $background = array('red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 127);
    }
    // Store the color index for the background as that is what GD uses.
    $arguments['background_idx'] = imagecolorallocatealpha($this->getToolkit()->getResource(), $background['red'], $background['green'], $background['blue'], $background['alpha']);

    if ($this->getToolkit()->getType() === IMAGETYPE_GIF) {
      // GIF does not work with a transparency channel, but can define 1 color
      // in its palette to act as transparent.

      // Get the current transparent color, if any.
      $gif_transparent_id = imagecolortransparent($this->getToolkit()->getResource());
      if ($gif_transparent_id !== -1) {
        // The gif already has a transparent color set: remember it to set it on
        // the rotated image as well.
        $arguments['gif_transparent_color'] = imagecolorsforindex($this->getToolkit()->getResource(), $gif_transparent_id);

        if ($background['alpha'] >= 127) {
          // We want a transparent background: use the color already set to act
          // as transparent, as background.
          $arguments['background_idx'] = $gif_transparent_id;
        }
      }
      else {
        // The gif does not currently have a transparent color set.
        if ($background['alpha'] >= 127) {
          // But as the background is transparent, it should get one.
          $arguments['gif_transparent_color'] = $background;
        }
      }
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    // PHP installations using non-bundled GD do not have imagerotate.
    if (!function_exists('imagerotate')) {
      $this->logger->notice('The image %file could not be rotated because the imagerotate() function is not available in this PHP installation.', array('%file' => $this->getToolkit()->getSource()));
      return FALSE;
    }

    $this->getToolkit()->setResource(imagerotate($this->getToolkit()->getResource(), 360 - $arguments['degrees'], $arguments['background_idx']));

    // GIFs need to reassign the transparent color after performing the rotate,
    // but only do so, if the image already had transparency of its own, or the
    // rotate added a transparent background.
    if (!empty($arguments['gif_transparent_color'])) {
      $transparent_idx = imagecolorexactalpha($this->getToolkit()->getResource(), $arguments['gif_transparent_color']['red'], $arguments['gif_transparent_color']['green'], $arguments['gif_transparent_color']['blue'], $arguments['gif_transparent_color']['alpha']);
      imagecolortransparent($this->getToolkit()->getResource(), $transparent_idx);
    }

    return TRUE;
  }

}
