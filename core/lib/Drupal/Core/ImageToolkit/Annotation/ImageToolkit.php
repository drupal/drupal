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
 * @Annotation
 *
 * @see \Drupal\Core\ImageToolkit\ImageToolkitInterface
 * @see \Drupal\Core\ImageToolkit\ImageToolkitManager
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
