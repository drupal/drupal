<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\BubbleableMetadataTest.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Render\Element;

/**
 * @coversDefaultClass \Drupal\Core\Render\BubbleableMetadata
 * @group Render
 */
class BubbleableMetadataTest extends UnitTestCase {

  /**
   * @covers ::apply
   * @dataProvider providerTestApply
   */
  public function testApply(BubbleableMetadata $metadata, array $render_array, array $expected) {
    $this->assertNull($metadata->applyTo($render_array));
    $this->assertEquals($expected, $render_array);
  }

  /**
   * Provides test data for apply().
   *
   * @return array
   */
  public function providerTestApply() {
    $data = [];

    $empty_metadata = new BubbleableMetadata();
    $nonempty_metadata = new BubbleableMetadata(['foo:bar'], ['settings' => ['foo' => 'bar']]);

    $empty_render_array = [];
    $nonempty_render_array = [
      '#cache' => [
        'tags' => ['llamas:are:awesome:but:kittens:too'],
      ],
      '#attached' => [
        'library' => [
          'core/jquery',
        ],
      ],
      '#post_render_cache' => [],
    ];


    $expected_when_empty_metadata = [
      '#cache' => [
        'tags' => []
      ],
      '#attached' => [],
      '#post_render_cache' => [],
    ];
    $data[] = [$empty_metadata, $empty_render_array, $expected_when_empty_metadata];
    $data[] = [$empty_metadata, $nonempty_render_array, $expected_when_empty_metadata];
    $expected_when_nonempty_metadata = [
      '#cache' => ['tags' => ['foo:bar']],
      '#attached' => [
        'settings' => [
          'foo' => 'bar',
        ],
      ],
      '#post_render_cache' => [],
    ];
    $data[] = [$nonempty_metadata, $empty_render_array, $expected_when_nonempty_metadata];
    $data[] = [$nonempty_metadata, $nonempty_render_array, $expected_when_nonempty_metadata];

    return $data;
  }

  /**
   * @covers ::createFromRenderArray
   * @dataProvider providerTestCreateFromRenderArray
   */
  public function testCreateFromRenderArray(array $render_array, BubbleableMetadata $expected) {
    $this->assertEquals($expected, BubbleableMetadata::createFromRenderArray($render_array));
  }

  /**
   * Provides test data for createFromRenderArray().
   *
   * @return array
   */
  public function providerTestCreateFromRenderArray() {
    $data = [];

    $empty_metadata = new BubbleableMetadata();
    $nonempty_metadata = new BubbleableMetadata(['foo:bar'], ['settings' => ['foo' => 'bar']]);

    $empty_render_array = [];
    $nonempty_render_array = [
      '#cache' => [
        'tags' => ['foo:bar'],
      ],
      '#attached' => [
        'settings' => [
          'foo' => 'bar',
        ],
      ],
      '#post_render_cache' => [],
    ];


    $data[] = [$empty_render_array, $empty_metadata];
    $data[] = [$nonempty_render_array, $nonempty_metadata];

    return $data;
  }

}
