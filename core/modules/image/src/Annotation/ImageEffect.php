<?php

/**
 * @file
 * Contains \Drupal\image\Annotation\ImageEffect.
 */

namespace Drupal\image\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an image effect annotation object.
 *
 * Plugin Namespace: Plugin\ImageEffect
 *
 * For a working example, see
 * \Drupal\image\Plugin\ImageEffect\ResizeImageEffect
 *
 * @see hook_image_effect_info_alter()
 * @see \Drupal\image\ConfigurableImageEffectInterface
 * @see \Drupal\image\ConfigurableImageEffectBase
 * @see \Drupal\image\ImageEffectInterface
 * @see \Drupal\image\ImageEffectBase
 * @see \Drupal\image\ImageEffectManager
 * @see \Drupal\Core\ImageToolkit\Annotation\ImageToolkitOperation
 * @see plugin_api
 *
 * @Annotation
 */
class ImageEffect extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the image effect.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the image effect.
   *
   * This will be shown when adding or configuring this image effect.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

}
