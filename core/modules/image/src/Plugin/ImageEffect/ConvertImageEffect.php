<?php

namespace Drupal\image\Plugin\ImageEffect;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\ConfigurableImageEffectBase;

/**
 * Converts an image resource.
 *
 * @ImageEffect(
 *   id = "image_convert",
 *   label = @Translation("Convert"),
 *   description = @Translation("Converts an image between extensions (e.g. from PNG to JPEG).")
 * )
 */
class ConvertImageEffect extends ConfigurableImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (!$image->convert($this->configuration['extension'])) {
      $this->logger->error('Image convert failed using the %toolkit toolkit on %path (%mimetype)', ['%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType()]);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeExtension($extension) {
    return $this->configuration['extension'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = [
      '#markup' => Unicode::strtoupper($this->configuration['extension']),
    ];
    $summary += parent::getSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'extension' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $extensions = \Drupal::service('image.toolkit.manager')->getDefaultToolkit()->getSupportedExtensions();
    $options = array_combine(
      $extensions,
      array_map(['\Drupal\Component\Utility\Unicode', 'strtoupper'], $extensions)
    );
    $form['extension'] = [
      '#type' => 'select',
      '#title' => t('Extension'),
      '#default_value' => $this->configuration['extension'],
      '#required' => TRUE,
      '#options' => $options,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['extension'] = $form_state->getValue('extension');
  }

}
