<?php

namespace Drupal\workflows\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Workflow type attribute object.
 *
 * Plugin Namespace: Plugin\WorkflowType
 *
 * For a working example, see \Drupal\content_moderation\Plugin\Workflow\ContentModerate
 *
 * @see \Drupal\workflows\WorkflowTypeInterface
 * @see \Drupal\workflows\WorkflowTypeManager
 * @see workflow_type_info_alter()
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class WorkflowType extends Plugin {

  /**
   * States required to exist.
   *
   * Normally supplied by WorkflowType::defaultConfiguration().
   */
  public array $required_states = [];

  /**
   * A list of optional form classes implementing PluginFormInterface.
   *
   * Forms which will be used for the workflow UI are:
   * - 'configure' (\Drupal\workflows\WorkflowTypeInterface::PLUGIN_FORM_KEY)
   * - 'state' (\Drupal\workflows\StateInterface::PLUGIN_FORM_KEY)
   * - 'transition' (\Drupal\workflows\TransitionInterface::PLUGIN_FORM_KEY)
   *
   * @see \Drupal\Core\Plugin\PluginWithFormsInterface
   * @see \Drupal\Core\Plugin\PluginFormInterface
   * @see \Drupal\workflows\Plugin\WorkflowTypeConfigureFormBase
   * @see \Drupal\workflows\Plugin\WorkflowTypeStateFormBase
   * @see \Drupal\workflows\Plugin\WorkflowTypeTransitionFormBase
   * @see \Drupal\workflows\WorkflowTypeInterface::PLUGIN_FORM_KEY
   * @see \Drupal\workflows\StateInterface::PLUGIN_FORM_KEY
   * @see \Drupal\workflows\TransitionInterface::PLUGIN_FORM_KEY
   */
  public array $forms = [];

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
    array $forms = [],
    array $required_states = [],
  ) {
    $this->forms = $forms;
    $this->required_states = $required_states;
  }

}
