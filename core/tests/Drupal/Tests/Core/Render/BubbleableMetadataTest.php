<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\Core\Render\BubbleableMetadata
 * @group Render
 */
class BubbleableMetadataTest extends UnitTestCase {

  /**
   * Tests merge().
   *
   * This only tests at a high level, because it reuses existing logic. Detailed
   * tests exist for the existing logic:
   *
   * @see \Drupal\Tests\Core\Cache\CacheTest::testMergeTags()
   * @see \Drupal\Tests\Core\Cache\CacheTest::testMergeMaxAges()
   * @see \Drupal\Tests\Core\Cache\CacheContextsTest
   * @see \Drupal\Tests\Core\Render\RendererPlaceholdersTest
   * @see testMergeAttachmentsLibraryMerging()
   * @see testMergeAttachmentsFeedMerging()
   * @see testMergeAttachmentsHtmlHeadMerging()
   * @see testMergeAttachmentsHtmlHeadLinkMerging()
   * @see testMergeAttachmentsHttpHeaderMerging()
   *
   * @covers ::merge
   * @dataProvider providerTestMerge
   */
  public function testMerge(BubbleableMetadata $a, CacheableMetadata $b, BubbleableMetadata $expected): void {
    // Verify that if the second operand is a CacheableMetadata object, not a
    // BubbleableMetadata object, that BubbleableMetadata::merge() doesn't
    // attempt to merge assets.
    if (!$b instanceof BubbleableMetadata) {
      $renderer = $this->getMockBuilder('Drupal\Core\Render\Renderer')
        ->disableOriginalConstructor()
        ->getMock();
    }
    // Otherwise, let the original ::mergeAttachments() method be executed.
    else {
      $renderer = $this->getMockBuilder('Drupal\Core\Render\Renderer')
        ->disableOriginalConstructor()
        ->onlyMethods([])
        ->getMock();
    }

    $cache_contexts_manager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    $container->set('renderer', $renderer);
    \Drupal::setContainer($container);

    $this->assertEqualsCanonicalizing($expected, $a->merge($b));
  }

  /**
   * Provides test data for testMerge().
   *
   * @return array
   *   An array of test data for testMerge().
   */
  public static function providerTestMerge() {
    return [
      // Second operand is a BubbleableMetadata object.
      // All empty.
      [(new BubbleableMetadata()), (new BubbleableMetadata()), (new BubbleableMetadata())],
      // Cache contexts.
      [(new BubbleableMetadata())->setCacheContexts(['foo']), (new BubbleableMetadata())->setCacheContexts(['bar']), (new BubbleableMetadata())->setCacheContexts(['bar', 'foo'])],
      // Cache tags.
      [(new BubbleableMetadata())->setCacheTags(['foo']), (new BubbleableMetadata())->setCacheTags(['bar']), (new BubbleableMetadata())->setCacheTags(['foo', 'bar'])],
      // Cache max-ages.
      [(new BubbleableMetadata())->setCacheMaxAge(60), (new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT), (new BubbleableMetadata())->setCacheMaxAge(60)],
      // Assets.
      [(new BubbleableMetadata())->setAttachments(['library' => ['core/foo']]), (new BubbleableMetadata())->setAttachments(['library' => ['core/bar']]), (new BubbleableMetadata())->setAttachments(['library' => ['core/foo', 'core/bar']])],
      // Placeholders.
      [(new BubbleableMetadata())->setAttachments(['placeholders' => ['<my-placeholder>' => ['callback', ['A']]]]), (new BubbleableMetadata())->setAttachments(['placeholders' => ['<my-placeholder>' => ['callback', ['A']]]]), (new BubbleableMetadata())->setAttachments(['placeholders' => ['<my-placeholder>' => ['callback', ['A']]]])],

      // Second operand is a CacheableMetadata object.
      // All empty.
      [(new BubbleableMetadata()), (new CacheableMetadata()), (new BubbleableMetadata())],
      // Cache contexts.
      [(new BubbleableMetadata())->setCacheContexts(['foo']), (new CacheableMetadata())->setCacheContexts(['bar']), (new BubbleableMetadata())->setCacheContexts(['bar', 'foo'])],
      // Cache tags.
      [(new BubbleableMetadata())->setCacheTags(['foo']), (new CacheableMetadata())->setCacheTags(['bar']), (new BubbleableMetadata())->setCacheTags(['foo', 'bar'])],
      // Cache max-ages.
      [(new BubbleableMetadata())->setCacheMaxAge(60), (new CacheableMetadata())->setCacheMaxAge(Cache::PERMANENT), (new BubbleableMetadata())->setCacheMaxAge(60)],
    ];
  }

