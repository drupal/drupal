<?php

/**
 * @file
 * Contains \Drupal\Core\Annotation\LocalAction.
 */

namespace Drupal\Core\Annotation\Menu;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a LocalAction type Plugin annotation object.
 *
 * @Annotation
 */
class LocalAction extends Plugin {

  /**
   * The ID.
   *
   * @var string
   */
  public $id;

  /**
   * A static title for the local action.
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
   * An array of route names where this action appears.
   *
   * @var array (optional)
   */
  public $appears_on = array();

}
