<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\CommentSourceWithHighwaterTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

/**
 * Tests the Drupal 6 comment source w/ highwater handling.
 *
 * @group migrate_drupal
 */
class CommentSourceWithHighwaterTest extends CommentTestBase {

  const ORIGINAL_HIGHWATER = 1382255613;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->migrationConfiguration['highwaterProperty']['field'] = 'timestamp';
    array_shift($this->expectedResults);
    parent::setUp();
  }

}
