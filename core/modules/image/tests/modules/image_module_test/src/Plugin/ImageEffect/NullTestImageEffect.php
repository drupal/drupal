<?php

namespace Drupal\image_module_test\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\image\Attribute\ImageEffect;
use Drupal\image\ImageEffectBase;

/**
 * Performs no operation on an image resource.
 */
#[ImageEffect(
  id: "image_module_test_null",
  label: new TranslatableMarkup("Image module test")
)]
class NullTestImageEffect extends ImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    // Unset image dimensions.
    $dimensions['width'] = $dimensions['height'] = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    return TRUE;
  }

}
