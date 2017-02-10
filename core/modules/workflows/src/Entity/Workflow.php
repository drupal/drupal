<?php

namespace Drupal\workflows\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\workflows\Exception\RequiredStateMissingException;
use Drupal\workflows\State;
use Drupal\workflows\Transition;
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
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "workflow",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "states",
 *     "transitions",
 *     "type",
 *     "type_settings"
 *   },
 * )
 *
 * @internal
 *   The workflow system is currently experimental and should only be leveraged
 *   by experimental modules and development releases of contributed modules.
 */
class Workflow extends ConfigEntityBase implements WorkflowInterface, EntityWithPluginCollectionInterface {

  /**
   * The Workflow ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Moderation state label.
   *
   * @var string
   */
  protected $label;

  /**
   * The states of the workflow.
   *
   * The array key is the machine name for the state. The structure of each
   * array item is:
   * @code
   *   label: {translatable label}
   *   weight: {integer value}
   * @endcode
   *
   * @var array
   */
  protected $states = [];

  /**
   * The permitted transitions of the workflow.
   *
   * The array key is the machine name for the transition. The machine name is
   * generated from the machine names of the states. The structure of each array
   * item is:
   * @code
   *   from:
   *     - {state machine name}
   *     - {state machine name}
   *   to: {state machine name}
   *   label: {translatable label}
   * @endcode
   *
   * @var array
   */
  protected $transitions = [];

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
    $missing_states = array_diff($workflow_type->getRequiredStates(), array_keys($this->getStates()));
    if (!empty($missing_states)) {
      throw new RequiredStateMissingException(sprintf("Workflow type '{$workflow_type->label()}' requires states with the ID '%s' in workflow '{$this->id()}'", implode("', '", $missing_states)));
    }
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function addState($state_id, $label) {
    if (isset($this->states[$state_id])) {
      throw new \InvalidArgumentException("The state '$state_id' already exists in workflow '{$this->id()}'");
    }
    if (preg_match('/[^a-z0-9_]+/', $state_id)) {
      throw new \InvalidArgumentException("The state ID '$state_id' must contain only lowercase letters, numbers, and underscores");
    }
    $this->states[$state_id] = [
      'label' => $label,
      'weight' => $this->getNextWeight($this->states),
    ];
    ksort($this->states);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasState($state_id) {
    return isset($this->states[$state_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getStates($state_ids = NULL) {
    if ($state_ids === NULL) {
      $state_ids = array_keys($this->states);
    }
    /** @var \Drupal\workflows\StateInterface[] $states */
    $states = array_combine($state_ids, array_map([$this, 'getState'], $state_ids));
    if (count($states) > 1) {
      // Sort states by weight and then label.
      $weights = $labels = [];
      foreach ($states as $id => $state) {
        $weights[$id] = $state->weight();
        $labels[$id] = $state->label();
      }
      array_multisort(
        $weights, SORT_NUMERIC, SORT_ASC,
        $labels, SORT_NATURAL, SORT_ASC
      );
      $states = array_replace($weights, $states);
    }
    return $states;
  }

  /**
   * {@inheritdoc}
   */
  public function getState($state_id) {
    if (!isset($this->states[$state_id])) {
      throw new \InvalidArgumentException("The state '$state_id' does not exist in workflow '{$this->id()}'");
    }
    $state = new State(
      $this,
      $state_id,
      $this->states[$state_id]['label'],
      $this->states[$state_id]['weight']
    );
    return $this->getTypePlugin()->decorateState($state);
  }

  /**
   * {@inheritdoc}
   */
  public function setStateLabel($state_id, $label) {
    if (!isset($this->states[$state_id])) {
      throw new \InvalidArgumentException("The state '$state_id' does not exist in workflow '{$this->id()}'");
    }
    $this->states[$state_id]['label'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStateWeight($state_id, $weight) {
    if (!isset($this->states[$state_id])) {
      throw new \InvalidArgumentException("The state '$state_id' does not exist in workflow '{$this->id()}'");
    }
    $this->states[$state_id]['weight'] = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteState($state_id) {
    if (!isset($this->states[$state_id])) {
      throw new \InvalidArgumentException("The state '$state_id' does not exist in workflow '{$this->id()}'");
    }
    if (count($this->states) === 1) {
      throw new \InvalidArgumentException("The state '$state_id' can not be deleted from workflow '{$this->id()}' as it is the only state");
    }

    foreach ($this->transitions as $transition_id => $transition) {
      $from_key = array_search($state_id, $transition['from'], TRUE);
      if ($from_key !== FALSE) {
        // Remove state from the from array.
        unset($transition['from'][$from_key]);
      }
      if (empty($transition['from']) || $transition['to'] === $state_id) {
        $this->deleteTransition($transition_id);
      }
      elseif ($from_key !== FALSE) {
        $this->setTransitionFromStates($transition_id, $transition['from']);
      }
    }
    unset($this->states[$state_id]);
    $this->getTypePlugin()->deleteState($state_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInitialState() {
    $ordered_states = $this->getStates();
    return reset($ordered_states);
  }

  /**
   * {@inheritdoc}
   */
  public function addTransition($transition_id, $label, array $from_state_ids, $to_state_id) {
    if (isset($this->transitions[$transition_id])) {
      throw new \InvalidArgumentException("The transition '$transition_id' already exists in workflow '{$this->id()}'");
    }
    if (preg_match('/[^a-z0-9_]+/', $transition_id)) {
      throw new \InvalidArgumentException("The transition ID '$transition_id' must contain only lowercase letters, numbers, and underscores");
    }

    if (!$this->hasState($to_state_id)) {
      throw new \InvalidArgumentException("The state '$to_state_id' does not exist in workflow '{$this->id()}'");
    }
    $this->transitions[$transition_id] = [
      'label' => $label,
      'from' => [],
      'to' => $to_state_id,
      // Always add to the end.
      'weight' => $this->getNextWeight($this->transitions),
    ];

    try {
      $this->setTransitionFromStates($transition_id, $from_state_ids);
    }
    catch (\InvalidArgumentException $e) {
      unset($this->transitions[$transition_id]);
      throw $e;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitions(array $transition_ids = NULL) {
    if ($transition_ids === NULL) {
      $transition_ids = array_keys($this->transitions);
    }
    /** @var \Drupal\workflows\TransitionInterface[] $transitions */
    $transitions = array_combine($transition_ids, array_map([$this, 'getTransition'], $transition_ids));
    if (count($transitions) > 1) {
      // Sort transitions by weights and then labels.
      $weights = $labels = [];
      foreach ($transitions as $id => $transition) {
        $weights[$id] = $transition->weight();
        $labels[$id] = $transition->label();
      }
      array_multisort(
        $weights, SORT_NUMERIC, SORT_ASC,
        $labels, SORT_NATURAL, SORT_ASC
      );
      $transitions = array_replace($weights, $transitions);
    }
    return $transitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransition($transition_id) {
    if (!isset($this->transitions[$transition_id])) {
      throw new \InvalidArgumentException("The transition '$transition_id' does not exist in workflow '{$this->id()}'");
    }
    $transition = new Transition(
      $this,
      $transition_id,
      $this->transitions[$transition_id]['label'],
      $this->transitions[$transition_id]['from'],
      $this->transitions[$transition_id]['to'],
      $this->transitions[$transition_id]['weight']
    );
    return $this->getTypePlugin()->decorateTransition($transition);
  }

  /**
   * {@inheritdoc}
   */
  public function hasTransition($transition_id) {
    return isset($this->transitions[$transition_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitionsForState($state_id, $direction = 'from') {
    $transition_ids = array_keys(array_filter($this->transitions, function ($transition) use ($state_id, $direction) {
      return in_array($state_id, (array) $transition[$direction], TRUE);
    }));
    return $this->getTransitions($transition_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitionFromStateToState($from_state_id, $to_state_id) {
    $transition_id = $this->getTransitionIdFromStateToState($from_state_id, $to_state_id);
    if (empty($transition_id)) {
      throw new \InvalidArgumentException("The transition from '$from_state_id' to '$to_state_id' does not exist in workflow '{$this->id()}'");
    }
    return $this->getTransition($transition_id);
  }

  /**
   * {@inheritdoc}
   */
  public function hasTransitionFromStateToState($from_state_id, $to_state_id) {
    return !empty($this->getTransitionIdFromStateToState($from_state_id, $to_state_id));
  }

  /**
   * Gets the transition ID from state to state.
   *
   * @param string $from_state_id
   *   The state ID to transition from.
   * @param string $to_state_id
   *   The state ID to transition to.
   *
   * @return string|null
   *   The transition ID, or NULL if no transition exists.
   */
  protected function getTransitionIdFromStateToState($from_state_id, $to_state_id) {
    foreach ($this->transitions as $transition_id => $transition) {
      if (in_array($from_state_id, $transition['from'], TRUE) && $transition['to'] === $to_state_id) {
        return $transition_id;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransitionLabel($transition_id, $label) {
    if (isset($this->transitions[$transition_id])) {
      $this->transitions[$transition_id]['label'] = $label;
    }
    else {
      throw new \InvalidArgumentException("The transition '$transition_id' does not exist in workflow '{$this->id()}'");
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransitionWeight($transition_id, $weight) {
    if (isset($this->transitions[$transition_id])) {
      $this->transitions[$transition_id]['weight'] = $weight;
    }
    else {
      throw new \InvalidArgumentException("The transition '$transition_id' does not exist in workflow '{$this->id()}'");
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransitionFromStates($transition_id, array $from_state_ids) {
    if (!isset($this->transitions[$transition_id])) {
      throw new \InvalidArgumentException("The transition '$transition_id' does not exist in workflow '{$this->id()}'");
    }

    // Ensure that the states exist.
    foreach ($from_state_ids as $from_state_id) {
      if (!$this->hasState($from_state_id)) {
        throw new \InvalidArgumentException("The state '$from_state_id' does not exist in workflow '{$this->id()}'");
      }
      if ($this->hasTransitionFromStateToState($from_state_id, $this->transitions[$transition_id]['to'])) {
        $transition = $this->getTransitionFromStateToState($from_state_id, $this->transitions[$transition_id]['to']);
        if ($transition_id !== $transition->id()) {
          throw new \InvalidArgumentException("The '{$transition->id()}' transition already allows '$from_state_id' to '{$this->transitions[$transition_id]['to']}' transitions in workflow '{$this->id()}'");
        }
      }
    }

    // Preserve the order of the state IDs in the from value and don't save any
    // keys.
    $from_state_ids = array_values($from_state_ids);
    sort($from_state_ids);
    $this->transitions[$transition_id]['from'] = $from_state_ids;
    ksort($this->transitions);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTransition($transition_id) {
    if (isset($this->transitions[$transition_id])) {
      unset($this->transitions[$transition_id]);
      $this->getTypePlugin()->deleteTransition($transition_id);
    }
    else {
      throw new \InvalidArgumentException("The transition '$transition_id' does not exist in workflow '{$this->id()}'");
    }
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getTypePlugin() {
    return $this->getPluginCollection()->get($this->type);
  }

  /**
   * {@inheritDoc}
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
   * Gets the weight for a new state or transition.
   *
   * @param array $items
   *   An array of states or transitions information where each item has a
   *   'weight' key with a numeric value.
   *
   * @return int
   *   The weight for a new item in the array so that it has the highest weight.
   */
  protected function getNextWeight(array $items) {
    return array_reduce($items, function ($carry, $item) {
      return max($carry, $item['weight'] + 1);
    }, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function status() {
    // In order for a workflow to be usable it must have at least one state.
    return !empty($this->status) && !empty($this->states);
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = $this->getTypePlugin()->onDependencyRemoval($dependencies);
    // Ensure the parent method is called in order to process dependencies that
    // affect third party settings.
    return parent::onDependencyRemoval($dependencies) || $changed;
  }

}
