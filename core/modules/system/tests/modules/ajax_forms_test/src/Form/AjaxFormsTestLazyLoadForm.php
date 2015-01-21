<?php

/**
 * @file
 * Contains \Drupal\ajax_forms_test\Form\AjaxFormsTestLazyLoadForm.
 */

namespace Drupal\ajax_forms_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder: Builds a form that triggers a simple AJAX callback.
 */
class AjaxFormsTestLazyLoadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_forms_test_lazy_load_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // We attach a JavaScript setting, so that one of the generated AJAX
    // commands will be a settings command. We can then check the settings
    // command to ensure that the 'currentPath' setting is not part
    // of the Ajax response.
    $form['#attached']['drupalSettings']['test'] = 'currentPathUpdate';
    $form['add_files'] = array(
      '#title' => $this->t('Add files'),
      '#type' => 'checkbox',
      '#default_value' => FALSE,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => array(
        'wrapper' => 'ajax-forms-test-lazy-load-ajax-wrapper',
        'callback' => 'ajax_forms_test_lazy_load_form_ajax',
      ),
      '#prefix' => '<div id="ajax-forms-test-lazy-load-ajax-wrapper"></div>',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

}
