<?php

/**
 * @file
 * Contains \Drupal\editor\Form\EditorImageDialog.
 */

namespace Drupal\editor\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\file\FileInterface;

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
   * @param \Drupal\filter\Entity\FilterFormat $filter_format
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

    $editor = editor_load($filter_format->format);

    // Construct strings to use in the upload validators.
    if (!empty($editor->image_upload['dimensions'])) {
      $max_dimensions = $editor->image_upload['dimensions']['max_width'] . 'x' . $editor->image_upload['dimensions']['max_height'];
    }
    else {
      $max_dimensions = 0;
    }
    $max_filesize = min(parse_size($editor->image_upload['max_size']), file_upload_max_size());

    $existing_file = isset($input['data-editor-file-uuid']) ? entity_load_by_uuid('file', $input['data-editor-file-uuid']) : NULL;
    $fid = $existing_file ? $existing_file->id() : NULL;

    $form['fid'] = array(
      '#title' => t('Image'),
      '#type' => 'managed_file',
      '#upload_location' => $editor->image_upload['scheme'] . '://' .$editor->image_upload['directory'],
      '#default_value' => $fid ? array($fid) : NULL,
      '#upload_validators' => array(
        'file_validate_extensions' => array('gif png jpg jpeg'),
        'file_validate_size' => array($max_filesize),
        'file_validate_image_resolution' => array($max_dimensions),
      ),
      '#required' => TRUE,
    );

    $form['attributes']['src'] = array(
     '#title' => t('URL'),
     '#type' => 'textfield',
     '#default_value' => isset($input['src']) ? $input['src'] : '',
     '#maxlength' => 2048,
     '#required' => TRUE,
    );

    // If the editor has image uploads enabled, show a managed_file form item,
    // otherwise show a (file URL) text form item.
    if ($editor->image_upload['status'] === '1') {
      $form['attributes']['src']['#access'] = FALSE;
    }
    else {
      $form['fid']['#access'] = FALSE;
    }

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

    // Convert any uploaded files from the FID values to data-editor-file-uuid
    // attributes.
    if (!empty($form_state['values']['fid'][0])) {
      $file = file_load($form_state['values']['fid'][0]);
      $form_state['values']['attributes']['src'] = file_create_url($file->getFileUri());
      $form_state['values']['attributes']['data-editor-file-uuid'] = $file->uuid();
    }

    if (form_get_errors()) {
      unset($form['#prefix'], $form['#suffix']);
      $status_messages = array('#theme' => 'status_messages');
      $output = drupal_render($form);
      $output = '<div>' . drupal_render($status_messages) . $output . '</div>';
      $response->addCommand(new HtmlCommand('#editor-image-dialog-form', $output));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state['values']));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