  /**
   * Tests addAttachments().
   *
   * This only tests at a high level, because it reuses existing logic. Detailed
   * tests exist for the existing logic:
   *
   * @see testMergeAttachmentsLibraryMerging()
   * @see testMergeAttachmentsFeedMerging()
   * @see testMergeAttachmentsHtmlHeadMerging()
   * @see testMergeAttachmentsHtmlHeadLinkMerging()
   * @see testMergeAttachmentsHttpHeaderMerging()
   *
   * @covers ::addAttachments
   * @covers ::setAttachments
   * @dataProvider providerTestAddAttachments
   */
  public function testAddAttachments(BubbleableMetadata $initial, $attachments, BubbleableMetadata $expected): void {
    $test = $initial;
    $test->addAttachments($attachments);
    $this->assertEquals($expected, $test);
  }

  /**
   * Provides test data for testAddAttachments().
   */
  public static function providerTestAddAttachments() {
    return [
      [new BubbleableMetadata(), [], new BubbleableMetadata()],
      [new BubbleableMetadata(), ['library' => ['core/foo']], (new BubbleableMetadata())->setAttachments(['library' => ['core/foo']])],
      [(new BubbleableMetadata())->setAttachments(['library' => ['core/foo']]), ['library' => ['core/bar']], (new BubbleableMetadata())->setAttachments(['library' => ['core/foo', 'core/bar']])],
    ];
  }

  /**
   * @covers ::applyTo
   * @dataProvider providerTestApplyTo
   */
  public function testApplyTo(BubbleableMetadata $metadata, array $render_array, array $expected): void {
    $this->assertNull($metadata->applyTo($render_array));
    $this->assertEquals($expected, $render_array);
  }

  /**
   * Provides test data for testApplyTo().
   *
   * @return array
   *   An array of test data for testApplyTo().
   */
  public static function providerTestApplyTo() {
    $data = [];

    $empty_metadata = new BubbleableMetadata();
    $nonempty_metadata = new BubbleableMetadata();
    $nonempty_metadata->setCacheContexts(['qux'])
      ->setCacheTags(['foo:bar'])
      ->setAttachments(['settings' => ['foo' => 'bar']]);

    $empty_render_array = [];
    $nonempty_render_array = [
      '#cache' => [
        'contexts' => ['qux'],
        'tags' => ['llamas:are:awesome:but:kittens:too'],
        'max-age' => Cache::PERMANENT,
      ],
      '#attached' => [
        'library' => [
          'core/jquery',
        ],
      ],
    ];

    $expected_when_empty_metadata = [
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
      '#attached' => [],
    ];
    $data[] = [$empty_metadata, $empty_render_array, $expected_when_empty_metadata];
    $data[] = [$empty_metadata, $nonempty_render_array, $expected_when_empty_metadata];
    $expected_when_nonempty_metadata = [
      '#cache' => [
        'contexts' => ['qux'],
        'tags' => ['foo:bar'],
        'max-age' => Cache::PERMANENT,
      ],
      '#attached' => [
        'settings' => [
          'foo' => 'bar',
        ],
      ],
    ];
    $data[] = [$nonempty_metadata, $empty_render_array, $expected_when_nonempty_metadata];
    $data[] = [$nonempty_metadata, $nonempty_render_array, $expected_when_nonempty_metadata];

    return $data;
  }

  /**
   * @covers ::createFromRenderArray
   * @dataProvider providerTestCreateFromRenderArray
   */
  public function testCreateFromRenderArray(array $render_array, BubbleableMetadata $expected): void {
    $this->assertEquals($expected, BubbleableMetadata::createFromRenderArray($render_array));
  }

  /**
   * Provides test data for createFromRenderArray().
   *
   * @return array
   *   An array of test data for createFromRenderArray().
   */
  public static function providerTestCreateFromRenderArray() {
    $data = [];

    $empty_metadata = new BubbleableMetadata();
    $nonempty_metadata = new BubbleableMetadata();
    $nonempty_metadata->setCacheContexts(['qux'])
      ->setCacheTags(['foo:bar'])
      ->setAttachments(['settings' => ['foo' => 'bar']]);

    $empty_render_array = [];
    $nonempty_render_array = [
      '#cache' => [
        'contexts' => ['qux'],
        'tags' => ['foo:bar'],
        'max-age' => Cache::PERMANENT,
      ],
      '#attached' => [
        'settings' => [
          'foo' => 'bar',
        ],
      ],
    ];

    $data[] = [$empty_render_array, $empty_metadata];
    $data[] = [$nonempty_render_array, $nonempty_metadata];

    return $data;
  }

