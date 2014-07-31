<?php

/**
 * @file
 * Contains \Drupal\image\ConfigurableImageEffectBase.
 */

namespace Drupal\image;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base class for configurable image effects.
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
