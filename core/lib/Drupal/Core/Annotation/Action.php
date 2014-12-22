<?php

/**
 * @file
 * Contains \Drupal\Core\Annotation\Action.
 */

namespace Drupal\Core\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Action annotation object.
 *
 * Plugin Namespace: Plugin\Action
 *
 * For a working example, see \Drupal\node\Plugin\Action\UnpublishNode
 *
 * @see \Drupal\Core\Action\ActionInterface
 * @see \Drupal\Core\Action\ActionManager
 * @see \Drupal\Core\Action\ActionBase
 * @see plugin_api
 *
 * @Annotation
 */
class Action extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the action plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The route name for a confirmation form for this action.
   *
   * @todo Provide a more generic way to allow an action to be confirmed first.
   *
   * @var string (optional)
   */
  public $confirm_form_route_name = '';

  /**
   * The entity type the action can apply to.
   *
   * @todo Replace with \Drupal\Core\Plugin\Context\Context.
   *
   * @var string
   */
  public $type = '';

  /**
   * The category under which the action should be listed in the UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

}
