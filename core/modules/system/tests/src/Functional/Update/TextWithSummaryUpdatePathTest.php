<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the update path for the removed text_with_summary field type.
 */
#[Group('Update')]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
class TextWithSummaryUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $checkFailedUpdates = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-11.3.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/add-text-with-summary-storage.php',
    ];
  }

  /**
   * Tests the update requirement for the missing field type.
   */
  public function testUpdateRequirements(): void {
    $this->writeSettings([
      'settings' => [
        'update_free_access' => (object) [
          'value' => TRUE,
          'required' => TRUE,
        ],
      ],
    ]);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);

    $this->assertSession()->pageTextContains('Missing text_with_summary field type');
    $this->assertSession()->pageTextContains('The text_with_summary field type has been moved to a contributed module.');
    $this->assertSession()->pageTextContains('composer require drupal/text_with_summary');
    $this->assertSession()->pageTextContains('enable the text_with_summary module');
  }

}
