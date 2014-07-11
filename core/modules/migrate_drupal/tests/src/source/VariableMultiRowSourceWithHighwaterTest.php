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
 */
class VariableMultiRowSourceWithHighwaterTest extends VariableMultiRowTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->migrationConfiguration['highwaterProperty']['field'] = 'test';
    parent::setup();
  }

}
