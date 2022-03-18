<?php

namespace Drupal\Tests\aggregator\Unit;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Render\BubbleableMetadata
 * @group aggregator
 */
class BubbleableMetadataTest extends UnitTestCase {

  /**
   * Tests feed asset merging.
   *
   * @covers ::mergeAttachments
   *
   * @dataProvider providerTestMergeAttachmentsFeedMerging
   */
  public function testMergeAttachmentsFeedMerging($a, $b, $expected) {
    $this->assertSame($expected, BubbleableMetadata::mergeAttachments($a, $b));
  }

  /**
   * Data provider for testMergeAttachmentsFeedMerging.
   *
   * @return array
   */
  public function providerTestMergeAttachmentsFeedMerging() {
    $feed_a = [
      'aggregator/rss',
      'Feed title',
    ];

    $feed_b = [
      'taxonomy/term/1/feed',
      'RSS - foo',
    ];

    $a = [
      'feed' => [
        $feed_a,
      ],
    ];
    $b = [
      'feed' => [
        $feed_b,
      ],
    ];

    $expected_a = [
      'feed' => [
        $feed_a,
        $feed_b,
      ],
    ];

    // Merging in the opposite direction yields the opposite library order.
    $expected_b = [
      'feed' => [
        $feed_b,
        $feed_a,
      ],
    ];

    return [
      [$a, $b, $expected_a],
      [$b, $a, $expected_b],
    ];
  }

}
