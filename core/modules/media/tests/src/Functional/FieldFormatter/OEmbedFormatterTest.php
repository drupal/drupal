<?php

namespace Drupal\Tests\media\Functional\FieldFormatter;

use Drupal\media\Entity\Media;
use Drupal\media_test_oembed\Controller\ResourceController;
use Drupal\media_test_oembed\UrlResolver;
use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\Tests\media\Traits\OEmbedTestTrait;

/**
 * @covers \Drupal\media\Plugin\Field\FieldFormatter\OEmbedFormatter
 *
 * @group media
 */
class OEmbedFormatterTest extends MediaFunctionalTestBase {

  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'link',
    'media_test_oembed',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['view media']));
    $this->lockHttpClientToFixtures();
  }

  /**
   * Data provider for testRender().
   *
   * @see ::testRender()
   *
   * @return array
   */
  public function providerRender() {
    return [
      'Vimeo video' => [
        'https://vimeo.com/7073899',
        'video_vimeo.json',
        [],
        [
          'iframe' => [
            'src' => '/media/oembed?url=https%3A//vimeo.com/7073899',
            'width' => 480,
            'height' => 360,
          ],
        ],
      ],
      'Vimeo video, resized' => [
        'https://vimeo.com/7073899',
        'video_vimeo.json?maxwidth=100&maxheight=100',
        ['max_width' => 100, 'max_height' => 100],
        [
          'iframe' => [
            'src' => '/media/oembed?url=https%3A//vimeo.com/7073899',
            'width' => 100,
            'height' => 100,
          ],
        ],
      ],
      'tweet' => [
        'https://twitter.com/drupaldevdays/status/935643039741202432',
        'rich_twitter.json',
        [],
        [
          'iframe' => [
            'src' => '/media/oembed?url=https%3A//twitter.com/drupaldevdays/status/935643039741202432',
            'width' => 550,
            'height' => 360,
          ],
        ],
      ],
      'Flickr photo' => [
        'https://www.flickr.com/photos/amazeelabs/26497866357',
        'photo_flickr.json',
        [],
        [
          'img' => [
            'src' => '/core/misc/druplicon.png',
            'width' => 88,
            'height' => 100,
          ],
        ],
      ],
    ];
  }

  /**
   * Tests the oEmbed field formatter.
   *
   * @param string $url
   *   The canonical URL of the media asset to test.
   * @param string $resource_url
   *   The oEmebd resource URL of the media asset to test.
   * @param mixed $formatter_settings
   *   Settings for the oEmbed field formatter.
   * @param array $selectors
   *   An array of arrays. Each key is a CSS selector targeting an element in
   *   the rendered output, and each value is an array of attributes, keyed by
   *   name, that the element is expected to have.
   *
   * @dataProvider providerRender
   */
  public function testRender($url, $resource_url, array $formatter_settings, array $selectors) {
    $media_type = $this->createMediaType([], 'oembed:video');

    $source = $media_type->getSource();
    $source_field = $source->getSourceFieldDefinition($media_type);

    entity_get_display('media', $media_type->id(), 'full')
      ->removeComponent('thumbnail')
      ->setComponent($source_field->getName(), [
        'type' => 'oembed',
        'settings' => $formatter_settings,
      ])
      ->save();

    $this->hijackProviderEndpoints();

    ResourceController::setResourceUrl($url, $this->getFixturesDirectory() . '/' . $resource_url);
    UrlResolver::setEndpointUrl($url, $resource_url);

    $entity = Media::create([
      'bundle' => $media_type->id(),
      $source_field->getName() => $url,
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    foreach ($selectors as $selector => $attributes) {
      foreach ($attributes as $attribute => $value) {
        $assert->elementAttributeContains('css', $selector, $attribute, $value);
      }
    }
  }

}
