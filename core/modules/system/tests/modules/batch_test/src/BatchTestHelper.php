<?php

declare(strict_types=1);

namespace Drupal\batch_test;

use Drupal\Core\Form\FormState;

/**
 * Batch helper for testing batches.
 */
class BatchTestHelper {

  /**
   * Batch operation: Submits form_test_mock_form().
   */
  public function nestedDrupalFormSubmitCallback($value): void {
    $form_state = (new FormState())
      ->setValue('test_value', $value);
    \Drupal::formBuilder()->submitForm('Drupal\batch_test\Form\BatchTestMockForm', $form_state);
  }

  /**
   * Helper function: Stores or retrieves traced execution data.
   */
  public function stack($data = NULL, $reset = FALSE): array|null {
    $state = \Drupal::state();
    if ($reset) {
      $state->delete('batch_test.stack');
    }
    if (!isset($data)) {
      return $state->get('batch_test.stack');
    }
    $stack = $state->get('batch_test.stack');
    $stack[] = $data;
    $state->set('batch_test.stack', $stack);

    return NULL;
  }

}
