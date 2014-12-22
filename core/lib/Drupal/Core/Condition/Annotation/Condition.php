<?php
/**
 * @file
 * Contains \Drupal\Core\Condition\Annotation\Condition.
 */

namespace Drupal\Core\Condition\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a condition plugin annotation object.
 *
 * Condition plugins provide generalized conditions for use in other
 * operations, such as conditional block placement.
 *
 * Plugin Namespace: Plugin\Condition
 *
 * For a working example, see \Drupal\user\Plugin\Condition\UserRole.
 *
 * @see \Drupal\Core\Condition\ConditionManager
 * @see \Drupal\Core\Condition\ConditionInterface
 * @see \Drupal\Core\Condition\ConditionPluginBase
 * @see block_api
 *
 * @ingroup plugin_api
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

  /**
   * The category under which the condition should listed in the UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

}
