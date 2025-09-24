<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests update_post_update_fix_update_emails.
 */
#[Group('Update')]
#[RunTestsInSeparateProcesses]
class UpdateEmptyEmailsUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests update_post_update_fix_update_emails.
   */
  public function testRunUpdates(): void {
    // Add an empty email address, just as was previously possible.
    $this->config('update.settings')->set('notification.emails', [''])->save();

    $this->runUpdates();

    $this->assertEquals([], $this->config('update.settings')->get('notification.emails'));
  }

}
