<?php

namespace Drupal\editor_test\Plugin\Editor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\editor\Plugin\EditorBase;

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
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
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
  public function getJSSettings(Editor $editor) {
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
  public function getLibraries(Editor $editor) {
    return array(
      'editor_test/trex',
    );
  }

}
