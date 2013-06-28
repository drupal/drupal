<?php

/**
 * @file
 * Contains \Drupal\ckeditor\Plugin\ckeditor\plugin\DrupalImage.
 */

namespace Drupal\ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\Annotation\CKEditorPlugin;
use Drupal\Core\Annotation\Translation;
use Drupal\editor\Plugin\Core\Entity\Editor;

/**
 * Defines the "drupalimage" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalimage",
 *   label = @Translation("Drupal image"),
 *   module = "ckeditor"
 * )
 */
class DrupalImage extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ckeditor') . '/js/plugins/drupalimage/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array(
      array('system', 'drupal.ajax'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array(
      'drupalImage_dialogTitleAdd' => t('Insert Image'),
      'drupalImage_dialogTitleEdit' => t('Edit Image'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'DrupalImage' => array(
        'label' => t('Image'),
        'image' => drupal_get_path('module', 'ckeditor') . '/js/plugins/drupalimage/image.png',
      ),
    );
  }

}
