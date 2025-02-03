<?php

declare(strict_types=1);

namespace Drupal\ckeditor5_drupalelementstyle_test\Hook;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Core\Hook\Attribute\Hook;

// cspell:ignore drupalelementstyle

/**
 * Hook implementations for ckeditor5_drupalelementstyle_test.
 */
class Ckeditor5DrupalElementStyleTestHooks {

  /**
   * Implements hook_ckeditor5_plugin_info_alter().
   */
  #[Hook('ckeditor5_plugin_info_alter')]
  public function ckeditor5PluginInfoAlter(array &$plugin_definitions): void {
    // Update `media_mediaAlign`.
    assert($plugin_definitions['media_mediaAlign'] instanceof CKEditor5PluginDefinition);
    $media_align_plugin_definition = $plugin_definitions['media_mediaAlign']->toArray();
    $media_align_plugin_definition['ckeditor5']['config']['drupalMedia']['toolbar'] = [
      0 => [
        'name' => 'drupalMedia:align',
        'title' => 'Test title',
        'display' => 'splitButton',
        'items' => array_values(array_filter($media_align_plugin_definition['ckeditor5']['config']['drupalMedia']['toolbar'], function (string $toolbar_item): bool {
          return $toolbar_item !== '|';
        })),
        'defaultItem' => 'drupalElementStyle:align:breakText',
      ],
    ];
    $plugin_definitions['media_mediaAlign'] = new CKEditor5PluginDefinition($media_align_plugin_definition);
  }

}
