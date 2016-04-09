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
  function getDefaultSettings() {
    return array('ponies_too' => TRUE);
  }

  /**
   * {@inheritdoc}
   */
  function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $form['ponies_too'] = array(
      '#title' => t('Pony mode'),
      '#type' => 'checkbox',
      '#default_value' => TRUE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function getJSSettings(Editor $editor) {
    $js_settings = array();
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
    return array(
      'editor_test/unicorn',
    );
  }

}