  /**
   * Tests library asset merging.
   *
   * @covers ::mergeAttachments
   */
  public function testMergeAttachmentsLibraryMerging(): void {
    $a['#attached'] = [
      'library' => [
        'core/drupal',
        'core/drupalSettings',
      ],
      'drupalSettings' => [
        'foo' => ['d'],
      ],
    ];
    $b['#attached'] = [
      'library' => [
        'core/jquery',
      ],
      'drupalSettings' => [
        'bar' => ['a', 'b', 'c'],
      ],
    ];
    $expected['#attached'] = [
      'library' => [
        'core/drupal',
        'core/drupalSettings',
        'core/jquery',
      ],
      'drupalSettings' => [
        'foo' => ['d'],
        'bar' => ['a', 'b', 'c'],
      ],
    ];
    $this->assertSame($expected['#attached'], BubbleableMetadata::mergeAttachments($a['#attached'], $b['#attached']), 'Attachments merged correctly.');

    // Merging in the opposite direction yields the opposite library order.
    $expected['#attached'] = [
      'library' => [
        'core/jquery',
        'core/drupal',
        'core/drupalSettings',
      ],
      'drupalSettings' => [
        'bar' => ['a', 'b', 'c'],
        'foo' => ['d'],
      ],
    ];
    $this->assertSame($expected['#attached'], BubbleableMetadata::mergeAttachments($b['#attached'], $a['#attached']), 'Attachments merged correctly; opposite merging yields opposite order.');

    // Merging with duplicates: duplicates are simply retained, it's up to the
    // rest of the system to handle duplicates.
    $b['#attached']['library'][] = 'core/drupalSettings';
    $expected['#attached'] = [
      'library' => [
        'core/drupal',
        'core/drupalSettings',
        'core/jquery',
        'core/drupalSettings',
      ],
      'drupalSettings' => [
        'foo' => ['d'],
        'bar' => ['a', 'b', 'c'],
      ],
    ];
    $this->assertSame($expected['#attached'], BubbleableMetadata::mergeAttachments($a['#attached'], $b['#attached']), 'Attachments merged correctly; duplicates are retained.');

    // Merging with duplicates (simple case).
    $b['#attached']['drupalSettings']['foo'] = ['a', 'b', 'c'];
    $expected['#attached'] = [
      'library' => [
        'core/drupal',
        'core/drupalSettings',
        'core/jquery',
        'core/drupalSettings',
      ],
      'drupalSettings' => [
        'foo' => ['a', 'b', 'c'],
        'bar' => ['a', 'b', 'c'],
      ],
    ];
    $this->assertSame($expected['#attached'], BubbleableMetadata::mergeAttachments($a['#attached'], $b['#attached']));

    // Merging with duplicates (simple case) in the opposite direction yields
    // the opposite JS setting asset order, but also opposite overriding order.
    $expected['#attached'] = [
      'library' => [
        'core/jquery',
        'core/drupalSettings',
        'core/drupal',
        'core/drupalSettings',
      ],
      'drupalSettings' => [
        'bar' => ['a', 'b', 'c'],
        'foo' => ['d', 'b', 'c'],
      ],
    ];
    $this->assertSame($expected['#attached'], BubbleableMetadata::mergeAttachments($b['#attached'], $a['#attached']));

    // Merging with duplicates: complex case.
    // Only the second of these two entries should appear in drupalSettings.
    $build = [];
    $build['a']['#attached']['drupalSettings']['commonTest'] = 'firstValue';
    $build['b']['#attached']['drupalSettings']['commonTest'] = 'secondValue';
    // Only the second of these entries should appear in drupalSettings.
    $build['a']['#attached']['drupalSettings']['commonTestJsArrayLiteral'] = ['firstValue'];
    $build['b']['#attached']['drupalSettings']['commonTestJsArrayLiteral'] = ['secondValue'];
    // Only the second of these two entries should appear in drupalSettings.
    $build['a']['#attached']['drupalSettings']['commonTestJsObjectLiteral'] = ['key' => 'firstValue'];
    $build['b']['#attached']['drupalSettings']['commonTestJsObjectLiteral'] = ['key' => 'secondValue'];
    // Real world test case: multiple elements in a render array are adding the
    // same (or nearly the same) JavaScript settings. When merged, they should
    // contain all settings and not duplicate some settings.
    $settings_one = ['moduleName' => ['ui' => ['button A', 'button B'], 'magical flag' => 3.14159265359]];
    $build['a']['#attached']['drupalSettings']['commonTestRealWorldIdentical'] = $settings_one;
    $build['b']['#attached']['drupalSettings']['commonTestRealWorldIdentical'] = $settings_one;
    $settings_two_a = ['moduleName' => ['ui' => ['button A', 'button B', 'button C'], 'magical flag' => 3.14159265359, 'thingiesOnPage' => ['id1' => []]]];
    $build['a']['#attached']['drupalSettings']['commonTestRealWorldAlmostIdentical'] = $settings_two_a;
    $settings_two_b = ['moduleName' => ['ui' => ['button D', 'button E'], 'magical flag' => 3.14, 'thingiesOnPage' => ['id2' => []]]];
    $build['b']['#attached']['drupalSettings']['commonTestRealWorldAlmostIdentical'] = $settings_two_b;

    $merged = BubbleableMetadata::mergeAttachments($build['a']['#attached'], $build['b']['#attached']);

    // Test whether #attached can be used to override a previous setting.
    $this->assertSame('secondValue', $merged['drupalSettings']['commonTest']);

    // Test whether #attached can be used to add and override a JavaScript
    // array literal (an indexed PHP array) values.
    $this->assertSame('secondValue', $merged['drupalSettings']['commonTestJsArrayLiteral'][0]);

    // Test whether #attached can be used to add and override a JavaScript
    // object literal (an associate PHP array) values.
    $this->assertSame('secondValue', $merged['drupalSettings']['commonTestJsObjectLiteral']['key']);

    // Test whether the two real world cases are handled correctly: the first
    // adds the exact same settings twice and hence tests idempotency, the
    // second adds *almost* the same settings twice: the second time, some
    // values are altered, and some key-value pairs are added.
    $this->assertSame($settings_one, $merged['drupalSettings']['commonTestRealWorldIdentical']);
    $expected_settings_two = $settings_two_a;
    $expected_settings_two['moduleName']['thingiesOnPage']['id1'] = [];
    $expected_settings_two['moduleName']['ui'][0] = 'button D';
    $expected_settings_two['moduleName']['ui'][1] = 'button E';
    $expected_settings_two['moduleName']['ui'][2] = 'button C';
    $expected_settings_two['moduleName']['magical flag'] = 3.14;
    $expected_settings_two['moduleName']['thingiesOnPage']['id2'] = [];
    $this->assertSame($expected_settings_two, $merged['drupalSettings']['commonTestRealWorldAlmostIdentical']);
  }

