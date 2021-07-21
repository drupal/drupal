<?php

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
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'Llama' => [
        'label' => t('Insert Llama'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->moduleList->getPath('ckeditor_test') . '/js/llama_button.js';
  }

}
