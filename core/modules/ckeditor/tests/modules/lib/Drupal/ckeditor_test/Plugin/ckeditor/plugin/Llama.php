<?php

/**
 * @file
 * Contains \Drupal\ckeditor_test\Plugin\ckeditor\plugin\Llama.
 */

namespace Drupal\ckeditor_test\Plugin\ckeditor\plugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\editor\Plugin\Core\Entity\Editor;

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
 * @Plugin(
 *   id = "llama",
 *   label = @Translation("Llama"),
 *   module = "ckeditor_test"
 * )
 */
class Llama extends PluginBase implements CKEditorPluginInterface {

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
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getButtons().
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}
