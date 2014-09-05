<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate_drupal\Unit\source\d6\CommentSourceWithHighWaterTest.
 */

namespace Drupal\Tests\migrate_drupal\Unit\source\d6;

/**
 * Tests the Drupal 6 comment source w/ high water handling.
 *
 * @group migrate_drupal
 */
class CommentSourceWithHighWaterTest extends CommentTestBase {

  const ORIGINAL_HIGH_WATER = 1382255613;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->migrationConfiguration['highWaterProperty']['field'] = 'timestamp';
    array_shift($this->expectedResults);
    parent::setUp();
  }

}
