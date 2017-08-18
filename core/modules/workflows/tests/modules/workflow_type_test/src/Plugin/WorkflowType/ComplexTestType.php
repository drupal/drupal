<?php

namespace Drupal\workflow_type_test\Plugin\WorkflowType;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workflows\Plugin\WorkflowTypeBase;

/**
 * Test workflow type.
 *
 * @WorkflowType(
 *   id = "workflow_type_complex_test",
 *   label = @Translation("Workflow Type Complex Test"),
 *   forms = {
 *     "configure" = "\Drupal\workflow_type_test\Form\ComplexTestTypeConfigureForm",
 *     "state" = "\Drupal\workflow_type_test\Form\ComplexTestTypeStateForm",
 *     "transition" = "\Drupal\workflow_type_test\Form\ComplexTestTypeTransitionForm",
 *   }
 * )
 */
class ComplexTestType extends WorkflowTypeBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    // Always return TRUE to allow the logic in
    // \Drupal\workflows\Entity\Workflow::onDependencyRemoval() to be tested.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'example_setting' => '',
    ];
  }

}
