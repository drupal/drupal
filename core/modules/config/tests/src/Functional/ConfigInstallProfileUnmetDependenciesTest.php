<?php

declare(strict_types=1);

namespace Drupal\Tests\config\Functional;

use Drupal\FunctionalTests\Installer\InstallerTestBase;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\RequirementsPageTrait;

/**
 * Tests install profile config overrides can not add unmet dependencies.
 *
 * @group Config
 */
class ConfigInstallProfileUnmetDependenciesTest extends InstallerTestBase {

  use RequirementsPageTrait;

  /**
   * The installation profile to install.
   *
   * @var string
   */
  protected $profile = 'testing_config_overrides';

  /**
   * Contains the expected exception if it is thrown.
   *
   * @var \Drupal\Core\Config\UnmetDependenciesException
   */
  protected $expectedException = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment(): void {
    parent::prepareEnvironment();
    $this->copyTestingOverrides();
  }

  /**
   * {@inheritdoc}
   */
  public function setUpSettings(): void {
    // During set up an UnmetDependenciesException should be thrown, which will
    // be re-thrown by TestHttpClientMiddleware as a standard Exception.
    try {
      parent::setUpSettings();
    }
    catch (\Exception $exception) {
      $this->expectedException = $exception;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setUpSite(): void {
    // This step can no longer be reached.
  }

  /**
   * Copy the testing_config_overrides install profile.
   *
   * So we can change the configuration to include a dependency that can not be
   * met. File API functions are not available yet.
   */
  protected function copyTestingOverrides(): void {
    $dest = $this->siteDirectory . '/profiles/testing_config_overrides';
    mkdir($dest, 0777, TRUE);
    $source = DRUPAL_ROOT . '/core/profiles/tests/testing_config_overrides';
    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $item) {
      if ($item->isDir()) {
        mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
      }
      else {
        copy((string) $item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
      }
    }

    // Add a dependency that can not be met.
    $config_file = $dest . DIRECTORY_SEPARATOR . InstallStorage::CONFIG_INSTALL_DIRECTORY . DIRECTORY_SEPARATOR . 'system.action.user_block_user_action.yml';
    $action = Yaml::decode(file_get_contents($config_file));
    $action['dependencies']['module'][] = 'does_not_exist';
    file_put_contents($config_file, Yaml::encode($action));
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled(): void {
    if ($this->expectedException) {
      $this->assertStringContainsString('Configuration objects provided by <em class="placeholder">testing_config_overrides</em> have unmet dependencies: <em class="placeholder">system.action.user_block_user_action (does_not_exist)</em>', $this->expectedException->getMessage());
      $this->assertStringContainsString('Drupal\Core\Config\UnmetDependenciesException', $this->expectedException->getMessage());
    }
    else {
      $this->fail('Expected Drupal\Core\Config\UnmetDependenciesException exception not thrown');
    }
  }

}
