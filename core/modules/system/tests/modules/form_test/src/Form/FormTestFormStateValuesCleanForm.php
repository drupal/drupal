<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder for \Drupal\Core\Form\FormState::cleanValues() test.
 *
 * @internal
 */
class FormTestFormStateValuesCleanForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_form_state_clean_values_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Build an example form containing multiple submit and button elements; not
    // only on the top-level.
    $form = ['#tree' => TRUE];
    $form['foo'] = ['#type' => 'submit', '#value' => $this->t('Submit')];
    $form['bar'] = ['#type' => 'submit', '#value' => $this->t('Submit')];
    $form['beer'] = ['#type' => 'value', '#value' => 1000];
    $form['baz']['foo'] = ['#type' => 'button', '#value' => $this->t('Submit')];
    $form['baz']['baz'] = ['#type' => 'submit', '#value' => $this->t('Submit')];
    $form['baz']['beer'] = ['#type' => 'value', '#value' => 2000];

    // Add an arbitrary element and manually set it to be cleaned.
    // Using $form_state->addCleanValueKey('wine'); didn't work here.
    $class = static::class;
    $form['wine'] = [
      '#type' => 'value',
      '#value' => 3000,
      '#process' => [[$class, 'cleanValue']],
    ];

    return $form;
  }

  /**
   * Helper function to clean a value on an element.
   */
  public static function cleanValue(&$element, FormStateInterface $form_state, &$complete_form) {
    $form_state->addCleanValueKey('wine');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    // This won't have a proper JSON header, but Drupal doesn't check for that
    // anyway so this is fine until it's replaced with a JsonResponse.
    print Json::encode($form_state->getValues());
    exit;
  }

}
