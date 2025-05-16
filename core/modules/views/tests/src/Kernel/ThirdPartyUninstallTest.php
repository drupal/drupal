<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\views\Entity\View;

/**
 * Tests proper removal of third-party settings from views.
 *
 * @group views
 */
class ThirdPartyUninstallTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views_third_party_settings_test'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_third_party_uninstall'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests removing third-party settings when a provider module is uninstalled.
   */
  public function testThirdPartyUninstall(): void {
    $view = View::load('test_third_party_uninstall');
    $this->assertNotEmpty($view);
    $this->assertContains('views_third_party_settings_test', $view->getDependencies()['module']);
    $this->assertTrue($view->getThirdPartySetting('views_third_party_settings_test', 'example_setting'));

    \Drupal::service('module_installer')->uninstall(['views_third_party_settings_test']);

    $view = View::load('test_third_party_uninstall');
    $this->assertNotEmpty($view);
    $this->assertNotContains('views_third_party_settings_test', $view->getDependencies()['module']);
    $this->assertNull($view->getThirdPartySetting('views_third_party_settings_test', 'example_setting'));
  }

}
