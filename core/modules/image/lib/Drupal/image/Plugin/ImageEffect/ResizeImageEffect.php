<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\ImageEffect\ResizeImageEffect.
 */

namespace Drupal\image\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\image\ConfigurableImageEffectInterface;
use Drupal\image\ImageEffectBase;

/**
 * Resizes an image resource.
 *
 * @ImageEffect(
 *   id = "image_resize",
 *   label = @Translation("Resize"),
 *   description = @Translation("Resizing will make images an exact set of dimensions. This may cause images to be stretched or shrunk disproportionately.")
 * )
 */
class ResizeImageEffect extends ImageEffectBase implements ConfigurableImageEffectInterface {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (!$image->resize($this->configuration['width'], $this->configuration['height'])) {
      watchdog('image', 'Image resize failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()), WATCHDOG_ERROR);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions) {
    // The new image will have the exact dimensions defined for the effect.
    $dimensions['width'] = $this->configuration['width'];
    $dimensions['height'] = $this->configuration['height'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return array(
      '#theme' => 'image_resize_summary',
      '#data' => $this->configuration,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'width' => NULL,
      'height' => NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getForm() {
    $form['width'] = array(
      '#type' => 'number',
      '#title' => t('Width'),
      '#default_value' => $this->configuration['width'],
      '#field_suffix' => ' ' . t('pixels'),
      '#required' => TRUE,
      '#min' => 1,
    );
    $form['height'] = array(
      '#type' => 'number',
      '#title' => t('Height'),
      '#default_value' => $this->configuration['height'],
      '#field_suffix' => ' ' . t('pixels'),
      '#required' => TRUE,
      '#min' => 1,
    );
    return $form;
  }

}
