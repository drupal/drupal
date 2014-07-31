<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\ImageToolkit\Operation\gd\Rotate.
 */

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

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
        'description' => 'An hexadecimal integer specifying the background color to use for the uncovered area of the image after the rotation. E.g. 0x000000 for black, 0xff00ff for magenta, and 0xffffff for white. For images that support transparency, this will default to transparent. Otherwise it will be white',
        'required' => FALSE,
        'default' => NULL,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    // PHP installations using non-bundled GD do not have imagerotate.
    if (!function_exists('imagerotate')) {
      $this->logger->notice('The image %file could not be rotated because the imagerotate() function is not available in this PHP installation.', array('%file' => $this->getToolkit()->getImage()->getSource()));
      return FALSE;
    }

    // Convert the hexadecimal background value to a color index value.
    if (!empty($arguments['background'])) {
      $rgb = array();
      for ($i = 16; $i >= 0; $i -= 8) {
        $rgb[] = (($arguments['background'] >> $i) & 0xFF);
      }
      $arguments['background'] = imagecolorallocatealpha($this->getToolkit()->getResource(), $rgb[0], $rgb[1], $rgb[2], 0);
    }
    // Set background color as transparent if $arguments['background'] is NULL.
    else {
      // Get the current transparent color.
      $arguments['background'] = imagecolortransparent($this->getToolkit()->getResource());

      // If no transparent colors, use white.
      if ($arguments['background'] == 0) {
        $arguments['background'] = imagecolorallocatealpha($this->getToolkit()->getResource(), 255, 255, 255, 0);
      }
    }

    // Images are assigned a new color palette when rotating, removing any
    // transparency flags. For GIF images, keep a record of the transparent color.
    if ($this->getToolkit()->getType() == IMAGETYPE_GIF) {
      $transparent_index = imagecolortransparent($this->getToolkit()->getResource());
      if ($transparent_index != 0) {
        $transparent_gif_color = imagecolorsforindex($this->getToolkit()->getResource(), $transparent_index);
      }
    }

    $this->getToolkit()->setResource(imagerotate($this->getToolkit()->getResource(), 360 - $arguments['degrees'], $arguments['background']));

    // GIFs need to reassign the transparent color after performing the rotate.
    if (isset($transparent_gif_color)) {
      $arguments['background'] = imagecolorexactalpha($this->getToolkit()->getResource(), $transparent_gif_color['red'], $transparent_gif_color['green'], $transparent_gif_color['blue'], $transparent_gif_color['alpha']);
      imagecolortransparent($this->getToolkit()->getResource(), $arguments['background']);
    }

    return TRUE;
  }

}
