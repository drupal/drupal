<?php

/**
 * @file
 * Contains \Drupal\image_module_test\Plugin\ImageEffect\UriDependentTestImageEffect.
 */

namespace Drupal\image_module_test\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\image\ImageEffectBase;

/**
 * Performs an image operation that depends on the URI of the original image.
 *
 * @ImageEffect(
 *   id = "image_module_test_uri_dependent",
 *   label = @Translation("URI dependent test image effect")
 * )
 */
class UriDependentTestImageEffect extends ImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    $dimensions = $this->getUriDependentDimensions($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    $dimensions = $this->getUriDependentDimensions($image->getSource());
    return $image->resize($dimensions['width'], $dimensions['height']);
  }

  /**
   * Make the image dimensions dependent on the image file extension.
   *
   * @param string $uri
   *   Original image file URI.
   *
   * @return array
   *   Associative array.
   *   - width: Integer with the derivative image width.
   *   - height: Integer with the derivative image height.
   */
  protected function getUriDependentDimensions($uri) {
    $dimensions = [];
    $extension = pathinfo($uri, PATHINFO_EXTENSION);
    switch (strtolower($extension)) {
      case 'png':
        $dimensions['width'] = $dimensions['height'] = 100;
        break;

      case 'gif':
        $dimensions['width'] = $dimensions['height'] = 50;
        break;

      default:
        $dimensions['width'] = $dimensions['height'] = 20;
        break;

    }
    return $dimensions;
  }

}
