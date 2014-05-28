<?php

/**
 * @file
 * Contains \Drupal\ckeditor_test\Plugin\CKEditorPlugin\Llama.
 */

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
 * @see MetaContextual
 * @see MetaButton
 * @see MetaContextualAndButton
 *
 * @CKEditorPlugin(
 *   id = "llama",
 *   label = @Translation("Llama")
 * )
 */
class Llama extends PluginBase implements CKEditorPluginInterface {

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getDependencies().
   */
  function getDependencies(Editor $editor) {
    return array();
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getLibraries().
   */
  function getLibraries(Editor $editor) {
    return array();
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::isInternal().
   */
  function isInternal() {
    return FALSE;
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  function getFile() {
    return drupal_get_path('module', 'ckeditor_test') . '/js/llama.js';
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getConfig().
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}
