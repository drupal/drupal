<?php

/**
 * @file
 * Contains \Drupal\ajax_forms_test\Form\AjaxFormsTestLazyLoadForm.
 */

namespace Drupal\ajax_forms_test\Form;

use Drupal\Core\Form\FormBase;

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
  public function buildForm(array $form, array &$form_state) {
    // We attach a JavaScript setting, so that one of the generated AJAX
    // commands will be a settings command. We can then check the settings
    // command to ensure that the 'currentPath' setting is not part
    // of the Ajax response.
    $form['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('test' => 'currentPathUpdate'),
    );
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
  public function submitForm(array &$form, array &$form_state) {
    if ($form_state['values']['add_files']) {
      $path = drupal_get_path('module', 'system');
      $attached = array(
        '#attached' => array(
          'css' => array(
            $path . '/css/system.admin.css' => array(),
          ),
          'js' => array(
            0 => array(
              'type' => 'setting',
              'data' => array('ajax_forms_test_lazy_load_form_submit' => 'executed'),
            ),
            $path . '/system.js' => array(),
          ),
        ),
      );
      drupal_render($attached);
    }
    $form_state['rebuild'] = TRUE;
  }

}
