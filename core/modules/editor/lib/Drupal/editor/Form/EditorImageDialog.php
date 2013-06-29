<?php

/**
 * @file
 * Contains \Drupal\editor\Form\EditorImageDialog.
 */

namespace Drupal\editor\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\filter\Plugin\Core\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Provides an image dialog for text editors.
 */
class EditorImageDialog implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'editor_image_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\filter\Plugin\Core\Entity\FilterFormat $filter_format
   *   The filter format for which this dialog corresponds.
   */
  public function buildForm(array $form, array &$form_state, FilterFormat $filter_format = NULL) {
    // The default values are set directly from $_POST, provided by the
    // editor plugin opening the dialog.
    $input = isset($form_state['input']['editor_object']) ? $form_state['input']['editor_object'] : array();

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = array('editor', 'drupal.editor.dialog');
    $form['#prefix'] = '<div id="editor-image-dialog-form">';
    $form['#suffix'] = '</div>';

    // Everything under the "attributes" key is merged directly into the
    // generated img tag's attributes.
    $form['attributes']['src'] = array(
      '#title' => t('URL'),
      '#type' => 'textfield',
      '#default_value' => isset($input['src']) ? $input['src'] : '',
      '#maxlength' => 2048,
      '#required' => TRUE,
    );

    $form['attributes']['alt'] = array(
      '#title' => t('Alternative text'),
      '#type' => 'textfield',
      '#default_value' => isset($input['alt']) ? $input['alt'] : '',
      '#maxlength' => 2048,
    );
    $form['dimensions'] = array(
      '#type' => 'item',
      '#title' => t('Image size'),
      '#field_prefix' => '<div class="container-inline">',
      '#field_suffix' => '</div>',
    );
    $form['dimensions']['width'] = array(
      '#title' => t('Width'),
      '#title_display' => 'invisible',
      '#type' => 'number',
      '#default_value' => isset($input['width']) ? $input['width'] : '',
      '#size' => 8,
      '#maxlength' => 8,
      '#min' => 1,
      '#max' => 99999,
      '#placeholder' => 'width',
      '#field_suffix' => ' x ',
      '#parents' => array('attributes', 'width'),
    );
    $form['dimensions']['height'] = array(
      '#title' => t('Height'),
      '#title_display' => 'invisible',
      '#type' => 'number',
      '#default_value' => isset($input['height']) ? $input['height'] : '',
      '#size' => 8,
      '#maxlength' => 8,
      '#min' => 1,
      '#max' => 99999,
      '#placeholder' => 'height',
      '#field_suffix' => 'pixels',
      '#parents' => array('attributes', 'height'),
    );

    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['save_modal'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
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
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $response = new AjaxResponse();

    if (form_get_errors()) {
      unset($form['#prefix'], $form['#suffix']);
      $output = drupal_render($form);
      $output = '<div>' . theme('status_messages') . $output . '</div>';
      $response->addCommand(new HtmlCommand('#editor-image-dialog-form', $output));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state['values']));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
