<?php

namespace Drupal\ajax_test\Form;

use Drupal\ajax_test\Controller\AjaxTestController;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Dummy form for testing DialogRenderer with _form routes.
 *
 * @internal
 */
class AjaxTestDialogForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_test_dialog_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // In order to use WebTestBase::drupalPostAjaxForm() to POST from a link, we need
    // to have a dummy field we can set in WebTestBase::drupalPostForm() else it won't
    // submit anything.
    $form['textfield'] = [
      '#type' => 'hidden'
    ];
    $form['button1'] = [
      '#type' => 'submit',
      '#name' => 'button1',
      '#value' => 'Button 1 (modal)',
      '#ajax' => [
        'callback' => '::modal',
      ],
    ];
    $form['button2'] = [
      '#type' => 'submit',
      '#name' => 'button2',
      '#value' => 'Button 2 (non-modal)',
      '#ajax' => [
        'callback' => '::nonModal',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('ajax_test.dialog_contents');
  }


  /**
   * AJAX callback handler for AjaxTestDialogForm.
   */
  public function modal(&$form, FormStateInterface $form_state) {
    return $this->dialog(TRUE);
  }

  /**
   * AJAX callback handler for AjaxTestDialogForm.
   */
  public function nonModal(&$form, FormStateInterface $form_state) {
    return $this->dialog(FALSE);
  }


  /**
   * Util to render dialog in ajax callback.
   *
   * @param bool $is_modal
   *   (optional) TRUE if modal, FALSE if plain dialog. Defaults to FALSE.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response object.
   */
  protected function dialog($is_modal = FALSE) {
    $content = AjaxTestController::dialogContents();
    $response = new AjaxResponse();
    $title = $this->t('AJAX Dialog & contents');

    // Attach the library necessary for using the Open(Modal)DialogCommand and
    // set the attachments for this Ajax response.
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';

    if ($is_modal) {
      $response->addCommand(new OpenModalDialogCommand($title, $content));
    }
    else {
      $selector = '#ajax-test-dialog-wrapper-1';
      $response->addCommand(new OpenDialogCommand($selector, $title, $content));
    }
    return $response;
  }

}
