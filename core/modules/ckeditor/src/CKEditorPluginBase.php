<?php

/**
 * @file
 * Contains \Drupal\ckeditor\Plugin\CKEditorPluginBase.
 */

namespace Drupal\ckeditor;

use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines a base CKEditor plugin implementation.
 *
 * No other CKEditor plugins can be internal, unless a different CKEditor build
 * than the one provided by Drupal core is used. Most CKEditor plugins don't
 * need to provide additional settings forms.
 *
 * This base assumes that your plugin has buttons that you want to be enabled
 * through the toolbar builder UI. It is still possible to also implement the
 * CKEditorPluginContextualInterface (for contextual enabling) and
 * CKEditorPluginConfigurableInterface interfaces (for configuring plugin
 * settings) though.
 *
 * NOTE: the Drupal plugin ID should correspond to the CKEditor plugin name.
 *
 * @see CKEditorPluginInterface
 * @see CKEditorPluginButtonsInterface
 * @see CKEditorPluginContextualInterface
 * @see CKEditorPluginConfigurableInterface
 */
abstract class CKEditorPluginBase extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * {@inheritdoc}
   */
  function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function getDependencies(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  function getLibraries(Editor $editor) {
    return array();
  }
}
