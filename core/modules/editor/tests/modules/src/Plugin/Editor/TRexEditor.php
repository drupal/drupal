<?php

/**
 * @file
 * Contains \Drupal\editor_test\Plugin\Editor\TRexEditor.
 */

namespace Drupal\editor_test\Plugin\Editor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Plugin\EditorBase;
use Drupal\editor\Entity\Editor as EditorEntity;

/**
 * Defines a Tyrannosaurus-Rex powered text editor for testing purposes.
 *
 * @Editor(
 *   id = "trex",
 *   label = @Translation("TRex Editor"),
 *   supports_content_filtering = TRUE,
 *   supports_inline_editing = TRUE,
 *   is_xss_safe = FALSE,
 *   supported_element_types = {
 *     "textarea",
 *   }
 * )
 */
class TRexEditor extends EditorBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return array('stumpy_arms' => TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, EditorEntity $editor) {
    $form['stumpy_arms'] = array(
      '#title' => t('Stumpy arms'),
      '#type' => 'checkbox',
      '#default_value' => TRUE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(EditorEntity $editor) {
    $js_settings = array();
    $settings = $editor->getSettings();
    if ($settings['stumpy_arms']) {
      $js_settings['doMyArmsLookStumpy'] = TRUE;
    }
    return $js_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(EditorEntity $editor) {
    return array(
      'editor_test/trex',
    );
  }

}
