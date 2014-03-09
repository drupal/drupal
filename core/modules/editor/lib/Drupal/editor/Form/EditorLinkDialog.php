<?php

/**
 * @file
 * Contains \Drupal\editor\Form\EditorLinkDialog.
 */

namespace Drupal\editor\Form;

use Drupal\Core\Form\FormBase;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Provides a link dialog for text editors.
 */
class EditorLinkDialog extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editor_link_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\filter\Entity\FilterFormat $filter_format
   *   The filter format for which this dialog corresponds.
   */
  public function buildForm(array $form, array &$form_state, FilterFormat $filter_format = NULL) {
    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    $input = isset($form_state['input']['editor_object']) ? $form_state['input']['editor_object'] : array();

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="editor-link-dialog-form">';
    $form['#suffix'] = '</div>';

    // Everything under the "attributes" key is merged directly into the
    // generated link tag's attributes.
    $form['attributes']['href'] = array(
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#default_value' => isset($input['href']) ? $input['href'] : '',
      '#maxlength' => 2048,
    );

    $form['attributes']['target'] = array(
      '#title' => $this->t('Open in new window'),
      '#type' => 'checkbox',
      '#default_value' => !empty($input['target']),
      '#return_value' => '_blank',
    );

    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['save_modal'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => array(),
      '#ajax' => array(
        'callback' => array($this, 'submitForm'),
        'event' => 'click',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $response = new AjaxResponse();

    if (form_get_errors($form_state)) {
      unset($form['#prefix'], $form['#suffix']);
      $status_messages = array('#theme' => 'status_messages');
      $output = drupal_render($form);
      $output = '<div>' . drupal_render($status_messages) . $output . '</div>';
      $response->addCommand(new HtmlCommand('#editor-link-dialog-form', $output));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state['values']));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
