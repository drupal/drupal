<?php

namespace Drupal\content_moderation;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\content_moderation\Entity\ModerationState;
use Drupal\content_moderation\Entity\ModerationStateTransition;

/**
 * Defines a class for dynamic permissions based on transitions.
 */
class Permissions {

  use StringTranslationTrait;

  /**
   * Returns an array of transition permissions.
   *
   * @return array
   *   The transition permissions.
   */
  public function transitionPermissions() {
    // @todo https://www.drupal.org/node/2779933 write a test for this.
    $perms = [];
    /* @var \Drupal\content_moderation\ModerationStateInterface[] $states */
    $states = ModerationState::loadMultiple();
    /* @var \Drupal\content_moderation\ModerationStateTransitionInterface $transition */
    foreach (ModerationStateTransition::loadMultiple() as $id => $transition) {
      $perms['use ' . $id . ' transition'] = [
        'title' => $this->t('Use the %transition_name transition', [
          '%transition_name' => $transition->label(),
        ]),
        'description' => $this->t('Move content from %from state to %to state.', [
          '%from' => $states[$transition->getFromState()]->label(),
          '%to' => $states[$transition->getToState()]->label(),
        ]),
      ];
    }

    return $perms;
  }

}
