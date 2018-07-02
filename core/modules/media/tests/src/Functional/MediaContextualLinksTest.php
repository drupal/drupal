<?php

namespace Drupal\Tests\media\Functional;

use Drupal\media\Entity\Media;

/**
 * Tests views contextual links on media items.
 *
 * @group media
 */
class MediaContextualLinksTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'contextual',
  ];

  /**
   * Tests contextual links.
   */
  public function testMediaContextualLinks() {
    // Create a media type.
    $mediaType = $this->createMediaType('test');

    // Create a media item.
    $media = Media::create([
      'bundle' => $mediaType->id(),
      'name' => 'Unnamed',
    ]);
    $media->save();

    $user = $this->drupalCreateUser([
      'administer media',
      'access contextual links',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('media/' . $media->id());
    $this->assertSession()->elementAttributeContains('css', 'div[data-contextual-id]', 'data-contextual-id', 'media:media=' . $media->id() . ':');
  }

}
