<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\ImageEffect\ScaleAndCropImageEffect.
 */

namespace Drupal\image\Plugin\ImageEffect;

use Drupal\Core\Annotation\Translation;
use Drupal\image\Annotation\ImageEffect;

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
  public function applyEffect($image) {
    if (!image_scale_and_crop($image, $this->configuration['width'], $this->configuration['height'])) {
      watchdog('image', 'Image scale and crop failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->toolkit->getPluginId(), '%path' => $image->source, '%mimetype' => $image->info['mime_type'], '%dimensions' => $image->info['width'] . 'x' . $image->info['height']), WATCHDOG_ERROR);
      return FALSE;
    }
    return TRUE;
  }

}
