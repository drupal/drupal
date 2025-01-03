<?php

namespace Drupal\media\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\media\Plugin\media\Source\OEmbedInterface;

/**
 * Theme suggestions for media.
 */
class MediaThemeSuggestionsHooks {

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_media')]
  public function themeSuggestionsMedia(array $variables): array {
    $suggestions = [];
    /** @var \Drupal\media\MediaInterface $media */
    $media = $variables['elements']['#media'];
    $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');

    $suggestions[] = 'media__' . $sanitized_view_mode;
    $suggestions[] = 'media__' . $media->bundle();
    $suggestions[] = 'media__' . $media->bundle() . '__' . $sanitized_view_mode;

    // Add suggestions based on the source plugin ID.
    $source = $media->getSource();
    if ($source instanceof DerivativeInspectionInterface) {
      $source_id = $source->getBaseId();
      $derivative_id = $source->getDerivativeId();
      if ($derivative_id) {
        $source_id .= '__derivative_' . $derivative_id;
      }
    }
    else {
      $source_id = $source->getPluginId();
    }
    $suggestions[] = "media__source_$source_id";

    // If the source plugin uses oEmbed, add a suggestion based on the provider
    // name, if available.
    if ($source instanceof OEmbedInterface) {
      $provider_id = $source->getMetadata($media, 'provider_name');
      if ($provider_id) {
        $provider_id = \Drupal::transliteration()->transliterate($provider_id);
        $provider_id = preg_replace('/[^a-z0-9_]+/', '_', mb_strtolower($provider_id));
        $suggestions[] = end($suggestions) . "__provider_$provider_id";
      }
    }

    return $suggestions;
  }

}
