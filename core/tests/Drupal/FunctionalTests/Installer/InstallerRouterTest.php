<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Test\PerformanceTestRecorder;

/**
 * Tests router rebuilding during installation.
 *
 * @group Installer
 */
class InstallerRouterTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'test_profile';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment(): void {
    parent::prepareEnvironment();
    $info = [
      'type' => 'profile',
      'core_version_requirement' => '*',
      'name' => 'Router testing profile',
      'install' => [
        'router_test',
        'router_installer_test',
      ],
    ];
    // File API functions are not available yet.
    $path = $this->siteDirectory . '/profiles/test_profile';
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/test_profile.info.yml", Yaml::encode($info));

    $settings_services_file = DRUPAL_ROOT . '/sites/default/default.services.yml';
    copy($settings_services_file, $this->siteDirectory . '/services.yml');
    PerformanceTestRecorder::registerService($this->siteDirectory . '/services.yml', TRUE);
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled(): void {
    $this->assertSession()->statusCodeEquals(200);
    // Ensures that router is not rebuilt unnecessarily during the install. It
    // is rebuilt during:
    // - router_test_install()
    // - router_installer_test_modules_installed()
    // - install_finished()
    $this->assertSame(3, \Drupal::service('core.performance.test.recorder')->getCount('event', RoutingEvents::FINISHED));
    $this->assertStringEndsWith('/core/install.php/router_installer_test/test1', \Drupal::state()->get('router_installer_test_modules_installed'));
    $this->assertStringEndsWith('/core/install.php/router_test/test1', \Drupal::state()->get('router_test_install'));
  }

}
