<?php

/**
 * @file
 * Contains \Drupal\Core\ImageToolkit\Annotation\ImageToolkit.
 */

namespace Drupal\Core\ImageToolkit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for the image toolkit plugin.
 *
 * An image toolkit provides common image file manipulations like scaling,
 * cropping, and rotating.
 *
 * Plugin namespace: Plugin\ImageToolkit
 *
 * For a working example, see
 * \Drupal\system\Plugin\ImageToolkit\GDToolkit
 *
 * @see \Drupal\Core\ImageToolkit\Annotation\ImageToolkitOperation
 * @see \Drupal\Core\ImageToolkit\ImageToolkitInterface
 * @see \Drupal\Core\ImageToolkit\ImageToolkitBase
 * @see \Drupal\Core\ImageToolkit\ImageToolkitManager
 * @see plugin_api
 *
 * @Annotation
 */
class ImageToolkit extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the image toolkit.
   *
   * The string should be wrapped in a @Translation().
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

}
