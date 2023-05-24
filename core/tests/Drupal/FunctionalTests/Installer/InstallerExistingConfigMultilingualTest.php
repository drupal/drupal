<?php

namespace Drupal\FunctionalTests\Installer;

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
   *
   * @todo Remove this and thus re-enable this test in
   *   https://www.drupal.org/project/drupal/issues/3361121
   */
  protected function setUp(): void {
    $this->markTestSkipped('Skipped due to frequent random test failures.');
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigTarball() {
    return __DIR__ . '/../../../fixtures/config_install/multilingual.tar.gz';
  }

}
