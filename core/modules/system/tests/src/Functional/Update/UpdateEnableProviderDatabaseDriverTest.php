<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that update hooks are enabling the database driver providing module.
 *
 * @group Update
 */
class UpdateEnableProviderDatabaseDriverTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that post update hooks are properly run.
   */
  public function testPostUpdateEnableProviderDatabaseDriverHook() {
    $connection = Database::getConnection();
    $provider = $connection->getProvider();

    $this->assertFalse(\Drupal::moduleHandler()->moduleExists($provider));

    // Running the updates enables the module that is providing the database
    // driver.
    $this->runUpdates();

    $this->assertTrue(\Drupal::moduleHandler()->moduleExists($provider));
  }

}
