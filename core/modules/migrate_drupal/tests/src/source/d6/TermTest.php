<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\TermTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

/**
 * Tests the Drupal 6 taxonomy term source.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class TermTest extends TermTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 taxonomy term source functionality',
      'description' => 'Tests D6 taxonomy term source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

}
