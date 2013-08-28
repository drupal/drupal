<?php

/**
 * @file
 * Contains \Drupal\system\Annotation\ImageToolkit.
 */

namespace Drupal\system\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for the image toolkit plugin.
 *
 * @Annotation
 *
 * @see \Drupal\system\Plugin\ImageToolkitInterface
 * @see \Drupal\system\Plugin\ImageToolkitManager
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
