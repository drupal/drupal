<?php

namespace Drupal\editor_test\Plugin\Editor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\editor\Plugin\EditorBase;

/**
 * Defines a Unicorn-powered text editor for Drupal (for testing purposes).
 *
 * @Editor(
 *   id = "unicorn",
 *   label = @Translation("Unicorn Editor"),
 *   supports_content_filtering = TRUE,
 *   supports_inline_editing = TRUE,
 *   is_xss_safe = FALSE,
 *   supported_element_types = {
 *     "textarea",
 *     "textfield",
 *   }
 * )
 */
class UnicornEditor extends EditorBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return ['ponies_too' => TRUE];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['ponies_too'] = [
      '#title' => $this->t('Pony mode'),
      '#type' => 'checkbox',
      '#default_value' => TRUE,
    ];
    $form_state->loadInclude('editor', 'admin.inc');
    $form['image_upload'] = editor_image_upload_settings_form($form_state->get('editor'));
    $form['image_upload']['#element_validate'][] = [$this, 'validateImageUploadSettings'];
    return $form;
  }

  /**
   * #element_validate handler for "image_upload" in buildConfigurationForm().
   *
   * Moves the text editor's image upload settings into $editor->image_upload.
   *
   * @see editor_image_upload_settings_form()
   */
  public function validateImageUploadSettings(array $element, FormStateInterface $form_state) {
    $settings = &$form_state->getValue(['editor', 'settings', 'image_upload']);
    $form_state->get('editor')->setImageUploadSettings($settings);
    $form_state->unsetValue(['editor', 'settings', 'image_upload']);
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(Editor $editor) {
    $js_settings = [];
    $settings = $editor->getSettings();
    if ($settings['ponies_too']) {
      $js_settings['ponyModeEnabled'] = TRUE;
    }
    return $js_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'editor_test/unicorn',
    ];
  }

}
