<?php

namespace Drupal\content_moderation\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\content_moderation\ModerationStateTransitionInterface;

/**
 * Defines the Moderation state transition entity.
 *
 * @ConfigEntityType(
 *   id = "moderation_state_transition",
 *   label = @Translation("Moderation state transition"),
 *   handlers = {
 *     "list_builder" = "Drupal\content_moderation\ModerationStateTransitionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\content_moderation\Form\ModerationStateTransitionForm",
 *       "edit" = "Drupal\content_moderation\Form\ModerationStateTransitionForm",
 *       "delete" = "Drupal\content_moderation\Form\ModerationStateTransitionDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "state_transition",
 *   admin_permission = "administer moderation state transitions",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/workflow/moderation/transitions/add",
 *     "edit-form" = "/admin/config/workflow/moderation/transitions/{moderation_state_transition}",
 *     "delete-form" = "/admin/config/workflow/moderation/transitions/{moderation_state_transition}/delete",
 *     "collection" = "/admin/config/workflow/moderation/transitions"
 *   }
 * )
 */
class ModerationStateTransition extends ConfigEntityBase implements ModerationStateTransitionInterface {

  /**
   * The Moderation state transition ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Moderation state transition label.
   *
   * @var string
   */
  protected $label;

  /**
   * ID of from state.
   *
   * @var string
   */
  protected $stateFrom;

  /**
   * ID of to state.
   *
   * @var string
   */
  protected $stateTo;

  /**
   * Relative weight of this transition.
   *
   * @var int
   */
  protected $weight;

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    if ($this->stateFrom) {
      $this->addDependency('config', ModerationState::load($this->stateFrom)->getConfigDependencyName());
    }
    if ($this->stateTo) {
      $this->addDependency('config', ModerationState::load($this->stateTo)->getConfigDependencyName());
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFromState() {
    return $this->stateFrom;
  }

  /**
   * {@inheritdoc}
   */
  public function getToState() {
    return $this->stateTo;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

}
