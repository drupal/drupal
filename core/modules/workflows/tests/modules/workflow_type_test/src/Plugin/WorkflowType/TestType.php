<?php

namespace Drupal\workflow_type_test\Plugin\WorkflowType;

use Drupal\workflows\Plugin\WorkflowTypeBase;

/**
 * Test workflow type.
 *
 * @WorkflowType(
 *   id = "workflow_type_test",
 *   label = @Translation("Workflow Type Test"),
 * )
 */
class TestType extends WorkflowTypeBase {
}
