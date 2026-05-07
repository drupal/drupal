<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that the search_help module is not installed after help updates.
 */
#[Group('help')]
#[RunTestsInSeparateProcesses]
class HelpNoSearchUpgradeTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-11.3.0.bare.standard.php.gz',
      __DIR__ . '/../../fixtures/uninstall-search.php',
    ];
  }

  /**
   * Tests system_update_11400().
   *
   * @see system_update_11400()
   */
  public function testSearchHelpInstall(): void {
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('search_help'));
    $this->assertTrue(\Drupal::database()->schema()->tableExists('help_search_items'));

    $this->runUpdates();

    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('search_help'));
    $this->assertFalse(\Drupal::database()->schema()->tableExists('help_search_items'));
  }

}
