<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * @coversDefaultClass \Drupal\media\Plugin\Filter\MediaEmbed
 * @group media
 */
class MediaEmbedFilterTest extends MediaEmbedFilterTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // @see media_test_embed_entity_access()
    // @see media_test_embed_entity_view_alter()
    'media_test_embed',
  ];

  /**
   * Ensures media entities are rendered correctly.
   *
   * @dataProvider providerTestBasics
   */
  public function testBasics(array $embed_attributes, $expected_view_mode, array $expected_attributes, CacheableMetadata $expected_cacheability) {
    $content = $this->createEmbedCode($embed_attributes);

    $result = $this->applyFilter($content);

    $this->assertCount(1, $this->cssSelect('div[data-media-embed-test-view-mode="' . $expected_view_mode . '"]'));
    $this->assertHasAttributes($this->cssSelect('div[data-media-embed-test-view-mode="' . $expected_view_mode . '"]')[0], $expected_attributes);
    $this->assertEqualsCanonicalizing($expected_cacheability->getCacheTags(), $result->getCacheTags());
    $this->assertEqualsCanonicalizing($expected_cacheability->getCacheContexts(), $result->getCacheContexts());
    $this->assertSame($expected_cacheability->getCacheMaxAge(), $result->getCacheMaxAge());
    $this->assertSame(['library'], array_keys($result->getAttachments()));
    $this->assertSame(['media/filter.caption'], $result->getAttachments()['library']);
  }

  /**
   * Data provider for testBasics().
   */
  public function providerTestBasics() {
    $default_cacheability = (new CacheableMetadata())
      ->setCacheTags([
        '_media_test_embed_filter_access:media:1',
        '_media_test_embed_filter_access:user:2',
        'config:image.style.thumbnail',
        'file:1',
        'media:1',
        'media_view',
        'user:2',
      ])
      ->setCacheContexts(['timezone', 'user.permissions'])
      ->setCacheMaxAge(Cache::PERMANENT);

    return [
      'data-entity-uuid only ⇒ default view mode used' => [
        [
          'data-entity-type' => 'media',
          'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
        ],
        EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE,
        [],
        $default_cacheability,
      ],
      'data-entity-uuid + data-view-mode=full ⇒ specified view mode used' => [
        [
          'data-entity-type' => 'media',
          'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
          'data-view-mode' => 'full',
        ],
        'full',
        [],
        $default_cacheability,
      ],
      'data-entity-uuid + data-view-mode=default ⇒ specified view mode used' => [
        [
          'data-entity-type' => 'media',
          'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
          'data-view-mode' => EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE,
        ],
        EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE,
        [],
        $default_cacheability,
      ],
      'data-entity-uuid + data-view-mode=foobar ⇒ specified view mode used' => [
        [
          'data-entity-type' => 'media',
          'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
          'data-view-mode' => 'foobar',
        ],
        'foobar',
        [],
        (new CacheableMetadata())
          ->setCacheTags([
            '_media_test_embed_filter_access:media:1',
            'config:image.style.medium',
            'file:1',
            'media:1',
            'media_view',
          ])
          ->setCacheContexts(['user.permissions'])
          ->setCacheMaxAge(Cache::PERMANENT),
      ],
      'custom attributes are retained' => [
        [
          'data-foo' => 'bar',
          'foo' => 'bar',
          'data-entity-type' => 'media',
          'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
        ],
        EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE,
        [
          'data-foo' => 'bar',
          'foo' => 'bar',
        ],
        $default_cacheability,
      ],
    ];
  }

  /**
   * Tests that entity access is respected by embedding an unpublished entity.
   *
   * @dataProvider providerAccessUnpublished
   */
  public function testAccessUnpublished($allowed_to_view_unpublished, $expected_rendered, CacheableMetadata $expected_cacheability, array $expected_attachments) {
    // Unpublish the embedded entity so we can test variations in behavior.
    $this->embeddedEntity->setUnpublished()->save();

    // Are we testing as a user who is allowed to view the embedded entity?
    if ($allowed_to_view_unpublished) {
      $this->container->get('current_user')
        ->addRole($this->drupalCreateRole(['view own unpublished media']));
    }

    $content = $this->createEmbedCode([
      'data-entity-type' => 'media',
      'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
    ]);
    $result = $this->applyFilter($content);

    if (!$expected_rendered) {
      $this->assertEmpty($this->getRawContent());
    }
    else {
      $this->assertCount(1, $this->cssSelect('div[data-media-embed-test-view-mode="default"]'));
    }

    $this->assertEqualsCanonicalizing($expected_cacheability->getCacheTags(), $result->getCacheTags());
    $this->assertEqualsCanonicalizing($expected_cacheability->getCacheContexts(), $result->getCacheContexts());
    $this->assertSame($expected_cacheability->getCacheMaxAge(), $result->getCacheMaxAge());
    $this->assertSame($expected_attachments, $result->getAttachments());
  }

  /**
   * Data provider for testAccessUnpublished().
   */
  public function providerAccessUnpublished() {
    return [
      'user cannot access embedded media' => [
        FALSE,
        FALSE,
        (new CacheableMetadata())
          ->setCacheTags([
            '_media_test_embed_filter_access:media:1',
            'media:1',
            'media_view',
          ])
          ->setCacheContexts(['user.permissions'])
          ->setCacheMaxAge(Cache::PERMANENT),
        [],
      ],
      'user can access embedded media' => [
        TRUE,
        TRUE,
        (new CacheableMetadata())
          ->setCacheTags([
            '_media_test_embed_filter_access:media:1',
            '_media_test_embed_filter_access:user:2',
            'config:image.style.thumbnail',
            'file:1',
            'media:1',
            'media_view',
            'user:2',
          ])
          ->setCacheContexts(['timezone', 'user', 'user.permissions'])
          ->setCacheMaxAge(Cache::PERMANENT),
        ['library' => ['media/filter.caption']],
      ],
    ];
  }

  /**
   * @covers ::applyPerEmbedMediaOverrides
   * @dataProvider providerOverridesAltAndTitle
   */
  public function testOverridesAltAndTitle($title_field_property_enabled, array $expected_title_attributes) {
    // The `alt` field property is enabled by default, the `title` one is not.
    if ($title_field_property_enabled) {
      $source_field = FieldConfig::load('media.image.field_media_image');
      $source_field->setSetting('title_field', TRUE);
      $source_field->save();
    }

    $base = [
      'data-entity-type' => 'media',
      'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
    ];
    $input = $this->createEmbedCode($base);
    $input .= $this->createEmbedCode([
      'alt' => 'alt 1',
      'title' => 'title 1',
    ] + $base);
    $input .= $this->createEmbedCode([
      'alt' => 'alt 2',
      'title' => 'title 2',
    ] + $base);
    $input .= $this->createEmbedCode([
      'alt' => 'alt 3',
      'title' => 'title 3',
    ] + $base);
    $input .= $this->createEmbedCode([
      'alt' => '""',
      'title' => 'title 4',
    ] + $base);

    $this->applyFilter($input);

    $img_nodes = $this->cssSelect('img');
    $this->assertCount(5, $img_nodes);
    $this->assertHasAttributes($img_nodes[0], [
      'alt' => 'default alt',
      'title' => $expected_title_attributes[0],
    ]);
    $this->assertHasAttributes($img_nodes[1], [
      'alt' => 'alt 1',
      'title' => $expected_title_attributes[1],
    ]);
    $this->assertHasAttributes($img_nodes[2], [
      'alt' => 'alt 2',
      'title' => $expected_title_attributes[2],
    ]);
    $this->assertHasAttributes($img_nodes[3], [
      'alt' => 'alt 3',
      'title' => $expected_title_attributes[3],
    ]);
    $this->assertHasAttributes($img_nodes[4], [
      'alt' => '',
      'title' => $expected_title_attributes[4],
    ]);
  }

  /**
   * Data provider for testOverridesAltAndTitle().
   */
  public function providerOverridesAltAndTitle() {
    return [
      '`title` field property disabled ⇒ `title` is not overridable' => [
        FALSE,
        [NULL, NULL, NULL, NULL, NULL],
      ],
      '`title` field property enabled ⇒ `title` is overridable' => [
        TRUE,
        [NULL, 'title 1', 'title 2', 'title 3', 'title 4'],
      ],
    ];
  }

  /**
   * Tests the indicator for missing entities.
   *
   * @dataProvider providerMissingEntityIndicator
   */
  public function testMissingEntityIndicator($uuid, array $filter_ids, array $additional_attributes) {
    $content = $this->createEmbedCode([
      'data-entity-type' => 'media',
      'data-entity-uuid' => $uuid,
      'data-view-mode' => 'foobar',
    ] + $additional_attributes);

    // If the UUID being used in the embed is that of the sample entity, first
    // assert that it currently results in a functional embed, then delete it.
    if ($uuid === static::EMBEDDED_ENTITY_UUID) {
      $result = $this->processText($content, 'en', $filter_ids);
      $this->setRawContent($result->getProcessedText());
      $this->assertCount(1, $this->cssSelect('div[data-media-embed-test-view-mode="foobar"]'));
      $this->embeddedEntity->delete();
    }
    $result = $this->processText($content, 'en', $filter_ids);
    $this->setRawContent($result->getProcessedText());
    $this->assertCount(0, $this->cssSelect('div[data-media-embed-test-view-mode="foobar"]'));
    $this->assertCount(1, $this->cssSelect('div.this-error-message-is-themeable'));
    if (in_array('filter_align', $filter_ids, TRUE) && !empty($additional_attributes['data-align'])) {
      $this->assertCount(1, $this->cssSelect('div.align-' . $additional_attributes['data-align']));
    }

  }

  /**
   * Data provider for testMissingEntityIndicator().
   */
  public function providerMissingEntityIndicator() {
    return [
      'invalid UUID' => [
        'uuid' => 'invalidUUID',
        'filter_ids' => [
          'filter_align',
          'filter_caption',
          'media_embed',
        ],
        'additional_attributes' => [],
      ],
      'valid UUID but for a deleted entity' => [
        'uuid' => static::EMBEDDED_ENTITY_UUID,
        'filter_ids' => [
          'filter_align',
          'filter_caption',
          'media_embed',
        ],
        'additional_attributes' => [],
      ],
      'invalid UUID; data-align attribute without filter_align enabled' => [
        'uuid' => 'invalidUUID',
        'filter_ids' => [
          'filter_caption',
          'media_embed',
        ],
        'additional_attributes' => ['data-align' => 'right'],
      ],
      'invalid UUID; data-align attribute with filter_align enabled' => [
        'uuid' => 'invalidUUID',
        'filter_ids' => [
          'filter_align',
          'filter_caption',
          'media_embed',
        ],
        'additional_attributes' => ['data-align' => 'left'],
      ],
      'valid UUID but for a deleted entity; data-align attribute with filter_align enabled' => [
        'uuid' => static::EMBEDDED_ENTITY_UUID,
        'filter_ids' => [
          'filter_align',
          'filter_caption',
          'media_embed',
        ],
        'additional_attributes' => ['data-align' => 'center'],
      ],
    ];
  }

  /**
   * Tests that only <drupal-media> tags are processed.
   *
   * @see \Drupal\Tests\media\FunctionalJavascript\CKEditorIntegrationTest::testOnlyDrupalMediaTagProcessed()
   */
  public function testOnlyDrupalMediaTagProcessed() {
    $content = $this->createEmbedCode([
      'data-entity-type' => 'media',
      'data-entity-uuid' => $this->embeddedEntity->uuid(),
    ]);
    $content = str_replace('drupal-media', 'drupal-entity', $content);

    $filter_result = $this->processText($content, 'en', ['media_embed']);
    // If input equals output, the filter didn't change anything.
    $this->assertSame($content, $filter_result->getProcessedText());
  }

  /**
   * Tests recursive rendering protection.
   */
  public function testRecursionProtection() {
    $text = $this->createEmbedCode([
      'data-entity-type' => 'media',
      'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
    ]);

    // Render and verify the presence of the embedded entity 20 times.
    for ($i = 0; $i < 20; $i++) {
      $this->applyFilter($text);
      $this->assertCount(1, $this->cssSelect('div[data-media-embed-test-view-mode="default"]'));
    }

    // Render a 21st time, this is exceeding the recursion limit. The entity
    // embed markup will be stripped.
    $this->applyFilter($text);
    $this->assertEmpty($this->getRawContent());
  }

  /**
   * @covers \Drupal\filter\Plugin\Filter\FilterAlign
   * @covers \Drupal\filter\Plugin\Filter\FilterCaption
   * @dataProvider providerFilterIntegration
   */
  public function testFilterIntegration(array $filter_ids, array $additional_attributes, $verification_selector, $expected_verification_success, array $expected_asset_libraries = [], $prefix = '', $suffix = '') {
    $content = $this->createEmbedCode([
      'data-entity-type' => 'media',
      'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
    ] + $additional_attributes);
    $content = $prefix . $content . $suffix;

    $result = $this->processText($content, 'en', $filter_ids);
    $this->setRawContent($result->getProcessedText());
    $this->assertCount($expected_verification_success ? 1 : 0, $this->cssSelect($verification_selector));
    $this->assertCount(1, $this->cssSelect('div[data-media-embed-test-view-mode="default"]'));
    $this->assertEqualsCanonicalizing([
      '_media_test_embed_filter_access:media:1',
      '_media_test_embed_filter_access:user:2',
      'config:image.style.thumbnail',
      'file:1',
      'media:1',
      'media_view',
      'user:2',
    ], $result->getCacheTags());
    $this->assertEqualsCanonicalizing(['timezone', 'user.permissions'], $result->getCacheContexts());
    $this->assertSame(Cache::PERMANENT, $result->getCacheMaxAge());
    $this->assertSame(['library'], array_keys($result->getAttachments()));
    $this->assertSame($expected_asset_libraries, $result->getAttachments()['library']);
  }

  /**
   * Data provider for testFilterIntegration().
   */
  public function providerFilterIntegration() {
    $default_asset_libraries = ['media/filter.caption'];

    $caption_additional_attributes = ['data-caption' => 'Yo.'];
    $caption_verification_selector = 'figure > figcaption';
    $caption_test_cases = [
      '`data-caption`; only `media_embed` ⇒ caption absent' => [
        ['media_embed'],
        $caption_additional_attributes,
        $caption_verification_selector,
        FALSE,
        $default_asset_libraries,
      ],
      '`data-caption`; `filter_caption` + `media_embed` ⇒ caption present' => [
        ['filter_caption', 'media_embed'],
        $caption_additional_attributes,
        $caption_verification_selector,
        TRUE,
        ['filter/caption', 'media/filter.caption'],
      ],
      '`<a>` + `data-caption`; `filter_caption` + `media_embed` ⇒ caption present, link preserved' => [
        ['filter_caption', 'media_embed'],
        $caption_additional_attributes,
        'figure > a[href="https://www.drupal.org"] + figcaption',
        TRUE,
        ['filter/caption', 'media/filter.caption'],
        '<a href="https://www.drupal.org">',
        '</a>',
      ],
    ];

    $align_additional_attributes = ['data-align' => 'center'];
    $align_verification_selector = 'div[data-media-embed-test-view-mode].align-center';
    $align_test_cases = [
      '`data-align`; `media_embed` ⇒ alignment absent' => [
        ['media_embed'],
        $align_additional_attributes,
        $align_verification_selector,
        FALSE,
        $default_asset_libraries,
      ],
      '`data-align`; `filter_align` + `media_embed` ⇒ alignment present' => [
        ['filter_align', 'media_embed'],
        $align_additional_attributes,
        $align_verification_selector,
        TRUE,
        $default_asset_libraries,
      ],
      '`<a>` + `data-align`; `filter_align` + `media_embed` ⇒ alignment present, link preserved' => [
        ['filter_align', 'media_embed'],
        $align_additional_attributes,
        'a[href="https://www.drupal.org"] > div[data-media-embed-test-view-mode].align-center',
        TRUE,
        $default_asset_libraries,
        '<a href="https://www.drupal.org">',
        '</a>',
      ],
    ];

    $caption_and_align_test_cases = [
      '`data-caption` + `data-align`; `filter_align` + `filter_caption` + `media_embed` ⇒ aligned caption present' => [
        ['filter_align', 'filter_caption', 'media_embed'],
        $align_additional_attributes + $caption_additional_attributes,
        'figure.align-center > figcaption',
        TRUE,
        ['filter/caption', 'media/filter.caption'],
      ],
      '`<a>` + `data-caption` + `data-align`; `filter_align` + `filter_caption` + `media_embed` ⇒ aligned caption present, link preserved' => [
        ['filter_align', 'filter_caption', 'media_embed'],
        $align_additional_attributes + $caption_additional_attributes,
        'figure.align-center > a[href="https://www.drupal.org"] + figcaption',
        TRUE,
        ['filter/caption', 'media/filter.caption'],
        '<a href="https://www.drupal.org">',
        '</a>',
      ],
    ];

    return $caption_test_cases + $align_test_cases + $caption_and_align_test_cases;
  }

}
