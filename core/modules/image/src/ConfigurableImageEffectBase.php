<?php

/**
 * @file
 * Contains \Drupal\image\ConfigurableImageEffectBase.
 */

namespace Drupal\image;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base class for configurable image effects.
 *
 * @see \Drupal\image\Annotation\ImageEffect
 * @see \Drupal\image\ConfigurableImageEffectInterface
 * @see \Drupal\image\ImageEffectInterface
 * @see \Drupal\image\ImageEffectBase
 * @see \Drupal\image\ImageEffectManager
 * @see plugin_api
 */
abstract class ConfigurableImageEffectBase extends ImageEffectBase implements ConfigurableImageEffectInterface {

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
