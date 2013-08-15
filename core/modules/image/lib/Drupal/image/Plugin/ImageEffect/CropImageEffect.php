<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\ImageEffect\CropImageEffect.
 */

namespace Drupal\image\Plugin\ImageEffect;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\Annotation\ImageEffect;

/**
 * Crops an image resource.
 *
 * @ImageEffect(
 *   id = "image_crop",
 *   label = @Translation("Crop"),
 *   description = @Translation("Resizing will make images an exact set of dimensions. This may cause images to be stretched or shrunk disproportionately.")
 * )
 */
class CropImageEffect extends ResizeImageEffect {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    // Set sane default values.
    $this->configuration += array(
      'anchor' => 'center-center',
    );

    list($x, $y) = explode('-', $this->configuration['anchor']);
    $x = image_filter_keyword($x, $image->getWidth(), $this->configuration['width']);
    $y = image_filter_keyword($y, $image->getHeight(), $this->configuration['height']);
    if (!$image->crop($x, $y, $this->configuration['width'], $this->configuration['height'])) {
      watchdog('image', 'Image crop failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()), WATCHDOG_ERROR);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return array(
      '#theme' => 'image_crop_summary',
      '#data' => $this->configuration,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getForm() {
    $this->configuration += array(
      'width' => '',
      'height' => '',
      'anchor' => 'center-center',
    );
    $form = parent::getForm();
    $form['anchor'] = array(
      '#type' => 'radios',
      '#title' => t('Anchor'),
      '#options' => array(
        'left-top' => t('Top') . ' ' . t('Left'),
        'center-top' => t('Top') . ' ' . t('Center'),
        'right-top' => t('Top') . ' ' . t('Right'),
        'left-center' => t('Center') . ' ' . t('Left'),
        'center-center' => t('Center'),
        'right-center' => t('Center') . ' ' . t('Right'),
        'left-bottom' => t('Bottom') . ' ' . t('Left'),
        'center-bottom' => t('Bottom') . ' ' . t('Center'),
        'right-bottom' => t('Bottom') . ' ' . t('Right'),
      ),
      '#theme' => 'image_anchor',
      '#default_value' => $this->configuration['anchor'],
      '#description' => t('The part of the image that will be retained during the crop.'),
    );
    return $form;
  }

}
