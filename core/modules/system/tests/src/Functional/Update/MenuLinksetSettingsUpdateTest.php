<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests creation of system linkset settings.
 *
 * @see system_post_update_linkset_settings()
 *
 * @group Update
 */
class MenuLinksetSettingsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests system_post_update_linkset_settings().
   */
  public function testSystemPostUpdateLinksetSettings(): void {
    // Ensure config is not present.
    $config = $this->config('system.feature_flags');
    $this->assertTrue($config->isNew());

    $this->runUpdates();

    // Confirm that config was created and the endpoint is disabled.
    $updated_config = $this->config('system.feature_flags');
    $this->assertFalse($updated_config->isNew());
    $this->assertFalse($updated_config->get('linkset_endpoint'));
  }

}
