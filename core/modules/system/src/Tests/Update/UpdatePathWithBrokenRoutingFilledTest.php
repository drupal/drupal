<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\UpdatePathWithBrokenRoutingFilledTest.
 */

namespace Drupal\system\Tests\Update;

/**
 * Runs UpdatePathWithBrokenRoutingTest with a dump filled with content.
 *
 * @group Update
 */
class UpdatePathWithBrokenRoutingFilledTest extends UpdatePathWithBrokenRoutingTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseDumpFiles[0] =  '/../../../tests/fixtures/update/drupal-8.filled.standard.php.gz';
    parent::setUp();
  }

}
