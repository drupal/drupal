<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\VariableMultiRowSourceWithHighwaterTest.
 */

namespace Drupal\migrate_drupal\Tests\source;

/**
 * Tests variable multirow source w/ highwater handling.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class VariableMultiRowSourceWithHighwaterTest extends VariableMultiRowTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 variable multirow source + highwater',
      'description' => 'Tests D6 variable multirow source plugin with highwater handling.',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->migrationConfiguration['highwaterProperty']['field'] = 'test';
    parent::setup();
  }

}
