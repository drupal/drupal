<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Test\PerformanceTestRecorder;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the interactive installer.
 *
 * @group Installer
 */
class InstallerPerformanceTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected function prepareSettings() {
    parent::prepareSettings();
    PerformanceTestRecorder::registerService($this->siteDirectory . '/services.yml', FALSE);
  }

  /**
   * Ensures that the user page is available after installation.
   */
  public function testInstaller() {
    // Ensures that router is not rebuilt unnecessarily during the install.
    // Currently it is built once during the install in install_finished().
    $this->assertSame(1, \Drupal::service('core.performance.test.recorder')->getCount('event', RoutingEvents::FINISHED));
  }

}
