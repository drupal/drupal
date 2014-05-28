<?php

/**
 * @file
 * Contains \Drupal\ckeditor\Plugin\ckeditor\plugin\DrupalLink.
 */

namespace Drupal\ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "drupallink" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupallink",
 *   label = @Translation("Drupal link"),
 *   module = "ckeditor"
 * )
 */
class DrupalLink extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ckeditor') . '/js/plugins/drupallink/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array(
      'core/drupal.ajax',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array(
      'drupalLink_dialogTitleAdd' => t('Add Link'),
      'drupalLink_dialogTitleEdit' => t('Edit Link'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $path = drupal_get_path('module', 'ckeditor') . '/js/plugins/drupallink';
    return array(
      'DrupalLink' => array(
        'label' => t('Link'),
        'image' => $path . '/link.png',
      ),
      'DrupalUnlink' => array(
        'label' => t('Unlink'),
        'image' => $path . '/unlink.png',
      ),
    );
  }

}
