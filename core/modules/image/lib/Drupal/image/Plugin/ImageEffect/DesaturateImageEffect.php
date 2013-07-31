<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\ImageEffect\DesaturateImageEffect.
 */

namespace Drupal\image\Plugin\ImageEffect;

use Drupal\Core\Annotation\Translation;
use Drupal\image\Annotation\ImageEffect;
use Drupal\image\ImageEffectBase;

/**
 * Desaturates (grayscale) an image resource.
 *
 * @ImageEffect(
 *   id = "image_desaturate",
 *   label = @Translation("Desaturate"),
 *   description = @Translation("Desaturate converts an image to grayscale.")
 * )
 */
class DesaturateImageEffect extends ImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions) {
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect($image) {
    if (!image_desaturate($image)) {
      watchdog('image', 'Image desaturate failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->toolkit->getPluginId(), '%path' => $image->source, '%mimetype' => $image->info['mime_type'], '%dimensions' => $image->info['width'] . 'x' . $image->info['height']), WATCHDOG_ERROR);
      return FALSE;
    }
    return TRUE;
  }

}
