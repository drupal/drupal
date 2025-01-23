<?php

declare(strict_types=1);

namespace Drupal\ajax_forms_test\Form;

use Drupal\ajax_forms_test\Callbacks;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder: Builds a form that triggers a simple AJAX callback.
 *
 * @internal
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
    $form['add_files'] = [
      '#title' => $this->t('Add files'),
      '#type' => 'checkbox',
      '#default_value' => FALSE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'wrapper' => 'ajax-forms-test-lazy-load-ajax-wrapper',
        'callback' => [Callbacks::class, 'lazyLoadFormAjax'],
      ],
      '#prefix' => '<div id="ajax-forms-test-lazy-load-ajax-wrapper"></div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

}
