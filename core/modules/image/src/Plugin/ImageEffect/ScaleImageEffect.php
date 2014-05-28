<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\ImageEffect\ScaleImageEffect.
 */

namespace Drupal\image\Plugin\ImageEffect;

use Drupal\Component\Utility\Image;
use Drupal\Core\Image\ImageInterface;

/**
 * Scales an image resource.
 *
 * @ImageEffect(
 *   id = "image_scale",
 *   label = @Translation("Scale"),
 *   description = @Translation("Scaling will maintain the aspect-ratio of the original image. If only a single dimension is specified, the other dimension will be calculated.")
 * )
 */
class ScaleImageEffect extends ResizeImageEffect {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (!$image->scale($this->configuration['width'], $this->configuration['height'], $this->configuration['upscale'])) {
      watchdog('image', 'Image scale failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()), WATCHDOG_ERROR);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions) {
    if ($dimensions['width'] && $dimensions['height']) {
      Image::scaleDimensions($dimensions, $this->configuration['width'], $this->configuration['height'], $this->configuration['upscale']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return array(
      '#theme' => 'image_scale_summary',
      '#data' => $this->configuration,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + array(
      'upscale' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getForm() {
    $form = parent::getForm();
    $form['#element_validate'] = array(array($this, 'validateScaleEffect'));
    $form['width']['#required'] = FALSE;
    $form['height']['#required'] = FALSE;
    $form['upscale'] = array(
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['upscale'],
      '#title' => t('Allow Upscaling'),
      '#description' => t('Let scale make images larger than their original size'),
    );
    return $form;
  }

  /**
   * Validates to ensure that either a height or a width is specified.
   */
  public function validateScaleEffect(array $element, array &$form_state) {
    if (empty($element['width']['#value']) && empty($element['height']['#value'])) {
      form_error($element, $form_state, t('Width and height can not both be blank.'));
    }
  }

}
