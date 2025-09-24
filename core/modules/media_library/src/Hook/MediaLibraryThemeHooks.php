<?php

namespace Drupal\media_library\Hook;

use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for media_library.
 */
class MediaLibraryThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'media__media_library' => [
        'base hook' => 'media',
      ],
      'media_library_wrapper' => [
        'render element' => 'element',
        'initial preprocess' => static::class . ':preprocessMediaLibraryWrapper',
      ],
      'media_library_item' => [
        'render element' => 'element',
        'initial preprocess' => static::class . ':preprocessMediaLibraryItem',
      ],
    ];
  }

  /**
   * Prepares variables for the media library modal dialog.
   *
   * Default template: media-library-wrapper.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties of the element.
   *     Properties used: #menu, #content.
   */
  public function preprocessMediaLibraryWrapper(array &$variables): void {
    $variables['menu'] = &$variables['element']['menu'];
    $variables['content'] = &$variables['element']['content'];
  }

  /**
   * Prepares variables for a selected media item.
   *
   * Default template: media-library-item.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties and children of
   *     the element.
   */
  public function preprocessMediaLibraryItem(array &$variables): void {
    $element = &$variables['element'];
    foreach (Element::children($element) as $key) {
      $variables['content'][$key] = $element[$key];
    }
  }

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

  /**
   * Implements hook_preprocess_HOOK() for the 'media_library' view.
   */
  #[Hook('preprocess_views_view__media_library')]
  public function preprocessViewsViewMediaLibrary(array &$variables): void {
    $variables['attributes']['data-view-display-id'] = $variables['view']->current_display;
  }

}
