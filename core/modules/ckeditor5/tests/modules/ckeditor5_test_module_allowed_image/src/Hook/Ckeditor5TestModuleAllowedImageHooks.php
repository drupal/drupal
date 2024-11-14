<?php

declare(strict_types=1);

namespace Drupal\ckeditor5_test_module_allowed_image\Hook;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for ckeditor5_test_module_allowed_image.
 */
class Ckeditor5TestModuleAllowedImageHooks {

  /**
   * Implements hook_ckeditor5_plugin_info_alter().
   */
  #[Hook('ckeditor5_plugin_info_alter')]
  public function ckeditor5PluginInfoAlter(array &$plugin_definitions) : void {
    // Add a custom file type to the image upload plugin. Note that 'svg+xml'
    // below should be an IANA image media type Name, with the "image/" prefix
    // omitted. In other words: a subtype of type image.
    // @see https://www.iana.org/assignments/media-types/media-types.xhtml#image
    // @see https://ckeditor.com/docs/ckeditor5/latest/api/module_image_imageconfig-ImageUploadConfig.html#member-types
    $image_upload_plugin_definition = $plugin_definitions['ckeditor5_imageUpload']->toArray();
    $image_upload_plugin_definition['ckeditor5']['config']['image']['upload']['types'][] = 'svg+xml';
    $plugin_definitions['ckeditor5_imageUpload'] = new CKEditor5PluginDefinition($image_upload_plugin_definition);
  }

}
