<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\content_moderation\Entity\ModerationStateTransition;

/**
 * Validates whether a certain state transition is allowed.
 */
class StateTransitionValidation implements StateTransitionValidationInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Stores the possible state transitions.
   *
   * @var array
   */
  protected $possibleTransitions = [];

  /**
   * Constructs a new StateTransitionValidation.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $query_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queryFactory = $query_factory;
  }

  /**
   * Computes a mapping of possible transitions.
   *
   * This method is uncached and will recalculate the list on every request.
   * In most cases you want to use getPossibleTransitions() instead.
   *
   * @see static::getPossibleTransitions()
   *
   * @return array[]
   *   An array containing all possible transitions. Each entry is keyed by the
   *   "from" state, and the value is an array of all legal "to" states based
   *   on the currently defined transition objects.
   */
  protected function calculatePossibleTransitions() {
    $transitions = $this->transitionStorage()->loadMultiple();

    $possible_transitions = [];
    /** @var \Drupal\content_moderation\ModerationStateTransitionInterface $transition */
    foreach ($transitions as $transition) {
      $possible_transitions[$transition->getFromState()][] = $transition->getToState();
    }
    return $possible_transitions;
  }

  /**
   * Returns a mapping of possible transitions.
   *
   * @return array[]
   *   An array containing all possible transitions. Each entry is keyed by the
   *   "from" state, and the value is an array of all legal "to" states based
   *   on the currently defined transition objects.
   */
  protected function getPossibleTransitions() {
    if (empty($this->possibleTransitions)) {
      $this->possibleTransitions = $this->calculatePossibleTransitions();
    }
    return $this->possibleTransitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidTransitionTargets(ContentEntityInterface $entity, AccountInterface $user) {
    $bundle = $this->loadBundleEntity($entity->getEntityType()->getBundleEntityType(), $entity->bundle());

    $states_for_bundle = $bundle->getThirdPartySetting('content_moderation', 'allowed_moderation_states', []);

    /** @var \Drupal\content_moderation\Entity\ModerationState $current_state */
    $current_state = $entity->moderation_state->entity;

    $all_transitions = $this->getPossibleTransitions();
    $destination_ids = $all_transitions[$current_state->id()];

    $destination_ids = array_intersect($states_for_bundle, $destination_ids);
    $destinations = $this->entityTypeManager->getStorage('moderation_state')->loadMultiple($destination_ids);

    return array_filter($destinations, function(ModerationStateInterface $destination_state) use ($current_state, $user) {
      return $this->userMayTransition($current_state, $destination_state, $user);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getValidTransitions(ContentEntityInterface $entity, AccountInterface $user) {
    $bundle = $this->loadBundleEntity($entity->getEntityType()->getBundleEntityType(), $entity->bundle());

    /** @var \Drupal\content_moderation\Entity\ModerationState $current_state */
    $current_state = $entity->moderation_state->entity;
    $current_state_id = $current_state ? $current_state->id() : $bundle->getThirdPartySetting('content_moderation', 'default_moderation_state');

    // Determine the states that are legal on this bundle.
    $legal_bundle_states = $bundle->getThirdPartySetting('content_moderation', 'allowed_moderation_states', []);

    // Legal transitions include those that are possible from the current state,
    // filtered by those whose target is legal on this bundle and that the
    // user has access to execute.
    $transitions = array_filter($this->getTransitionsFrom($current_state_id), function(ModerationStateTransition $transition) use ($legal_bundle_states, $user) {
      return in_array($transition->getToState(), $legal_bundle_states, TRUE)
        && $user->hasPermission('use ' . $transition->id() . ' transition');
    });

    return $transitions;
  }

  /**
   * Returns a list of possible transitions from a given state.
   *
   * This list is based only on those transitions that exist, not what
   * transitions are legal in a given context.
   *
   * @param string $state_name
   *   The machine name of the state from which we are transitioning.
   *
   * @return ModerationStateTransition[]
   *   A list of possible transitions from a given state.
   */
  protected function getTransitionsFrom($state_name) {
    $result = $this->transitionStateQuery()
      ->condition('stateFrom', $state_name)
      ->sort('weight')
      ->execute();

    return $this->transitionStorage()->loadMultiple($result);
  }

  /**
   * {@inheritdoc}
   */
  public function userMayTransition(ModerationStateInterface $from, ModerationStateInterface $to, AccountInterface $user) {
    if ($transition = $this->getTransitionFromStates($from, $to)) {
      return $user->hasPermission('use ' . $transition->id() . ' transition');
    }
    return FALSE;
  }

  /**
   * Returns the transition object that transitions from one state to another.
   *
   * @param \Drupal\content_moderation\ModerationStateInterface $from
   *   The origin state.
   * @param \Drupal\content_moderation\ModerationStateInterface $to
   *   The destination state.
   *
   * @return ModerationStateTransition|null
   *   A transition object, or NULL if there is no such transition.
   */
  protected function getTransitionFromStates(ModerationStateInterface $from, ModerationStateInterface $to) {
    $from = $this->transitionStateQuery()
      ->condition('stateFrom', $from->id())
      ->condition('stateTo', $to->id())
      ->execute();

    $transitions = $this->transitionStorage()->loadMultiple($from);

    if ($transitions) {
      return current($transitions);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isTransitionAllowed(ModerationStateInterface $from, ModerationStateInterface $to) {
    $allowed_transitions = $this->calculatePossibleTransitions();
    if (isset($allowed_transitions[$from->id()])) {
      return in_array($to->id(), $allowed_transitions[$from->id()], TRUE);
    }
    return FALSE;
  }

  /**
   * Returns a transition state entity query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   A transition state entity query.
   */
  protected function transitionStateQuery() {
    return $this->queryFactory->get('moderation_state_transition', 'AND');
  }

  /**
   * Returns the transition entity storage service.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The transition state entity storage.
   */
  protected function transitionStorage() {
    return $this->entityTypeManager->getStorage('moderation_state_transition');
  }

  /**
   * Returns the state entity storage service.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The moderation state entity storage.
   */
  protected function stateStorage() {
    return $this->entityTypeManager->getStorage('moderation_state');
  }

  /**
   * Loads a specific bundle entity.
   *
   * @param string $bundle_entity_type_id
   *   The bundle entity type ID.
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface|null
   *   The specific bundle entity.
   */
  protected function loadBundleEntity($bundle_entity_type_id, $bundle_id) {
    return $this->entityTypeManager->getStorage($bundle_entity_type_id)->load($bundle_id);
  }

}
