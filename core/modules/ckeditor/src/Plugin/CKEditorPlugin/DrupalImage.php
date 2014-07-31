<?php

/**
 * @file
 * Contains \Drupal\ckeditor\Plugin\ckeditor\plugin\DrupalImage.
 */

namespace Drupal\ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "drupalimage" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalimage",
 *   label = @Translation("Image"),
 *   module = "ckeditor"
 * )
 */
class DrupalImage extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ckeditor') . '/js/plugins/drupalimage/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array(
      'core/drupal.ajax',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array(
      'drupalImage_dialogTitleAdd' => t('Insert Image'),
      'drupalImage_dialogTitleEdit' => t('Edit Image'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'DrupalImage' => array(
        'label' => t('Image'),
        'image' => drupal_get_path('module', 'ckeditor') . '/js/plugins/drupalimage/image.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\editor\Form\EditorImageDialog
   * @see editor_image_upload_settings_form()
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    form_load_include($form_state, 'inc', 'editor', 'editor.admin');
    $form['image_upload'] = editor_image_upload_settings_form($editor);
    $form['image_upload']['#attached']['library'][] = 'ckeditor/drupal.ckeditor.drupalimage.admin';
    $form['image_upload']['#element_validate'][] = array($this, 'validateImageUploadSettings');
    return $form;
  }

  /**
   * #element_validate handler for the "image_upload" element in settingsForm().
   *
   * Moves the text editor's image upload settings from the DrupalImage plugin's
   * own settings into $editor->image_upload.
   *
   * @see \Drupal\editor\Form\EditorImageDialog
   * @see editor_image_upload_settings_form()
   */
  function validateImageUploadSettings(array $element, FormStateInterface $form_state) {
    $settings = &$form_state['values']['editor']['settings']['plugins']['drupalimage']['image_upload'];
    $form_state['editor']->setImageUploadSettings($settings);
    unset($form_state['values']['editor']['settings']['plugins']['drupalimage']);
  }

}
