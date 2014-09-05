<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate_drupal\Unit\source\VariableMultiRowSourceWithHighwaterTest.
 */

namespace Drupal\Tests\migrate_drupal\Unit\source;

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
