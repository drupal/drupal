<?php

/**
 * @file
 * Contains \Drupal\ckeditor_test\Plugin\CKEditorPlugin\LlamaButton.
 */

namespace Drupal\ckeditor_test\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginButtonsInterface;

/**
 * Defines a "LlamaButton" plugin, with a toolbar builder-enabled "llama" feature.
 *
 * @CKEditorPlugin(
 *   id = "llama_button",
 *   label = @Translation("Llama Button")
 * )
 */
class LlamaButton extends Llama implements CKEditorPluginButtonsInterface {

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginButtonsInterface::getButtons().
   */
  function getButtons() {
    return array(
      'Llama' => array(
        'label' => t('Insert Llama'),
      ),
    );
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  function getFile() {
    return drupal_get_path('module', 'ckeditor_test') . '/js/llama_button.js';
  }

}
