<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that the search_help module is installed after help updates.
 */
#[Group('help')]
#[RunTestsInSeparateProcesses]
class HelpSearchHelpUpgradeTest extends UpdatePathTestBase {

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
    ];
  }

  /**
   * Tests system_update_11400() and help_post_update_search_help_dependencies().
   *
   * @see system_update_11400()
   * @see help_post_update_search_help_dependencies()
   */
  public function testSearchHelpInstall(): void {
    $this->assertSame(['help'], $this->config('search.page.help_search')->get('dependencies.module'));
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('search_help'));
    $this->assertSame(77, (int) \Drupal::database()->query('SELECT COUNT(*) FROM {help_search_items}')->fetchField());

    $this->runUpdates();

    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('search_help'));
    $this->assertSame(76, (int) \Drupal::database()->query('SELECT COUNT(*) FROM {help_search_items}')->fetchField());

    $this->assertSame(['search_help'], $this->config('search.page.help_search')->get('dependencies.module'));
  }

}
