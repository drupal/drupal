<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\CommentSourceTestWithHighwater.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

/**
 * Tests the Drupal 6 comment source w/ highwater handling.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class CommentSourceWithHighwaterTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 comment source + highwater',
      'description' => 'Tests D6 comment source plugin with highwater handling.',
      'group' => 'Migrate Drupal',
    );
  }

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
