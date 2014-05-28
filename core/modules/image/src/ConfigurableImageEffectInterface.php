<?php

/**
 * @file
 * Contains \Drupal\image\ConfigurableImageEffectInterface.
 */

namespace Drupal\image;

/**
 * Defines the interface for configurable image effects.
 */
interface ConfigurableImageEffectInterface extends ImageEffectInterface {

  /**
   * Builds the part of the image effect form specific to this image effect.
   *
   * This method is only responsible for the form elements specific to this
   * image effect. All other aspects of the form are handled by calling code.
   *
   * @return array
   *   A render array.
   */
  public function getForm();

}
