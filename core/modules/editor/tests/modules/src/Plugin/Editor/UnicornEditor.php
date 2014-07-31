<?php

/**
 * @file
 * Contains \Drupal\editor_test\Plugin\Editor\UnicornEditor.
 */

namespace Drupal\editor_test\Plugin\Editor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Plugin\EditorBase;
use Drupal\editor\Entity\Editor as EditorEntity;

/**
 * Defines a Unicorn-powered text editor for Drupal (for testing purposes).
 *
 * @Editor(
 *   id = "unicorn",
 *   label = @Translation("Unicorn Editor"),
 *   supports_content_filtering = TRUE,
 *   supports_inline_editing = TRUE,
 *   is_xss_safe = FALSE
 * )
 */
class UnicornEditor extends EditorBase {

  /**
   * {@inheritdoc}
   */
  function getDefaultSettings() {
    return array('ponies too' => TRUE);
  }

  /**
   * {@inheritdoc}
   */
  function settingsForm(array $form, FormStateInterface $form_state, EditorEntity $editor) {
    $form['foo'] = array(
      '#title' => t('Foo'),
      '#type' => 'textfield',
      '#default_value' => 'bar',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function getJSSettings(EditorEntity $editor) {
    $js_settings = array();
    $settings = $editor->getSettings();
    if ($settings['ponies too']) {
      $js_settings['ponyModeEnabled'] = TRUE;
    }
    return $js_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(EditorEntity $editor) {
    return array(
      'edit_test/unicorn',
    );
  }

}
