<?php

namespace Drupal\workflows\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\workflows\Exception\RequiredStateMissingException;
use Drupal\workflows\WorkflowInterface;

/**
 * Defines the workflow entity.
 *
 * @ConfigEntityType(
 *   id = "workflow",
 *   label = @Translation("Workflow"),
 *   label_collection = @Translation("Workflows"),
 *   handlers = {
 *     "access" = "Drupal\workflows\WorkflowAccessControlHandler",
 *     "list_builder" = "Drupal\workflows\WorkflowListBuilder",
 *     "form" = {
 *       "add" = "Drupal\workflows\Form\WorkflowAddForm",
 *       "edit" = "Drupal\workflows\Form\WorkflowEditForm",
 *       "delete" = "Drupal\workflows\Form\WorkflowDeleteForm",
 *       "add-state" = "Drupal\workflows\Form\WorkflowStateAddForm",
 *       "edit-state" = "Drupal\workflows\Form\WorkflowStateEditForm",
 *       "delete-state" = "Drupal\workflows\Form\WorkflowStateDeleteForm",
 *       "add-transition" = "Drupal\workflows\Form\WorkflowTransitionAddForm",
 *       "edit-transition" = "Drupal\workflows\Form\WorkflowTransitionEditForm",
 *       "delete-transition" = "Drupal\workflows\Form\WorkflowTransitionDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "workflow",
 *   admin_permission = "administer workflows",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/workflow/workflows/add",
 *     "edit-form" = "/admin/config/workflow/workflows/manage/{workflow}",
 *     "delete-form" = "/admin/config/workflow/workflows/manage/{workflow}/delete",
 *     "add-state-form" = "/admin/config/workflow/workflows/manage/{workflow}/add_state",
 *     "add-transition-form" = "/admin/config/workflow/workflows/manage/{workflow}/add_transition",
 *     "collection" = "/admin/config/workflow/workflows",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "type",
 *     "type_settings",
 *   },
 * )
 */
class Workflow extends ConfigEntityBase implements WorkflowInterface, EntityWithPluginCollectionInterface {

  /**
   * The Workflow ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The workflow label.
   *
   * @var string
   */
  protected $label;

  /**
   * The workflow type plugin ID.
   *
   * @see \Drupal\workflows\WorkflowTypeManager
   *
   * @var string
   */
  protected $type;

  /**
   * The configuration for the workflow type plugin.
   *
   * @var array
   */
  protected $type_settings = [];

  /**
   * The workflow type plugin collection.
   *
   * @var \Drupal\Component\Plugin\LazyPluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $workflow_type = $this->getTypePlugin();
    $missing_states = array_diff($workflow_type->getRequiredStates(), array_keys($this->getTypePlugin()->getStates()));
    if (!empty($missing_states)) {
      throw new RequiredStateMissingException(sprintf("Workflow type '{$workflow_type->label()}' requires states with the ID '%s' in workflow '{$this->id()}'", implode("', '", $missing_states)));
    }
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function getTypePlugin() {
    return $this->getPluginCollection()->get($this->type);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['type_settings' => $this->getPluginCollection()];
  }

  /**
   * Encapsulates the creation of the workflow's plugin collection.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   *   The workflow's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection && $this->type) {
      $this->pluginCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.workflows.type'), $this->type, $this->type_settings);
    }
    return $this->pluginCollection;
  }

  /**
   * Loads all workflows of the provided type.
   *
   * @param string $type
   *   The workflow type to load all workflows for.
   *
   * @return static[]
   *   An array of workflow objects of the provided workflow type, indexed by
   *   their IDs.
   *
   *  @see \Drupal\workflows\Annotation\WorkflowType
   */
  public static function loadMultipleByType($type) {
    return self::loadMultiple(\Drupal::entityQuery('workflow')->condition('type', $type)->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function status() {
    // In order for a workflow to be usable it must have at least one state.
    return !empty($this->status) && !empty($this->getTypePlugin()->getStates());
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    // Give the parent method and the workflow type plugin a chance to react
    // to removed dependencies and report if either of these two made a change.
    $parent_changed_entity = parent::onDependencyRemoval($dependencies);
    $plugin_changed_entity = $this->getTypePlugin()->onDependencyRemoval($dependencies);
    return $plugin_changed_entity || $parent_changed_entity;
  }

}
