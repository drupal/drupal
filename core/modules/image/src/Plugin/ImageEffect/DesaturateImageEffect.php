<?php

namespace Drupal\image\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\image\Attribute\ImageEffect;
use Drupal\image\ImageEffectBase;

/**
 * Desaturates (grayscale) an image resource.
 */
#[ImageEffect(
  id: "image_desaturate",
  label: new TranslatableMarkup("Desaturate"),
  description: new TranslatableMarkup("Desaturate converts an image to grayscale."),
)]
class DesaturateImageEffect extends ImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (!$image->desaturate()) {
      $this->logger->error('Image desaturate failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', ['%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()]);
      return FALSE;
    }
    return TRUE;
  }

}
