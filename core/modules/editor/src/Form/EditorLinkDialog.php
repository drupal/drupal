<?php

namespace Drupal\editor\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Provides a link dialog for text editors.
 *
 * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no
 * replacement.
 *
 * @see https://www.drupal.org/node/3291493
 *
 * @internal
 */
class EditorLinkDialog extends FormBase {

  /**
   * Constructs a form object for link dialog.
   */
  public function __construct() {
    @trigger_error(__NAMESPACE__ . '\EditorLinkDialog is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3291493', E_USER_DEPRECATED);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editor_link_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\editor\Entity\Editor $editor
   *   The text editor to which this dialog corresponds.
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, Editor $editor = NULL) {
    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    $user_input = $form_state->getUserInput();
    $input = $user_input['editor_object'] ?? [];

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="editor-link-dialog-form">';
    $form['#suffix'] = '</div>';

    // Everything under the "attributes" key is merged directly into the
    // generated link tag's attributes.
    $form['attributes']['href'] = [
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#default_value' => $input['href'] ?? '',
      '#maxlength' => 2048,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#editor-link-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
