<?php

/**
 * @file
 * Contains \Drupal\editor_test\Plugin\Editor\UnicornEditor.
 */

namespace Drupal\editor_test\Plugin\Editor;

use Drupal\editor\Plugin\EditorBase;
use Drupal\editor\Annotation\Editor;
use Drupal\Core\Annotation\Translation;
use Drupal\editor\Entity\Editor as EditorEntity;

/**
 * Defines a Unicorn-powered text editor for Drupal.
 *
 * @Editor(
 *   id = "unicorn",
 *   label = @Translation("Unicorn Editor"),
 *   supports_inline_editing = TRUE
 * )
 */
class UnicornEditor extends EditorBase {

  /**
   * Implements \Drupal\editor\Plugin\EditPluginInterface::getDefaultSettings().
   */
  function getDefaultSettings() {
    return array('ponies too' => TRUE);
  }

  /**
   * Implements \Drupal\editor\Plugin\EditPluginInterface::settingsForm().
   */
  function settingsForm(array $form, array &$form_state, EditorEntity $editor) {
    $form['foo'] = array('#type' => 'textfield', '#default_value' => 'bar');
    return $form;
  }

  /**
   * Implements \Drupal\editor\Plugin\EditPluginInterface::getJSSettings().
   */
  function getJSSettings(EditorEntity $editor) {
    $settings = array();
    if ($editor->settings['ponies too']) {
      $settings['ponyModeEnabled'] = TRUE;
    }
    return $settings;
  }

  /**
   * Implements \Drupal\editor\Plugin\EditPluginInterface::getLibraries().
   */
  public function getLibraries(EditorEntity $editor) {
    return array(
      array('edit_test', 'unicorn'),
    );
  }

}