  /**
   * Tests html_head asset merging.
   *
   * @covers ::mergeAttachments
   *
   * @dataProvider providerTestMergeAttachmentsHtmlHeadMerging
   */
  public function testMergeAttachmentsHtmlHeadMerging($a, $b, $expected): void {
    $this->assertSame($expected, BubbleableMetadata::mergeAttachments($a, $b));
  }

  /**
   * Data provider for testMergeAttachmentsHtmlHeadMerging.
   *
   * @return array
   *   An array of test data for testMergeAttachmentsHtmlHeadMerging.
   */
  public static function providerTestMergeAttachmentsHtmlHeadMerging() {
    $meta = [
      '#tag' => 'meta',
      '#attributes' => [
        'charset' => 'utf-8',
      ],
      '#weight' => -1000,
    ];

    $html_tag = [
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => [
        'name' => 'Generator',
        'content' => 'Kitten 1.0 (https://www.drupal.org/project/kitten)',
      ],
    ];

    $a = [
      'html_head' => [
        $meta,
        'system_meta_content_type',
      ],
    ];

    $b = [
      'html_head' => [
        $html_tag,
        'system_meta_generator',
      ],
    ];

    $expected_a = [
      'html_head' => [
        $meta,
        'system_meta_content_type',
        $html_tag,
        'system_meta_generator',
      ],
    ];

    // Merging in the opposite direction yields the opposite library order.
    $expected_b = [
      'html_head' => [
        $html_tag,
        'system_meta_generator',
        $meta,
        'system_meta_content_type',
      ],
    ];

    return [
      [$a, $b, $expected_a],
      [$b, $a, $expected_b],
    ];
  }

  /**
   * Tests html_head_link asset merging.
   *
   * @covers ::mergeAttachments
   *
   * @dataProvider providerTestMergeAttachmentsHtmlHeadLinkMerging
   */
  public function testMergeAttachmentsHtmlHeadLinkMerging($a, $b, $expected): void {
    $this->assertSame($expected, BubbleableMetadata::mergeAttachments($a, $b));
  }

