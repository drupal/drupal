<?php

namespace Drupal\content_moderation;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workflows\Entity\Workflow;

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
    $permissions = [];
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    foreach (Workflow::loadMultipleByType('content_moderation') as $id => $workflow) {
      foreach ($workflow->getTransitions() as $transition) {
        $permissions['use ' . $workflow->id() . ' transition ' . $transition->id()] = [
          'title' => $this->t('Use %transition transition from %workflow workflow.', [
            '%transition' => $transition->label(),
            '%workflow' => $workflow->label(),
          ]),
        ];
      }
    }

    return $permissions;
  }

}
