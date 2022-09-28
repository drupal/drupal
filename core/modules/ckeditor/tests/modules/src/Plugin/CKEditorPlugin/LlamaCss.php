<?php

namespace Drupal\ckeditor_test\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\ckeditor\CKEditorPluginCssInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines a "LlamaCss" plugin, with an associated "llama" CSS.
 *
 * @CKEditorPlugin(
 *   id = "llama_css",
 *   label = @Translation("Llama CSS")
 * )
 */
class LlamaCss extends Llama implements CKEditorPluginButtonsInterface, CKEditorPluginCssInterface {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'LlamaCSS' => [
        'label' => $this->t('Insert Llama CSS'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCssFiles(Editor $editor) {
    return [
      $this->moduleList->getPath('ckeditor_test') . '/css/llama.css',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->moduleList->getPath('ckeditor_test') . '/js/llama_css.js';
  }

}
