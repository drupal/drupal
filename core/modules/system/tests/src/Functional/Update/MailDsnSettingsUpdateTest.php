<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests creation of default mail transport dsn settings.
 *
 * @see system_post_update_mailer_dsn_settings()
 *
 * @group Update
 */
class MailDsnSettingsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests system_post_update_mailer_dsn_settings().
   */
  public function testSystemPostUpdateMailerDsnSettings(): void {
    $this->runUpdates();

    // Confirm that config was created.
    $config = $this->config('system.mail');
    $expected = [
      'scheme' => 'sendmail',
      'host' => 'default',
      'user' => NULL,
      'password' => NULL,
      'port' => NULL,
      'options' => [],
    ];
    $this->assertEquals($expected, $config->get('mailer_dsn'));
  }

}
