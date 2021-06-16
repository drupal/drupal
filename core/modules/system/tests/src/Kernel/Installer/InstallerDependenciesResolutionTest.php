<?php

namespace Drupal\Tests\system\Kernel\Installer;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that we handle module dependency resolution during install.
 *
 * @group Installer
 */
class InstallerDependenciesResolutionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Verifies that the exception message in the profile step is correct.
   */
  public function testDependenciesResolution() {
    // Prime the drupal_get_filename() static cache with the location of the
    // testing profile as it is not the currently active profile and we don't
    // yet have any cached way to retrieve its location.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    drupal_get_filename('profile', 'testing_missing_dependencies', 'core/profiles/testing_missing_dependencies/testing_missing_dependencies.info.yml');

    $info = drupal_verify_profile([
      'parameters' => ['profile' => 'testing_missing_dependencies'],
      'profile_info' => install_profile_info('testing_missing_dependencies'),
    ]);

    $message = $info['required_modules']['description']->render();
    $this->assertStringContainsString('Fictional', $message);
    $this->assertStringContainsString('Missing_module1', $message);
    $this->assertStringContainsString('Missing_module2', $message);
    $this->assertStringNotContainsString('Block', $message);
    $this->assertStringNotContainsString('Node', $message);
  }

}
