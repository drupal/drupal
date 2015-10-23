<?php

/**
 * @file
 * Contains \Drupal\ckeditor_test\Plugin\CKEditorPlugin\LlamaContextualAndButton.
 */

namespace Drupal\ckeditor_test\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines a "LlamaContextualAndbutton" plugin, with a contextually OR toolbar
 * builder-enabled "llama" feature.
 *
 * @CKEditorPlugin(
 *   id = "llama_contextual_and_button",
 *   label = @Translation("Contextual Llama With Button")
 * )
 */
class LlamaContextualAndButton extends Llama implements CKEditorPluginContextualInterface, CKEditorPluginButtonsInterface, CKEditorPluginConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  function isEnabled(Editor $editor) {
    // Automatically enable this plugin if the Strike button is enabled.
    $settings = $editor->getSettings();
    foreach ($settings['toolbar']['rows'] as $row) {
      foreach ($row as $group) {
        if (in_array('Strike', $group['items'])) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function getButtons() {
    return array(
      'Llama' => array(
        'label' => t('Insert Llama'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  function getFile() {
    return drupal_get_path('module', 'ckeditor_test') . '/js/llama_contextual_and_button.js';
  }

  /**
   * {@inheritdoc}
   */
  function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    // Defaults.
    $config = array('ultra_llama_mode' => FALSE);
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['llama_contextual_and_button'])) {
      $config = $settings['plugins']['llama_contextual_and_button'];
    }

    $form['ultra_llama_mode'] = array(
      '#title' => t('Ultra llama mode'),
      '#type' => 'checkbox',
      '#default_value' => $config['ultra_llama_mode'],
    );

    return $form;
  }

}
