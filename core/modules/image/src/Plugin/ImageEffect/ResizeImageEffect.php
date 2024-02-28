<?php

namespace Drupal\image\Plugin\ImageEffect;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\image\Attribute\ImageEffect;
use Drupal\image\ConfigurableImageEffectBase;

/**
 * Resizes an image resource.
 */
#[ImageEffect(
  id: "image_resize",
  label: new TranslatableMarkup("Resize"),
  description: new TranslatableMarkup("Resizing will make images an exact set of dimensions. This may cause images to be stretched or shrunk disproportionately."),
)]
class ResizeImageEffect extends ConfigurableImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (!$image->resize($this->configuration['width'], $this->configuration['height'])) {
      $this->logger->error('Image resize failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', ['%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()]);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    // The new image will have the exact dimensions defined for the effect.
    $dimensions['width'] = $this->configuration['width'];
    $dimensions['height'] = $this->configuration['height'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = [
      '#theme' => 'image_resize_summary',
      '#data' => $this->configuration,
    ];
    $summary += parent::getSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'width' => NULL,
      'height' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $this->configuration['width'],
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $this->configuration['height'],
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['height'] = $form_state->getValue('height');
    $this->configuration['width'] = $form_state->getValue('width');
  }

}
