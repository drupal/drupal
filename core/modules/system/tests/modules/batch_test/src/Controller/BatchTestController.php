<?php

declare(strict_types=1);

namespace Drupal\batch_test\Controller;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormState;

/**
 * Controller routines for batch tests.
 */
class BatchTestController {

  /**
   * Redirects successfully.
   *
   * @return array
   *   Render array containing success message.
   */
  public function testRedirect() {
    return [
      'success' => [
        '#markup' => 'Redirection successful.',
      ],
    ];
  }

  /**
   * Fires a batch process without a form submission.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response if the batch is progressive. No return value otherwise.
   */
  public function testLargePercentage() {
    batch_test_stack(NULL, TRUE);

    batch_set(_batch_test_batch_5());
    return batch_process('batch-test/redirect');
  }

  /**
   * Submits a form within a batch programmatically.
   *
   * @param int $value
   *   Some value passed to a custom batch callback.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response if the batch is progressive. No return value otherwise.
   */
  public function testNestedDrupalFormSubmit($value = 1) {
    // Set the batch and process it.
    $batch_builder = (new BatchBuilder())
      ->addOperation('_batch_test_nested_drupal_form_submit_callback', [$value]);
    batch_set($batch_builder->toArray());
    return batch_process('batch-test/redirect');
  }

  /**
   * Fires a batch process without a form submission.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response if the batch is progressive. No return value otherwise.
   */
  public function testNoForm() {
    batch_test_stack(NULL, TRUE);

    batch_set(_batch_test_batch_1());
    return batch_process('batch-test/redirect');

  }

  /**
   * Fires a batch process without a form submission and a finish redirect.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response if the batch is progressive. No return value otherwise.
   */
  public function testFinishRedirect() {
    batch_test_stack(NULL, TRUE);

    $batch = _batch_test_batch_1();
    $batch['finished'] = '_batch_test_finished_1_finished';
    batch_set($batch);
    return batch_process('batch-test/redirect');
  }

  /**
   * Submits the 'Chained' form programmatically.
   *
   * Programmatic form: the page submits the 'Chained' form through
   * \Drupal::formBuilder()->submitForm().
   *
   * @param int $value
   *   Some value passed to a the chained form.
   *
   * @return array
   *   Render array containing markup.
   */
  public function testProgrammatic($value = 1) {
    $form_state = (new FormState())->setValues([
      'value' => $value,
    ]);
    \Drupal::formBuilder()->submitForm('Drupal\batch_test\Form\BatchTestChainedForm', $form_state);
    return [
      'success' => [
        '#markup' => 'Got out of a programmatic batched form.',
      ],
    ];
  }

  /**
   * Runs a batch for testing theme used on the progress page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response if the batch is progressive. No return value otherwise.
   */
  public function testThemeBatch() {
    batch_test_stack(NULL, TRUE);
    $batch = [
      'operations' => [
        ['_batch_test_theme_callback', []],
      ],
    ];
    batch_set($batch);
    return batch_process('batch-test/redirect');
  }

  /**
   * Runs a batch for testing the title shown on the progress page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response if the batch is progressive. No return value otherwise.
   */
  public function testTitleBatch() {
    batch_test_stack(NULL, TRUE);
    $batch = [
      'title' => 'Batch Test',
      'operations' => [
        ['_batch_test_title_callback', []],
      ],
    ];
    batch_set($batch);
    return batch_process('batch-test/redirect');
  }

}
