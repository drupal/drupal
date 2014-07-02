<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\VariableMultiRowTest.
 */

namespace Drupal\migrate_drupal\Tests\source;

/**
 * Tests the Drupal 6 variable multirow source.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class VariableMultiRowTest extends VariableMultiRowTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 variable multirow source functionality',
      'description' => 'Tests D6 variable multirow source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

}
