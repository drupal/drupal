<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\VariableMultiRowSourceWithHighwaterTest.
 */

namespace Drupal\migrate_drupal\Tests\source;

/**
 * Tests variable multirow source w/ high water handling.
 *
 * @group migrate_drupal
 */
class VariableMultiRowSourceWithHighwaterTest extends VariableMultiRowTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->migrationConfiguration['highWaterProperty']['field'] = 'test';
    parent::setup();
  }

}
