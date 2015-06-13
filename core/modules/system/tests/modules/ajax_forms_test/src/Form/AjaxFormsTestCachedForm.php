<?php

/**
 * @file
 * Contains \Drupal\ajax_forms_test\Form\AjaxFormsTestCachedForm.
 */

namespace Drupal\ajax_forms_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides an AJAX form that will be cached.
 */
class AjaxFormsTestCachedForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_form_cache_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['test1'] = [
      '#type' => 'select',
      '#title' => $this->t('Test 1'),
      '#options' => [
        'option1' => $this->t('Option 1'),
        'option2' => $this->t('Option 2'),
      ],
      '#ajax' => [
        'url' => Url::fromRoute('system.ajax'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
