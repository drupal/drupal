<?php

/**
 * @file
 * Contains \Drupal\rest\Annotation\RestResource.
 */

namespace Drupal\rest\Annotation;

use \Drupal\Component\Annotation\Plugin;

/**
 * Defines a REST resource annotation object.
 *
 * @Annotation
 */
class RestResource extends Plugin {

  /**
   * The resource plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the resource plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
