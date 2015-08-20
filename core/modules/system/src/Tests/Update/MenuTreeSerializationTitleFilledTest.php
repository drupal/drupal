<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\MenuTreeSerializationTitleFilledTest.
 */

namespace Drupal\system\Tests\Update;

/**
 * Runs MenuTreeSerializationTitleTest with a dump filled with content.
 *
 * @group Update
 */
class MenuTreeSerializationTitleFilledTest extends MenuTreeSerializationTitleTest {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->databaseDumpFiles[0] = __DIR__ . '/../../../tests/fixtures/update/drupal-8.filled.standard.php.gz';
    parent::setUp();
  }

}
