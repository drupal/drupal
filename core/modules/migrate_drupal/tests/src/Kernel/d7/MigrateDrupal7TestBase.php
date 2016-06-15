<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d7;

use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Base class for Drupal 7 migration tests.
 */
abstract class MigrateDrupal7TestBase extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadFixture($this->getFixtureFilePath());
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../fixtures/drupal7.php';
  }

}
