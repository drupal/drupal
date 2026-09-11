<?php

declare(strict_types=1);

namespace Drupal\Tests\text\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests installing text_with_summary for an unused field storage.
 */
#[Group('Update')]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
class TextWithSummaryUnusedStorageUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-11.3.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/add-unused-text-with-summary-storage.php',
    ];
  }

  /**
   * Tests text_with_summary installation for an unused field storage.
   */
  public function testRunUpdates(): void {
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('text_with_summary'));

    $this->runUpdates();

    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('text_with_summary'));
  }

}
