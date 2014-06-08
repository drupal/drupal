<?php

/**
 * @file
 * Contains \Drupal\image\ConfigurableImageEffectInterface.
 */

namespace Drupal\image;

use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for configurable image effects.
 */
interface ConfigurableImageEffectInterface extends ImageEffectInterface, PluginFormInterface {
}
