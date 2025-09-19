<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests Views configuration updates.
 *
 * @group Update
 */
class ConfigUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
    ];
  }

  /**
   * @covers views_update_11201
   */
  public function testConfigUpdate(): void {
    $config = \Drupal::configFactory()->get('views.settings');
    $this->assertFalse($config->get('ui.show.advanced_column'));

    $this->runUpdates();

    $config = \Drupal::configFactory()->get('views.settings');
    $this->assertNull($config->get('ui.show.advanced_column'));
  }

}
