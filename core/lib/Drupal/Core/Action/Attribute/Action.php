<?php

namespace Drupal\Core\Action\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an Action attribute object.
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
#[\Attribute(\Attribute::TARGET_CLASS)]
class Action extends Plugin {

  /**
   * Constructs an Action attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   The label of the action.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $action_label
   *   (optional) A label that can be used by the action deriver.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $category
   *   (optional) The category under which the action should be listed in the
   *   UI.
   * @param string|null $deriver
   *   (optional) The deriver class.
   * @param string|null $confirm_form_route_name
   *   (optional) The route name for a confirmation form for this action.
   * @param string|null $type
   *   (optional) The entity type the action can apply to.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $action_label = NULL,
    public readonly ?TranslatableMarkup $category = NULL,
    public readonly ?string $deriver = NULL,
    public readonly ?string $confirm_form_route_name = NULL,
    public readonly ?string $type = NULL
  ) {}

}
