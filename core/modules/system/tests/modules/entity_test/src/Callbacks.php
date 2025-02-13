<?php

declare(strict_types=1);

namespace Drupal\entity_test;

use Drupal\Core\Form\FormStateInterface;

/**
 * Simple object with callbacks.
 */
class Callbacks {

  /**
   * Validation handler for the entity_test entity form.
   */
  public static function entityTestFormValidate(array &$form, FormStateInterface $form_state): void {
    $form['#entity_test_form_validate'] = TRUE;
  }

  /**
   * Validation handler for the entity_test entity form.
   */
  public static function entityTestFormValidateCheck(array &$form, FormStateInterface $form_state): void {
    if (!empty($form['#entity_test_form_validate'])) {
      \Drupal::state()->set('entity_test.form.validate.result', TRUE);
    }
  }

}
