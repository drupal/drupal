<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Test the system module updates with no dependencies installed.
 *
 * @group Update
 * @group legacy
 */
class NoDependenciesUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $installProfile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.6.0.bare.testing.php.gz',
    ];
  }

  /**
   * Test the system module updates with no dependencies installed.
   */
  public function testNoDependenciesUpdate() {
    $this->runUpdates();
  }

}
