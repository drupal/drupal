<?php

namespace Drupal\media_library\Hook;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for media_library.
 */
class MediaLibraryThemeHooks {

  /**
   * Implements hook_preprocess_media().
   */
  #[Hook('preprocess_media')]
  public function preprocessMedia(&$variables): void {
    if ($variables['view_mode'] === 'media_library') {
      /** @var \Drupal\media\MediaInterface $media */
      $media = $variables['media'];
      $variables['#cache']['contexts'][] = 'user.permissions';
      $rel = $media->access('edit') ? 'edit-form' : 'canonical';
      $variables['url'] = $media->toUrl($rel, [
        'language' => $media->language(),
      ]);
      $variables += [
        'preview_attributes' => new Attribute(),
        'metadata_attributes' => new Attribute(),
      ];
      $variables['status'] = $media->isPublished();
    }
  }

  /**
   * Implements hook_preprocess_views_view_fields().
   */
  #[Hook('preprocess_views_view_fields')]
  public function preprocessViewsViewFields(&$variables): void {
    // Add classes to media rendered entity field so it can be targeted for
    // JavaScript mouseover and click events.
    if ($variables['view']->id() === 'media_library' && isset($variables['fields']['rendered_entity'])) {
      if (isset($variables['fields']['rendered_entity']->wrapper_attributes)) {
        $variables['fields']['rendered_entity']->wrapper_attributes->addClass('js-click-to-select-trigger');
      }
    }
  }

}
