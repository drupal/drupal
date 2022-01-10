<?php

namespace Drupal\content_moderation;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\State;

/**
 * Defines a class for dynamic permissions based on transitions.
 *
 * @internal
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
    foreach (Workflow::loadMultipleByType('content_moderation') as $workflow) {
      foreach ($workflow->getTypePlugin()->getTransitions() as $transition) {
        $permissions['use ' . $workflow->id() . ' transition ' . $transition->id()] = [
          'title' => $this->t('%workflow workflow: Use %transition transition.', [
            '%workflow' => $workflow->label(),
            '%transition' => $transition->label(),
          ]),
          'description' => $this->formatPlural(
            count($transition->from()),
            'Move content from %from state to %to state.',
            'Move content from %from states to %to state.', [
              '%from' => implode(', ', array_map([State::class, 'labelCallback'], $transition->from())),
              '%to' => $transition->to()->label(),
            ]
          ),
          'dependencies' => [
            $workflow->getConfigDependencyKey() => [$workflow->getConfigDependencyName()],
          ],
        ];
      }
    }

    return $permissions;
  }

}
