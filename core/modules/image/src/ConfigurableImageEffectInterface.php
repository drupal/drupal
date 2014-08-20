<?php

/**
 * @file
 * Contains \Drupal\image\ConfigurableImageEffectInterface.
 */

namespace Drupal\image;

use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for configurable image effects.
 *
 * @see \Drupal\image\Annotation\ImageEffect
 * @see \Drupal\image\ConfigurableImageEffectBase
 * @see \Drupal\image\ImageEffectInterface
 * @see \Drupal\image\ImageEffectBase
 * @see \Drupal\image\ImageEffectManager
 * @see plugin_api
 */
interface ConfigurableImageEffectInterface extends ImageEffectInterface, PluginFormInterface {
}
