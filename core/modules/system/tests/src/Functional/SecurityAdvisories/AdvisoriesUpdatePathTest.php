<?php

namespace Drupal\Tests\system\Functional\SecurityAdvisories;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests advisories settings update path.
 *
 * @group system
 */
class AdvisoriesUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      dirname(__DIR__, 3) . '/fixtures/update/drupal-8.8.0.filled.standard.php.gz',
      dirname(__DIR__, 3) . '/fixtures/update/add-update-emails.php',
    ];
  }

  /**
   * Tests advisories settings update path.
   */
  public function testUpdatePath(): void {
    $this->assertTrue($this->config('system.advisories')->isNew());

    $this->runUpdates();

    $this->assertSame(6, $this->config('system.advisories')->get('interval_hours'));
    $this->assertSame(TRUE, $this->config('system.advisories')->get('enabled'));
    $this->assertSame(['graciepup@example.com'], $this->config('system.advisories')->get('emails'));
  }

}
