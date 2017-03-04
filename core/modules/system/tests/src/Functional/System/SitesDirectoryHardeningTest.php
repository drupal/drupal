<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Drupal permissions hardening of /sites subdirectories.
 *
 * @group system
 */
class SitesDirectoryHardeningTest extends BrowserTestBase {
  use StringTranslationTrait;

  /**
   * Tests the default behavior to restrict directory permissions is enforced.
   *
   * Checks both the the current sites directory and settings.php.
   */
  public function testSitesDirectoryHardening() {
    $site_path = $this->kernel->getSitePath();
    $settings_file = $this->settingsFile($site_path);

    // First, we check based on what the initial install has set.
    $this->assertTrue(drupal_verify_install_file($site_path, FILE_NOT_WRITABLE, 'dir'), new FormattableMarkup('Verified permissions for @file.', ['@file' => $site_path]));

    // We intentionally don't check for settings.local.php as that file is
    // not created by Drupal.
    $this->assertTrue(drupal_verify_install_file($settings_file, FILE_EXIST | FILE_READABLE | FILE_NOT_WRITABLE), new FormattableMarkup('Verified permissions for @file.', ['@file' => $settings_file]));

    $this->makeWritable($site_path);
    $this->checkSystemRequirements();

    $this->assertTrue(drupal_verify_install_file($site_path, FILE_NOT_WRITABLE, 'dir'), new FormattableMarkup('Verified permissions for @file after manual permissions change.', ['@file' => $site_path]));
    $this->assertTrue(drupal_verify_install_file($settings_file, FILE_EXIST | FILE_READABLE | FILE_NOT_WRITABLE), new FormattableMarkup('Verified permissions for @file after manual permissions change.', ['@file' => $settings_file]));
  }

  /**
   * Tests writable files remain writable when directory hardening is disabled.
   */
  public function testSitesDirectoryHardeningConfig() {
    $site_path = $this->kernel->getSitePath();
    $settings_file = $this->settingsFile($site_path);

    // Disable permissions enforcement.
    $settings = Settings::getAll();
    $settings['skip_permissions_hardening'] = TRUE;
    new Settings($settings);
    $this->assertTrue(Settings::get('skip_permissions_hardening'), 'Able to set hardening to true');
    $this->makeWritable($site_path);

    // Manually trigger the requirements check.
    $requirements = $this->checkSystemRequirements();
    $this->assertEqual(REQUIREMENT_WARNING, $requirements['configuration_files']['severity'], 'Warning severity is properly set.');
    $this->assertEqual($this->t('Protection disabled'), (string) $requirements['configuration_files']['description']['#context']['configuration_error_list']['#items'][0], 'Description is properly set.');

    $this->assertTrue(is_writable($site_path), 'Site directory remains writable when automatically fixing permissions is disabled.');
    $this->assertTrue(is_writable($settings_file), 'settings.php remains writable when automatically fixing permissions is disabled.');

    // Re-enable permissions enforcement.
    $settings = Settings::getAll();
    $settings['skip_permissions_hardening'] = FALSE;
    new Settings($settings);

    // Manually trigger the requirements check.
    $this->checkSystemRequirements();

    $this->assertFalse(is_writable($site_path), 'Site directory is protected when automatically fixing permissions is enabled.');
    $this->assertFalse(is_writable($settings_file), 'settings.php is protected when automatically fixing permissions is enabled.');
  }

  /**
   * Checks system runtime requirements.
   *
   * @return array
   *   An array of system requirements.
   */
  protected function checkSystemRequirements() {
    module_load_install('system');
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
   * Returns the path to settings.php
   *
   * @param string $site_path
   *   The sites subdirectory path.
   *
   * @return string
   *   The path to settings.php.
   */
  protected function settingsFile($site_path) {
    $settings_file = $site_path . '/settings.php';
    return $settings_file;
  }

}
