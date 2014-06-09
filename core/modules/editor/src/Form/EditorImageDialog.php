<?php

/**
 * @file
 * Contains \Drupal\editor\Form\EditorImageDialog.
 */

namespace Drupal\editor\Form;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Form\FormBase;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Provides an image dialog for text editors.
 */
class EditorImageDialog extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editor_image_dialog';
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
    if (!isset($form_state['image_element'])) {
      $form_state['image_element'] = isset($form_state['input']['editor_object']) ? $form_state['input']['editor_object'] : array();
    }
    $image_element = $form_state['image_element'];

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="editor-image-dialog-form">';
    $form['#suffix'] = '</div>';

    $editor = editor_load($filter_format->format);

    // Construct strings to use in the upload validators.
    $image_upload = $editor->getImageUploadSettings();
    if (!empty($image_upload['dimensions'])) {
      $max_dimensions = $image_upload['dimensions']['max_width'] . 'x' . $image_upload['dimensions']['max_height'];
    }
    else {
      $max_dimensions = 0;
    }
    $max_filesize = min(Bytes::toInt($image_upload['max_size']), file_upload_max_size());

    $existing_file = isset($image_element['data-editor-file-uuid']) ? entity_load_by_uuid('file', $image_element['data-editor-file-uuid']) : NULL;
    $fid = $existing_file ? $existing_file->id() : NULL;

    $form['fid'] = array(
      '#title' => $this->t('Image'),
      '#type' => 'managed_file',
      '#upload_location' => $image_upload['scheme'] . '://' . $image_upload['directory'],
      '#default_value' => $fid ? array($fid) : NULL,
      '#upload_validators' => array(
        'file_validate_extensions' => array('gif png jpg jpeg'),
        'file_validate_size' => array($max_filesize),
        'file_validate_image_resolution' => array($max_dimensions),
      ),
      '#required' => TRUE,
    );

    $form['attributes']['src'] = array(
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#default_value' => isset($image_element['src']) ? $image_element['src'] : '',
      '#maxlength' => 2048,
      '#required' => TRUE,
    );

    // If the editor has image uploads enabled, show a managed_file form item,
    // otherwise show a (file URL) text form item.
    if ($image_upload['status']) {
      $form['attributes']['src']['#access'] = FALSE;
      $form['attributes']['src']['#required'] = FALSE;
    }
    else {
      $form['fid']['#access'] = FALSE;
      $form['fid']['#required'] = FALSE;
    }

    $form['attributes']['alt'] = array(
      '#title' => $this->t('Alternative text'),
      '#type' => 'textfield',
      '#default_value' => isset($image_element['alt']) ? $image_element['alt'] : '',
      '#maxlength' => 2048,
    );
    $form['dimensions'] = array(
      '#type' => 'item',
      '#title' => $this->t('Image size'),
      '#field_prefix' => '<div class="container-inline">',
      '#field_suffix' => '</div>',
    );
    $form['dimensions']['width'] = array(
      '#title' => $this->t('Width'),
      '#title_display' => 'invisible',
      '#type' => 'number',
      '#default_value' => isset($image_element['width']) ? $image_element['width'] : '',
      '#size' => 8,
      '#maxlength' => 8,
      '#min' => 1,
      '#max' => 99999,
      '#placeholder' => $this->t('width'),
      '#field_suffix' => ' x ',
      '#parents' => array('attributes', 'width'),
    );
    $form['dimensions']['height'] = array(
      '#title' => $this->t('Height'),
      '#title_display' => 'invisible',
      '#type' => 'number',
      '#default_value' => isset($image_element['height']) ? $image_element['height'] : '',
      '#size' => 8,
      '#maxlength' => 8,
      '#min' => 1,
      '#max' => 99999,
      '#placeholder' => $this->t('height'),
      '#field_suffix' => $this->t('pixels'),
      '#parents' => array('attributes', 'height'),
    );

    // When Drupal core's filter_caption is being used, the text editor may
    // offer the ability to change the alignment.
    if (isset($image_element['data-align'])) {
      $form['align'] = array(
        '#title' => $this->t('Align'),
        '#type' => 'radios',
        '#options' => array(
          'none' => $this->t('None'),
          'left' => $this->t('Left'),
          'center' => $this->t('Center'),
          'right' => $this->t('Right'),
        ),
        '#default_value' => $image_element['data-align'] === '' ? 'none' : $image_element['data-align'],
        '#wrapper_attributes' => array('class' => array('container-inline')),
        '#attributes' => array('class' => array('container-inline')),
        '#parents' => array('attributes', 'data-align'),
      );
    }

    // When Drupal core's filter_caption is being used, the text editor may
    // offer the ability to in-place edit the image's caption: show a toggle.
    if (isset($image_element['hasCaption'])) {
      $form['caption'] = array(
        '#title' => $this->t('Caption'),
        '#type' => 'checkbox',
        '#default_value' => $image_element['hasCaption'] === 'true',
        '#parents' => array('attributes', 'hasCaption'),
      );
    }

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

    // Convert any uploaded files from the FID values to data-editor-file-uuid
    // attributes.
    if (!empty($form_state['values']['fid'][0])) {
      $file = file_load($form_state['values']['fid'][0]);
      $file_url = file_create_url($file->getFileUri());
      // Transform absolute image URLs to relative image URLs: prevent problems
      // on multisite set-ups and prevent mixed content errors.
      $file_url = file_url_transform_relative($file_url);
      $form_state['values']['attributes']['src'] = $file_url;
      $form_state['values']['attributes']['data-editor-file-uuid'] = $file->uuid();
    }

    if (form_get_errors($form_state)) {
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