  /**
   * Data provider for testMergeAttachmentsHtmlHeadLinkMerging.
   *
   * @return array
   *   An array of test data for testMergeAttachmentsHtmlHeadLinkMerging.
   */
  public static function providerTestMergeAttachmentsHtmlHeadLinkMerging() {
    $rel = [
      'rel' => 'rel',
      'href' => 'http://rel.example.com',
    ];

    $shortlink = [
      'rel' => 'shortlink',
      'href' => 'http://shortlink.example.com',
    ];

    $a = [
      'html_head_link' => [
        $rel,
        TRUE,
      ],
    ];

    $b = [
      'html_head_link' => [
        $shortlink,
        FALSE,
      ],
    ];

    $expected_a = [
      'html_head_link' => [
        $rel,
        TRUE,
        $shortlink,
        FALSE,
      ],
    ];

    // Merging in the opposite direction yields the opposite library order.
    $expected_b = [
      'html_head_link' => [
        $shortlink,
        FALSE,
        $rel,
        TRUE,
      ],
    ];

    return [
      [$a, $b, $expected_a],
      [$b, $a, $expected_b],
    ];
  }

  /**
   * Tests http_header asset merging.
   *
   * @covers ::mergeAttachments
   *
   * @dataProvider providerTestMergeAttachmentsHttpHeaderMerging
   */
  public function testMergeAttachmentsHttpHeaderMerging($a, $b, $expected): void {
    $this->assertSame($expected, BubbleableMetadata::mergeAttachments($a, $b));
  }

  /**
   * Data provider for testMergeAttachmentsHttpHeaderMerging.
   *
   * @return array
   *   An array of test data for testMergeAttachmentsHttpHeaderMerging.
   */
  public static function providerTestMergeAttachmentsHttpHeaderMerging() {
    $content_type = [
      'Content-Type',
      'application/rss+xml; charset=utf-8',
    ];

    $expires = [
      'Expires',
      'Sun, 19 Nov 1978 05:00:00 GMT',
    ];

    $a = [
      'http_header' => [
        $content_type,
      ],
    ];

    $b = [
      'http_header' => [
        $expires,
      ],
    ];

    $expected_a = [
      'http_header' => [
        $content_type,
        $expires,
      ],
    ];

    // Merging in the opposite direction yields the opposite library order.
    $expected_b = [
      'http_header' => [
        $expires,
        $content_type,
      ],
    ];

    return [
      [$a, $b, $expected_a],
      [$b, $a, $expected_b],
    ];
  }

  /**
   * Tests addCacheableDependency().
   *
   * This only tests at a high level, because it reuses existing logic. Detailed
   * tests exist for the existing logic:
   *
   * @see \Drupal\Tests\Core\Cache\CacheTest::testMergeTags()
   * @see \Drupal\Tests\Core\Cache\CacheTest::testMergeMaxAges()
   * @see \Drupal\Tests\Core\Cache\CacheContextsTest
   *
   * @covers ::addCacheableDependency
   * @dataProvider providerTestMerge
   */
  public function testAddCacheableDependency(BubbleableMetadata $a, $b, BubbleableMetadata $expected): void {
    $cache_contexts_manager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);

    $this->assertEqualsCanonicalizing($expected, $a->addCacheableDependency($b));
  }

  /**
   * Provides test data for testMerge().
   *
   * @return array
   *   An array of test data for testMerge().
   */
  public function providerTestAddCacheableDependency() {
    return [
      // Merge in a cacheable metadata.
      'merge-cacheable-metadata' => [
        (new BubbleableMetadata())->setCacheContexts(['foo'])->setCacheTags(['foo'])->setCacheMaxAge(20),
        (new CacheableMetadata())->setCacheContexts(['bar'])->setCacheTags(['bar'])->setCacheMaxAge(60),
        (new BubbleableMetadata())->setCacheContexts(['foo', 'bar'])->setCacheTags(['foo', 'bar'])->setCacheMaxAge(20),
      ],
      'merge-bubbleable-metadata' => [
        (new BubbleableMetadata())->setCacheContexts(['foo'])->setCacheTags(['foo'])->setCacheMaxAge(20)->setAttachments(['foo' => []]),
        (new BubbleableMetadata())->setCacheContexts(['bar'])->setCacheTags(['bar'])->setCacheMaxAge(60)->setAttachments(['bar' => []]),
        (new BubbleableMetadata())->setCacheContexts(['foo', 'bar'])->setCacheTags(['foo', 'bar'])->setCacheMaxAge(20)->setAttachments(['foo' => [], 'bar' => []]),
      ],
      'merge-attachments-metadata' => [
        (new BubbleableMetadata())->setAttachments(['foo' => []]),
        (new BubbleableMetadata())->setAttachments(['bar' => []]),
        (new BubbleableMetadata())->setAttachments(['foo' => [], 'bar' => []]),
      ],
    ];
  }

}
