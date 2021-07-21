<?php

namespace Drupal\image\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginManager;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "drupalimagestyle" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalimagestyle",
 *   label = @Translation("Image style"),
 *   module = "ckeditor"
 * )
 */
class DrupalImageStyle extends CKEditorPluginBase implements CKEditorPluginContextualInterface {

  /**
   * {@inheritdoc}
   */
  public function getFile(): string {
    return 'core/modules/image/js/plugins/drupalimagestyle/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons(): array {
    // Do not provide button. The drupalimagestyle plugin provides the button
    // for us.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor): bool {
    if (!$editor->hasAssociatedFilterFormat()) {
      return FALSE;
    }

    // Automatically enable this plugin if the text format associated with this
    // text editor uses the filter_image_style filter and the DrupalImage button
    // is enabled.
    $format = $editor->getFilterFormat();
    if ($format->filters('filter_image_style')->status) {
      $toolbarButtons = CKEditorPluginManager::getEnabledButtons($editor);
      return in_array('DrupalImage', $toolbarButtons, TRUE);
    }

    return FALSE;
  }

}
