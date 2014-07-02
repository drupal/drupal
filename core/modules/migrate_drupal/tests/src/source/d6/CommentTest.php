<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\CommentTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

/**
 * Tests the Drupal 6 comment source.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class CommentTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 comment source functionality',
      'description' => 'Tests D6 comment source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

}
