<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\ImageEffect\ScaleAndCropImageEffect.
 */

namespace Drupal\image\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;

/**
 * Scales and crops an image resource.
 *
 * @ImageEffect(
 *   id = "image_scale_and_crop",
 *   label = @Translation("Scale and crop"),
 *   description = @Translation("Scale and crop will maintain the aspect-ratio of the original image, then crop the larger dimension. This is most useful for creating perfectly square thumbnails without stretching the image.")
 * )
 */
class ScaleAndCropImageEffect extends ResizeImageEffect {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (!$image->scaleAndCrop($this->configuration['width'], $this->configuration['height'])) {
      $this->logger->error('Image scale and crop failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()));
      return FALSE;
    }
    return TRUE;
  }

}
