<?php

namespace Drupal\Core\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Action annotation object.
 *
 * Plugin Namespace: Plugin\Action
 *
 * @see \Drupal\Core\Action\ActionInterface
 * @see \Drupal\Core\Action\ActionManager
 * @see \Drupal\Core\Action\ActionBase
 * @see \Drupal\Core\Action\Plugin\Action\UnpublishAction
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
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The route name for a confirmation form for this action.
   *
   * This property is optional and it does not need to be declared.
   *
   * @var string
   *
   * @todo Provide a more generic way to allow an action to be confirmed first.
   */
  public $confirm_form_route_name = '';

  /**
   * The entity type the action can apply to.
   *
   * @var string
   *
   * @todo Replace with \Drupal\Core\Plugin\Context\Context.
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
