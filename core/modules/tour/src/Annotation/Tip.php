<?php

/**
 * @file
 * Contains \Drupal\tour\Annotation\Tip.
 */

namespace Drupal\tour\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Tip annotation object.
 *
 * @Annotation
 */
class Tip extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
