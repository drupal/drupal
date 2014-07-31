<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\ImageEffect\ScaleImageEffect.
 */

namespace Drupal\image\Plugin\ImageEffect;

use Drupal\Component\Utility\Image;
use Drupal\Core\Form\FormStateInterface;
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
      $this->logger->error('Image scale failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()));
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
    $summary = array(
      '#theme' => 'image_scale_summary',
      '#data' => $this->configuration,
    );
    $summary += parent::getSummary();

    return $summary;
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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
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
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    if (empty($form_state['values']['width']) && empty($form_state['values']['height'])) {
      form_set_error('data', $form_state, $this->t('Width and height can not both be blank.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['upscale'] = $form_state['values']['upscale'];
  }

}
