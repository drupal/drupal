<?php

namespace Drupal\ckeditor_test\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Llama" plugin, with a CKEditor "llama" feature.
 *
 * This feature does not correspond to a toolbar button. Because this plugin
 * does not implement the CKEditorPluginContextualInterface nor the
 * CKEditorPluginButtonsInterface interface, there is no way of actually loading
 * this plugin.
 *
 * @see \Drupal\ckeditor_test\Plugin\CKEditorPlugin\LlamaContextual
 * @see \Drupal\ckeditor_test\Plugin\CKEditorPlugin\LlamaButton
 * @see \Drupal\ckeditor_test\Plugin\CKEditorPlugin\LlamaContextualAndButton
 * @see \Drupal\ckeditor_test\Plugin\CKEditorPlugin\LlamaCss
 *
 * @CKEditorPlugin(
 *   id = "llama",
 *   label = @Translation("Llama")
 * )
 */
class Llama extends PluginBase implements CKEditorPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ckeditor_test') . '/js/llama.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
