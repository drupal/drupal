<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Update\EntityViewsDataUpdateFilledTest.
 */

namespace Drupal\views\Tests\Update;

/**
 * Runs EntityViewsDataUpdateTest with a dump filled with content.
 *
 * @group Update
 */
class EntityViewsDataUpdateFilledTest extends EntityViewsDataUpdateTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseDumpFiles[0] = __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.filled.standard.php.gz';
    parent::setUp();
  }

}
