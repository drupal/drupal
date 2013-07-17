<?php

/**
 * @file
 * Contains \Drupal\Core\Annotation\Menu\LocalTask.
 */

namespace Drupal\Core\Annotation\Menu;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a local task plugin annotation object.
 *
 * @Annotation
 */
class LocalTask extends Plugin {

  /**
   * The ID.
   *
   * @var string
   */
  public $id;

  /**
   * The static title for the local task.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * The route name.
   *
   * @var string
   */
  public $route_name;

  /**
   * The plugin ID of the root tab.
   *
   * @var array
   */
  public $tab_root_id;

  /**
   * The plugin ID of the parent tab (or NULL for a top-level tab).
   *
   * @var array|NULL
   */
  public $tab_parent_id;

  /**
   * The weight of the tab.
   *
   * @var int|NULL
   */
  public $weight;

  /**
   * The default link options.
   *
   * @var array (optional)
   */
  public $options = array();

}
