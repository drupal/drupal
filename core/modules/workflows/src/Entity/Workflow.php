<?php

namespace Drupal\workflows\Entity;

use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\workflows\Exception\RequiredStateMissingException;
use Drupal\workflows\Form\WorkflowAddForm;
use Drupal\workflows\Form\WorkflowDeleteForm;
use Drupal\workflows\Form\WorkflowEditForm;
use Drupal\workflows\Form\WorkflowStateAddForm;
use Drupal\workflows\Form\WorkflowStateDeleteForm;
use Drupal\workflows\Form\WorkflowStateEditForm;
use Drupal\workflows\Form\WorkflowTransitionAddForm;
use Drupal\workflows\Form\WorkflowTransitionDeleteForm;
use Drupal\workflows\Form\WorkflowTransitionEditForm;
use Drupal\workflows\WorkflowAccessControlHandler;
use Drupal\workflows\WorkflowInterface;
use Drupal\workflows\WorkflowListBuilder;

/**
 * Defines the workflow entity.
 */
#[ConfigEntityType(
  id: 'workflow',
  label: new TranslatableMarkup('Workflow'),
  label_collection: new TranslatableMarkup('Workflows'),
  label_singular: new TranslatableMarkup('workflow'),
  label_plural: new TranslatableMarkup('workflows'),
  config_prefix: 'workflow',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'access' => WorkflowAccessControlHandler::class,
    'list_builder' => WorkflowListBuilder::class,
    'form' => [
      'add' => WorkflowAddForm::class,
      'edit' => WorkflowEditForm::class,
      'delete' => WorkflowDeleteForm::class,
      'add-state' => WorkflowStateAddForm::class,
      'edit-state' => WorkflowStateEditForm::class,
      'delete-state' => WorkflowStateDeleteForm::class,
      'add-transition' => WorkflowTransitionAddForm::class,
      'edit-transition' => WorkflowTransitionEditForm::class,
      'delete-transition' => WorkflowTransitionDeleteForm::class,
    ],
    'route_provider' => ['html' => AdminHtmlRouteProvider::class],
  ],
  links: [
    'add-form' => '/admin/config/workflow/workflows/add',
    'edit-form' => '/admin/config/workflow/workflows/manage/{workflow}',
    'delete-form' => '/admin/config/workflow/workflows/manage/{workflow}/delete',
    'add-state-form' => '/admin/config/workflow/workflows/manage/{workflow}/add_state',
    'add-transition-form' => '/admin/config/workflow/workflows/manage/{workflow}/add_transition',
    'collection' => '/admin/config/workflow/workflows',
  ],
  admin_permission: 'administer workflows',
  label_count: [
    'singular' => '@count workflow',
    'plural' => '@count workflows',
  ],
  config_export: [
    'id',
    'label',
    'type',
    'type_settings',
  ],
)]
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
   * @var string
   *
   * @see \Drupal\workflows\WorkflowTypeManager
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
