<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Logger\RfcLogLevel;

/**
 * Verifies that installing from existing configuration works.
 *
 * @group Installer
 */
class InstallerExistingConfigMultilingualTest extends InstallerExistingConfigTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_config_install_multilingual';

  /**
   * {@inheritdoc}
   */
  protected function getConfigTarball() {
    return __DIR__ . '/../../../fixtures/config_install/multilingual.tar.gz';
  }

  /**
   * {@inheritdoc}
   */
  public function testConfigSync(): void {
    parent::testConfigSync();

    // Ensure no warning, error, critical, alert or emergency messages have been
    // logged.
    $count = (int) \Drupal::database()->select('watchdog', 'w')->fields('w')->condition('severity', RfcLogLevel::WARNING, '<=')->countQuery()->execute()->fetchField();
    $this->assertSame(0, $count);

    // Ensure the correct message is logged from locale_config_batch_finished().
    $count = (int) \Drupal::database()->select('watchdog', 'w')->fields('w')->condition('message', 'The configuration was successfully updated. %number configuration objects updated.')->countQuery()->execute()->fetchField();
    $this->assertSame(1, $count);
  }

}
