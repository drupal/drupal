<?php

namespace Drupal\workflows\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\workflows\State;
use Drupal\workflows\StateInterface;
use Drupal\workflows\Transition;
use Drupal\workflows\TransitionInterface;
use Drupal\workflows\WorkflowInterface;
use Drupal\workflows\WorkflowTypeInterface;

/**
 * A base class for Workflow type plugins.
 *
 * @see \Drupal\workflows\Annotation\WorkflowType
 */
abstract class WorkflowTypeBase extends PluginBase implements WorkflowTypeInterface {

  use PluginWithFormsTrait;

  /**
   * A regex for matching a valid state/transition machine name.
   */
  const VALID_ID_REGEX = '/[^a-z0-9_]+/';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $definition = $this->getPluginDefinition();
    // The label can be an object.
    // @see \Drupal\Core\StringTranslation\TranslatableMarkup
    return $definition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function workflowHasData(WorkflowInterface $workflow) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function workflowStateHasData(WorkflowInterface $workflow, StateInterface $state) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredStates() {
    return $this->getPluginDefinition()['required_states'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'states' => [],
      'transitions' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    return FALSE;
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
  public function addState($state_id, $label) {
    if ($this->hasState($state_id)) {
      throw new \InvalidArgumentException("The state '$state_id' already exists in workflow.");
    }
    if (preg_match(static::VALID_ID_REGEX, $state_id)) {
      throw new \InvalidArgumentException("The state ID '$state_id' must contain only lowercase letters, numbers, and underscores");
    }
    $this->configuration['states'][$state_id] = [
      'label' => $label,
      'weight' => $this->getNextWeight($this->configuration['states']),
    ];
    ksort($this->configuration['states']);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasState($state_id) {
    return isset($this->configuration['states'][$state_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getStates($state_ids = NULL) {
    if ($state_ids === NULL) {
      $state_ids = array_keys($this->configuration['states']);
    }
    /** @var \Drupal\workflows\StateInterface[] $states */
    $states = array_combine($state_ids, array_map([$this, 'getState'], $state_ids));
    return static::labelWeightMultisort($states);
  }

  /**
   * {@inheritdoc}
   */
  public function getState($state_id) {
    if (!isset($this->configuration['states'][$state_id])) {
      throw new \InvalidArgumentException("The state '$state_id' does not exist in workflow.");
    }
    return new State(
      $this,
      $state_id,
      $this->configuration['states'][$state_id]['label'],
      $this->configuration['states'][$state_id]['weight']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setStateLabel($state_id, $label) {
    if (!$this->hasState($state_id)) {
      throw new \InvalidArgumentException("The state '$state_id' does not exist in workflow.");
    }
    $this->configuration['states'][$state_id]['label'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStateWeight($state_id, $weight) {
    if (!$this->hasState($state_id)) {
      throw new \InvalidArgumentException("The state '$state_id' does not exist in workflow.");
    }
    if (!is_numeric($weight)) {
      $label = $this->getState($state_id)->label();
      throw new \InvalidArgumentException("The weight '$weight' must be numeric for state '$label'.");
    }
    $this->configuration['states'][$state_id]['weight'] = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteState($state_id) {
    if (!$this->hasState($state_id)) {
      throw new \InvalidArgumentException("The state '$state_id' does not exist in workflow.");
    }
    if (count($this->configuration['states']) === 1) {
      throw new \InvalidArgumentException("The state '$state_id' can not be deleted from workflow as it is the only state.");
    }

    foreach ($this->configuration['transitions'] as $transition_id => $transition) {
      if ($transition['to'] === $state_id) {
        $this->deleteTransition($transition_id);
        continue;
      }
      $from_key = array_search($state_id, $transition['from'], TRUE);
      if ($from_key !== FALSE) {
        // Remove state from the from array.
        unset($transition['from'][$from_key]);
        if (empty($transition['from'])) {
          // There are no more 'from' entries, remove the transition.
          $this->deleteTransition($transition_id);
          continue;
        }
        // We changed the from state, update the transition.
        $this->setTransitionFromStates($transition_id, $transition['from']);
      }
    }
    unset($this->configuration['states'][$state_id]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addTransition($transition_id, $label, array $from_state_ids, $to_state_id) {
    if ($this->hasTransition($transition_id)) {
      throw new \InvalidArgumentException("The transition '$transition_id' already exists in workflow.");
    }
    if (preg_match(static::VALID_ID_REGEX, $transition_id)) {
      throw new \InvalidArgumentException("The transition ID '$transition_id' must contain only lowercase letters, numbers, and underscores.");
    }

    if (!$this->hasState($to_state_id)) {
      throw new \InvalidArgumentException("The state '$to_state_id' does not exist in workflow.");
    }
    $this->configuration['transitions'][$transition_id] = [
      'label' => $label,
      'from' => [],
      'to' => $to_state_id,
      // Always add to the end.
      'weight' => $this->getNextWeight($this->configuration['transitions']),
    ];

    try {
      $this->setTransitionFromStates($transition_id, $from_state_ids);
    }
    catch (\InvalidArgumentException $e) {
      unset($this->configuration['transitions'][$transition_id]);
      throw $e;
    }

    ksort($this->configuration['transitions']);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitions(array $transition_ids = NULL) {
    if ($transition_ids === NULL) {
      $transition_ids = array_keys($this->configuration['transitions']);
    }
    /** @var \Drupal\workflows\TransitionInterface[] $transitions */
    $transitions = array_combine($transition_ids, array_map([$this, 'getTransition'], $transition_ids));
    return static::labelWeightMultisort($transitions);
  }

  /**
   * Sort states or transitions by weight, label, and key.
   *
   * @param \Drupal\workflows\StateInterface[]|\Drupal\workflows\TransitionInterface[] $objects
   *   An array of state or transition objects to multi-sort, keyed by the
   *   state or transition ID.
   *
   * @return \Drupal\workflows\StateInterface[]|\Drupal\workflows\TransitionInterface[]
   *   An array of sorted transitions or states, keyed by the state or
   *   transition ID.
   */
  protected static function labelWeightMultisort($objects) {
    if (count($objects) > 1) {
      // Separate weights, labels, and keys into arrays.
      $weights = $labels = [];
      $keys = array_keys($objects);
      foreach ($objects as $id => $object) {
        $weights[$id] = $object->weight();
        $labels[$id] = $object->label();
      }
      // Sort weights, labels, and keys in the same order as each other.
      array_multisort(
      // Use the numerical weight as the primary sort.
        $weights, SORT_NUMERIC, SORT_ASC,
        // When objects have the same weight, sort them alphabetically by label.
        $labels, SORT_NATURAL, SORT_ASC,
        // Ensure that the keys (the object IDs) are sorted in the same order as
        // the weights.
        $keys
      );
      // Combine keys and weights to make sure the weights are keyed with the
      // correct keys.
      $weights = array_combine($keys, $weights);
      // Return the objects sorted by weight.
      return array_replace($weights, $objects);
    }
    return $objects;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransition($transition_id) {
    if (!$this->hasTransition($transition_id)) {
      throw new \InvalidArgumentException("The transition '$transition_id' does not exist in workflow.");
    }
    return new Transition(
      $this,
      $transition_id,
      $this->configuration['transitions'][$transition_id]['label'],
      $this->configuration['transitions'][$transition_id]['from'],
      $this->configuration['transitions'][$transition_id]['to'],
      $this->configuration['transitions'][$transition_id]['weight']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function hasTransition($transition_id) {
    return isset($this->configuration['transitions'][$transition_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitionsForState($state_id, $direction = TransitionInterface::DIRECTION_FROM) {
    $transition_ids = array_keys(array_filter($this->configuration['transitions'], function ($transition) use ($state_id, $direction) {
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
      throw new \InvalidArgumentException("The transition from '$from_state_id' to '$to_state_id' does not exist in workflow.");
    }
    return $this->getTransition($transition_id);
  }

  /**
   * {@inheritdoc}
   */
  public function hasTransitionFromStateToState($from_state_id, $to_state_id) {
    return $this->getTransitionIdFromStateToState($from_state_id, $to_state_id) !== NULL;
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
    foreach ($this->configuration['transitions'] as $transition_id => $transition) {
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
    if (!$this->hasTransition($transition_id)) {
      throw new \InvalidArgumentException("The transition '$transition_id' does not exist in workflow.");
    }
    $this->configuration['transitions'][$transition_id]['label'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransitionWeight($transition_id, $weight) {
    if (!$this->hasTransition($transition_id)) {
      throw new \InvalidArgumentException("The transition '$transition_id' does not exist in workflow.");
    }
    if (!is_numeric($weight)) {
      $label = $this->getTransition($transition_id)->label();
      throw new \InvalidArgumentException("The weight '$weight' must be numeric for transition '$label'.");
    }
    $this->configuration['transitions'][$transition_id]['weight'] = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransitionFromStates($transition_id, array $from_state_ids) {
    if (!$this->hasTransition($transition_id)) {
      throw new \InvalidArgumentException("The transition '$transition_id' does not exist in workflow.");
    }

    // Ensure that the states exist.
    foreach ($from_state_ids as $from_state_id) {
      if (!$this->hasState($from_state_id)) {
        throw new \InvalidArgumentException("The state '$from_state_id' does not exist in workflow.");
      }
      if ($this->hasTransitionFromStateToState($from_state_id, $this->configuration['transitions'][$transition_id]['to'])) {
        $existing_transition_id = $this->getTransitionIdFromStateToState($from_state_id, $this->configuration['transitions'][$transition_id]['to']);
        if ($transition_id !== $existing_transition_id) {
          throw new \InvalidArgumentException("The '$existing_transition_id' transition already allows '$from_state_id' to '{$this->configuration['transitions'][$transition_id]['to']}' transitions in workflow.");
        }
      }
    }

    // Preserve the order of the state IDs in the from value and don't save any
    // keys.
    $from_state_ids = array_values($from_state_ids);
    sort($from_state_ids);
    $this->configuration['transitions'][$transition_id]['from'] = $from_state_ids;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTransition($transition_id) {
    if (!$this->hasTransition($transition_id)) {
      throw new \InvalidArgumentException("The transition '$transition_id' does not exist in workflow.");
    }
    unset($this->configuration['transitions'][$transition_id]);
    return $this;
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

}
