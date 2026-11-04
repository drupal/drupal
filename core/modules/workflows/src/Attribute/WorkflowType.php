<?php

namespace Drupal\workflows\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Workflow type attribute object.
 *
 * Plugin Namespace: Plugin\WorkflowType
 *
 * For a working example, see
 * \Drupal\content_moderation\Plugin\Workflow\ContentModerate
 *
 * @see \Drupal\workflows\WorkflowTypeInterface
 * @see \Drupal\workflows\WorkflowTypeManager
 * @see workflow_type_info_alter()
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class WorkflowType extends Plugin {

  /**
   * Constructs an Action attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   The label of the action.
   * @param string[] $forms
   *   A list of optional form classes implementing PluginFormInterface.
   * @param string[] $required_states
   *   States required to exist.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public array $forms = [],
    public array $required_states = [],
  ) {
  }

}
