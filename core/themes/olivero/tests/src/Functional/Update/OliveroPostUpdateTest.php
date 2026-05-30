<?php

declare(strict_types=1);

namespace Drupal\Tests\olivero\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the update path for Olivero.
 */
#[Group('olivero')]
#[Group('Update')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
#[CoversFunction('olivero_post_update_remove_shortcut_settings_if_not_installed')]
class OliveroPostUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../../modules/system/tests/fixtures/update/drupal-11.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests update olivero third party settings without shortcut installed.
   */
  public function testOliveroThirdPartySettingsWithoutShortcut(): void {
    $config = $this->config('olivero.settings');
    $this->assertNotEmpty($config->get('third_party_settings'));

    // Run updates.
    $this->runUpdates();

    $config = $this->config('olivero.settings');
    $this->assertNull($config->get('third_party_settings'));
  }

}
