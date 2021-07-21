<?php

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
    return $this->getModulePath('ckeditor') . '/js/plugins/drupallink/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'core/drupal.ajax',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'drupalLink_dialogTitleAdd' => $this->t('Add Link'),
      'drupalLink_dialogTitleEdit' => $this->t('Edit Link'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $path = $this->getModulePath('ckeditor') . '/js/plugins/drupallink';
    return [
      'DrupalLink' => [
        'label' => $this->t('Link'),
        'image' => $path . '/icons/drupallink.png',
      ],
      'DrupalUnlink' => [
        'label' => $this->t('Unlink'),
        'image' => $path . '/icons/drupalunlink.png',
      ],
    ];
  }

}
