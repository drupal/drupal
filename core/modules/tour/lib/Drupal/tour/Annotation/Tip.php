<?php

/**
 * @file
 * Contains \Drupal\tour\Annotation\Tip.
 */

namespace Drupal\tour\Annotation;

use Drupal\Component\Annotation\PluginID;

/**
 * Defines a Tip annotation object.
 *
 * @Annotation
 */
class Tip extends PluginID {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

}
