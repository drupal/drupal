<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Core\Site\Settings;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Drupal permissions hardening of /sites subdirectories.
 *
 * @group system
 */
class SitesDirectoryHardeningTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the default behavior to restrict directory permissions is enforced.
   *
   * Checks both the current sites directory and settings.php.
   */
  public function testSitesDirectoryHardening(): void {
    $site_path = $this->kernel->getSitePath();
    $settings_file = $this->settingsFile($site_path);

    // First, we check based on what the initial install has set.
    $this->assertTrue(drupal_verify_install_file($site_path, FILE_NOT_WRITABLE, 'dir'), "Verified permissions for $site_path.");

    // We intentionally don't check for settings.local.php as that file is
    // not created by Drupal.
    $this->assertTrue(drupal_verify_install_file($settings_file, FILE_EXIST | FILE_READABLE | FILE_NOT_WRITABLE), "Verified permissions for $settings_file.");

    $this->makeWritable($site_path);
    $this->checkSystemRequirements();

    $this->assertTrue(drupal_verify_install_file($site_path, FILE_NOT_WRITABLE, 'dir'), "Verified permissions for $site_path after manual permissions change.");
    $this->assertTrue(drupal_verify_install_file($settings_file, FILE_EXIST | FILE_READABLE | FILE_NOT_WRITABLE), "Verified permissions for $settings_file after manual permissions change.");
  }

  /**
   * Tests writable files remain writable when directory hardening is disabled.
   */
  public function testSitesDirectoryHardeningConfig(): void {
    $site_path = $this->kernel->getSitePath();
    $settings_file = $this->settingsFile($site_path);

    // Disable permissions enforcement.
    $settings = Settings::getAll();
    $settings['skip_permissions_hardening'] = TRUE;
    new Settings($settings);
    $this->assertTrue(Settings::get('skip_permissions_hardening'), 'Able to set skip permissions hardening to true.');
    $this->makeWritable($site_path);

    // Manually trigger the requirements check.
    $requirements = $this->checkSystemRequirements();
    $this->assertEquals(REQUIREMENT_WARNING, $requirements['configuration_files']['severity'], 'Warning severity is properly set.');
    $this->assertEquals('Protection disabled', (string) $requirements['configuration_files']['value']);
    $description = strip_tags((string) \Drupal::service('renderer')->renderInIsolation($requirements['configuration_files']['description']));
    $this->assertStringContainsString('settings.php is not protected from modifications and poses a security risk.', $description);
    $this->assertStringContainsString('services.yml is not protected from modifications and poses a security risk.', $description);

    // Verify that site directory and the settings.php remain writable when
    // automatically enforcing file permissions is disabled.
    $this->assertDirectoryIsWritable($site_path);
    $this->assertFileIsWritable($settings_file);

    // Re-enable permissions enforcement.
    $settings = Settings::getAll();
    $settings['skip_permissions_hardening'] = FALSE;
    new Settings($settings);

    // Manually trigger the requirements check.
    $requirements = $this->checkSystemRequirements();
    $this->assertEquals('Protected', (string) $requirements['configuration_files']['value']);

    // Verify that site directory and the settings.php remain protected when
    // automatically enforcing file permissions is enabled.
    $this->assertDirectoryIsNotWritable($site_path);
    $this->assertFileIsNotWritable($settings_file);
  }

  /**
   * Checks system runtime requirements.
   *
   * @return array
   *   An array of system requirements.
   */
  protected function checkSystemRequirements() {
    \Drupal::moduleHandler()->loadInclude('system', 'install');
    return system_requirements('runtime');
  }

  /**
   * Makes the given path and settings file writable.
   *
   * @param string $site_path
   *   The sites directory path, such as 'sites/default'.
   */
  protected function makeWritable($site_path) {
    chmod($site_path, 0755);
    chmod($this->settingsFile($site_path), 0644);
  }

  /**
   * Returns the path to settings.php.
   *
   * @param string $site_path
   *   The sites subdirectory path.
   *
   * @return string
   *   The path to settings.php.
   */
  protected function settingsFile($site_path): string {
    $settings_file = $site_path . '/settings.php';
    return $settings_file;
  }

}
