<?php

/**
 * @file
 * Contains \Drupal\editor\Form\EditorImageDialog.
 */

namespace Drupal\editor\Form;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    if (!$image_element = $form_state->get('image_element')) {
      $user_input = $form_state->getUserInput();
      $image_element = isset($user_input['editor_object']) ? $user_input['editor_object'] : [];
      $form_state->set('image_element', $image_element);
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="editor-image-dialog-form">';
    $form['#suffix'] = '</div>';

    $editor = editor_load($filter_format->id());

    // Construct strings to use in the upload validators.
    $image_upload = $editor->getImageUploadSettings();
    if (!empty($image_upload['dimensions'])) {
      $max_dimensions = $image_upload['dimensions']['max_width'] . '×' . $image_upload['dimensions']['max_height'];
    }
    else {
      $max_dimensions = 0;
    }
    $max_filesize = min(Bytes::toInt($image_upload['max_size']), file_upload_max_size());

    $existing_file = isset($image_element['data-entity-uuid']) ? \Drupal::entityManager()->loadEntityByUuid('file', $image_element['data-entity-uuid']) : NULL;
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

    // The alt attribute is *required*, but we allow users to opt-in to empty
    // alt attributes for the very rare edge cases where that is valid by
    // specifying two double quotes as the alternative text in the dialog.
    // However, that *is* stored as an empty alt attribute, so if we're editing
    // an existing image (which means the src attribute is set) and its alt
    // attribute is empty, then we show that as two double quotes in the dialog.
    // @see https://www.drupal.org/node/2307647
    $alt = isset($image_element['alt']) ? $image_element['alt'] : '';
    if ($alt === '' && !empty($image_element['src'])) {
      $alt = '""';
    }
    $form['attributes']['alt'] = array(
      '#title' => $this->t('Alternative text'),
      '#placeholder' => $this->t('Short description for the visually impaired'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#required_error' => $this->t('Alternative text is required.<br />(Only in rare cases should this be left empty. To create empty alternative text, enter <code>""</code> — two double quotes without any content).'),
      '#default_value' => $alt,
      '#maxlength' => 2048,
    );
    $form['dimensions'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Image size'),
      '#attributes' => array('class' => array(
        'container-inline',
        'fieldgroup',
        'form-composite',
      )),
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
      '#field_suffix' => ' × ',
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

    // When Drupal core's filter_align is being used, the text editor may
    // offer the ability to change the alignment.
    if (isset($image_element['data-align']) && $filter_format->filters('filter_align')->status) {
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
    if (isset($image_element['hasCaption']) && $filter_format->filters('filter_caption')->status) {
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
        'callback' => '::submitForm',
        'event' => 'click',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Convert any uploaded files from the FID values to data-entity-uuid
    // attributes and set data-entity-type to 'file'.
    $fid = $form_state->getValue(array('fid', 0));
    if (!empty($fid)) {
      $file = file_load($fid);
      $file_url = file_create_url($file->getFileUri());
      // Transform absolute image URLs to relative image URLs: prevent problems
      // on multisite set-ups and prevent mixed content errors.
      $file_url = file_url_transform_relative($file_url);
      $form_state->setValue(array('attributes', 'src'), $file_url);
      $form_state->setValue(array('attributes', 'data-entity-uuid'), $file->uuid());
      $form_state->setValue(array('attributes', 'data-entity-type'), 'file');
    }

    // When the alt attribute is set to two double quotes, transform it to the
    // empty string: two double quotes signify "empty alt attribute". See above.
    if (trim($form_state->getValue(array('attributes', 'alt'))) === '""') {
      $form_state->setValue(array('attributes', 'alt'), '');
    }

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#editor-image-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
