<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\UpdatePathTestBaseFilledTest.php
 */

namespace Drupal\system\Tests\Update;

/**
 * Runs UpdatePathTestBaseTest with a dump filled with content.
 *
 * @group Update
 */
class UpdatePathTestBaseFilledTest extends UpdatePathTestBaseTest {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles[0] = __DIR__ . '/../../../tests/fixtures/update/drupal-8.filled.standard.php.gz';
  }

}
