<?php
/**
 * @file
 * Contains \Drupal\Core\Condition\Annotation\Condition.
 */

namespace Drupal\Core\Condition\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a condition annotation object.
 *
 * @Annotation
 */
class Condition extends Plugin {

  /**
   * The condition plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the condition.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The name of the module providing the type.
   *
   * @var string
   */
  public $module;

  /**
   * An array of contextual data.
   *
   * @var array
   */
  public $condition = array();

}
