<?php

declare(strict_types=1);

namespace Drupal\form_test;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Simple class for testing methods as Form API callbacks.
 */
class Callbacks {

  use StringTranslationTrait;

  /**
   * Form element validation handler for 'name' in form_test_validate_form().
   */
  public function validateName(&$element, FormStateInterface $form_state) {
    $triggered = FALSE;
    if ($form_state->getValue('name') == 'element_validate') {
      // Alter the form element.
      $element['#value'] = '#value changed by #element_validate';
      // Alter the submitted value in $form_state.
      $form_state->setValueForElement($element, 'value changed by setValueForElement() in #element_validate');

      $triggered = TRUE;
    }
    if ($form_state->getValue('name') == 'element_validate_access') {
      $form_state->set('form_test_name', $form_state->getValue('name'));
      // Alter the form element.
      $element['#access'] = FALSE;

      $triggered = TRUE;
    }
    elseif ($form_state->has('form_test_name')) {
      // To simplify this test, just take over the element's value into
      // $form_state.
      $form_state->setValueForElement($element, $form_state->get('form_test_name'));

      $triggered = TRUE;
    }

    if ($triggered) {
      // Output the element's value from $form_state.
      \Drupal::messenger()
        ->addStatus($this->t('@label value: @value', [
          '@label' => $element['#title'],
          '@value' => $form_state->getValue('name'),
        ]));

      // Trigger a form validation error to see our changes.
      $form_state->setErrorByName('');
    }
  }

  /**
   * Create a header and options array. Helper function for callbacks.
   */
  public static function tableselectGetData(): array {
    $header = [
      'one' => t('One'),
      'two' => t('Two'),
      'three' => t('Three'),
      'four' => t('Four'),
    ];

    $options['row1'] = [
      'title' => ['data' => ['#title' => t('row1')]],
      'one' => 'row1col1',
      'two' => t('row1col2'),
      'three' => t('row1col3'),
      'four' => t('row1col4'),
    ];

    $options['row2'] = [
      'title' => ['data' => ['#title' => t('row2')]],
      'one' => 'row2col1',
      'two' => t('row2col2'),
      'three' => t('row2col3'),
      'four' => t('row2col4'),
    ];

    $options['row3'] = [
      'title' => ['data' => ['#title' => t('row3')]],
      'one' => 'row3col1',
      'two' => t('row3col2'),
      'three' => t('row3col3'),
      'four' => t('row3col4'),
    ];

    return [$header, $options];
  }

  /**
   * Submit callback that just lets the form rebuild.
   */
  public static function userRegisterFormRebuild(array $form, FormStateInterface $form_state): void {
    \Drupal::messenger()->addStatus('Form rebuilt.');
    $form_state->setRebuild();
  }

}
