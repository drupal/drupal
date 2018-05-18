<?php

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
class ScaleAndCropImageEffect extends CropImageEffect {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    $width = $this->configuration['width'];
    $height = $this->configuration['height'];
    $scale = max($width / $image->getWidth(), $height / $image->getHeight());

    list($x, $y) = explode('-', $this->configuration['anchor']);
    $x = image_filter_keyword($x, $image->getWidth() * $scale, $width);
    $y = image_filter_keyword($y, $image->getHeight() * $scale, $height);

    if (!$image->apply('scale_and_crop', ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height])) {
      $this->logger->error('Image scale and crop failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', ['%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()]);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = [
      '#theme' => 'image_scale_and_crop_summary',
      '#data' => $this->configuration,
    ];
    $summary += parent::getSummary();

    return $summary;
  }

}
