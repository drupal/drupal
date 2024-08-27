<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Functional;

use Drupal\media\Entity\Media;

/**
 * Tests media template suggestions.
 *
 * @group media
 */
class MediaTemplateSuggestionsTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests template suggestions from media_theme_suggestions_media().
   */
  public function testMediaThemeHookSuggestions(): void {
    $media_type = $this->createMediaType('test', [
      'queue_thumbnail_downloads' => FALSE,
    ]);

    // Create media item to be rendered.
    $media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Unnamed',
    ]);
    $media->save();
    $view_mode = 'full';

    // Simulate theming of the media item.
    $build = \Drupal::entityTypeManager()->getViewBuilder('media')->view($media, $view_mode);

    $variables['elements'] = $build;
    $suggestions = \Drupal::moduleHandler()->invokeAll('theme_suggestions_media', [$variables]);
    $this->assertSame($suggestions, ['media__full', 'media__' . $media_type->id(), 'media__' . $media_type->id() . '__full', 'media__source_' . $media_type->getSource()->getPluginId()], 'Found expected media suggestions.');
  }

}
