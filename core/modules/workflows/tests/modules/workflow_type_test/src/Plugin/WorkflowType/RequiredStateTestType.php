<?php

namespace Drupal\workflow_type_test\Plugin\WorkflowType;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workflows\Plugin\WorkflowTypeBase;

/**
 * Test workflow type.
 *
 * @WorkflowType(
 *   id = "workflow_type_required_state_test",
 *   label = @Translation("Required State Type Test"),
 *   required_states = {
 *     "fresh",
 *     "rotten",
 *   }
 * )
 */
class RequiredStateTestType extends WorkflowTypeBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'states' => [
        'fresh' => [
          'label' => 'Fresh',
          'weight' => 0,
        ],
        'rotten' => [
          'label' => 'Rotten',
          'weight' => 1,
        ],
      ],
      'transitions' => [
        'rot' => [
          'label' => 'Rot',
          'to' => 'rotten',
          'weight' => 0,
          'from' => [
            'fresh',
          ],
        ],
      ],
    ];
  }

}
