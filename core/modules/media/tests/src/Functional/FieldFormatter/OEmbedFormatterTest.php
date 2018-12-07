<?php

namespace Drupal\Tests\media\Functional\FieldFormatter;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
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
    'field_ui',
    'link',
    'media_test_oembed',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->lockHttpClientToFixtures();

    \Drupal::configFactory()
      ->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save(TRUE);

    $this->container->get('router.builder')->rebuild();
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
   * Tests that oEmbed media types' display can be configured correctly.
   */
  public function testDisplayConfiguration() {
    $account = $this->drupalCreateUser(['administer media display']);
    $this->drupalLogin($account);

    $media_type = $this->createMediaType('oembed:video');
    $this->drupalGet('/admin/structure/media/manage/' . $media_type->id() . '/display');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    // Test that the formatter doesn't try to check applicability for fields
    // which do not have a specific target bundle.
    // @see https://www.drupal.org/project/drupal/issues/2976795.
    $assert->pageTextNotContains('Can only flip STRING and INTEGER values!');
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
    $account = $this->drupalCreateUser(['view media']);
    $this->drupalLogin($account);

    $media_type = $this->createMediaType('oembed:video');

    $source = $media_type->getSource();
    $source_field = $source->getSourceFieldDefinition($media_type);

    EntityViewDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => $media_type->id(),
      'mode' => 'full',
      'status' => TRUE,
    ])->removeComponent('thumbnail')
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
