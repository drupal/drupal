<?php

/**
 * @file
 * Contains \Drupal\image\ConfigurableImageEffectBase.
 */

namespace Drupal\image;

/**
 * Provides a base class for configurable image effects.
 */
abstract class ConfigurableImageEffectBase extends ImageEffectBase implements ConfigurableImageEffectInterface {

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
  }

}
