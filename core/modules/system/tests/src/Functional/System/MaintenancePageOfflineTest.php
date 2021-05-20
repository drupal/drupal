<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests if the Maintenance page is served when the site is offline.
 *
 * @group system
 */
class MaintenancePageOfflineTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'test_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $settings_filename = $this->siteDirectory . '/settings.php';
    chmod($settings_filename, 0777);
    $settings_php = file_get_contents($settings_filename);
    // Ensure we can test errors rather than being caught in
    // \Drupal\Core\Test\HttpClientMiddleware\TestHttpClientMiddleware.
    $settings_php .= "\ndefine('SIMPLETEST_COLLECT_ERRORS', FALSE);\n";
    file_put_contents($settings_filename, $settings_php);
  }

  /**
   * Prepare settings values before a test case.
   *
   * @param string $maintenance_theme
   *   The name of a maintenance theme. Empty if there is no maintenance theme.
   * @param string $error_level
   *   The name of an error level.
   * @param bool $active_database
   *   TRUE if a database should be active.
   * @param bool $valid_hash_salt
   *   TRUE if a hash_salt should be valid.
   */
  protected function prepareCaseSettings($maintenance_theme, $error_level, $active_database = TRUE, $valid_hash_salt = TRUE) {
    $settings = [];
    if (!empty($maintenance_theme)) {
      $settings['settings']['maintenance_theme'] = (object) [
        'value' => $maintenance_theme,
        'required' => TRUE,
      ];
    }
    $settings['config']['system.logging']['error_level'] = (object) [
      'value' => $error_level,
      'required' => TRUE,
    ];
    if (!$active_database) {
      // Make a database inactive by setting an invalid password.
      $connection_info = Database::getConnectionInfo();
      $settings['databases']['default']['default']['password'] = (object) [
        'value' => $connection_info['default']['password'] . $this->randomMachineName(),
        'required' => TRUE,
      ];
    }
    if (!$valid_hash_salt) {
      // Set a hash_salt to invalid value.
      $settings['settings']['hash_salt'] = (object) [
        'value' => NULL,
        'required' => TRUE,
      ];
    }
    $this->writeSettings($settings);
  }

  /**
   * Tests cases when settings.php contains invalid database settings.
   *
   * Tests if the maintenance offline page is served when settings.php
   * contains invalid database settings.
   */
  public function testInvalidDatabaseSettings() {
    // Open a frontpage without any error.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Log in');

    // CASE 1 - a maintenance theme's template is picked up
    // and no errors are displayed.
    $this->prepareCaseSettings('test_theme', ERROR_REPORTING_HIDE, FALSE);
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(500);
    // A maintenance theme's offline template should be picked up.
    $this->assertSession()->pageTextContains('Service unavailable');
    $this->assertSession()->responseContains('<h1 class="title test-theme"');
    // A fatal error message and a backtrace should be hidden.
    $this->assertSession()->pageTextContains('The website encountered an unexpected error. Please try again later.');
    $this->assertSession()->pageTextNotContains('Access denied for user');
    $this->assertSession()->responseNotContains('<pre class="backtrace">');

    // CASE 2 - a maintenance theme's template is picked up
    // and all errors with a backtrace are displayed.
    $this->prepareCaseSettings('test_theme', ERROR_REPORTING_DISPLAY_VERBOSE, FALSE);
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(500);
    // A maintenance theme's offline template should be picked up.
    $this->assertSession()->pageTextContains('Service unavailable');
    $this->assertSession()->responseContains('<h1 class="title test-theme"');
    // A fatal error message and a backtrace should be shown.
    $this->assertSession()->pageTextContains('The website encountered an unexpected error. Please try again later.');
    $this->assertSession()->pageTextContains('Access denied for user');
    $this->assertSession()->responseContains('<pre class="backtrace">');

    // CASE 3 - a system's template is picked up
    // since a maintenance theme doesn't have the template
    // and no errors are displayed.
    $this->prepareCaseSettings('test_subtheme', ERROR_REPORTING_HIDE, FALSE);
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(500);
    // A system's offline template should be picked up.
    $this->assertSession()->pageTextContains('Service unavailable');
    $this->assertSession()->responseContains('<h1 class="title"');
    // A fatal error message and a backtrace should be hidden.
    $this->assertSession()->pageTextContains('The website encountered an unexpected error. Please try again later.');
    $this->assertSession()->pageTextNotContains('Access denied for user');
    $this->assertSession()->responseNotContains('<pre class="backtrace">');

    // CASE 4 - a system's template is picked up
    // since a maintenance theme is not set
    // and all errors with a backtrace are displayed.
    $this->prepareCaseSettings('', ERROR_REPORTING_DISPLAY_VERBOSE, FALSE);
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(500);
    // A system's offline template should be picked up.
    $this->assertSession()->pageTextContains('Service unavailable');
    $this->assertSession()->responseContains('<h1 class="title"');
    // A fatal error message and a backtrace should be shown.
    $this->assertSession()->pageTextContains('The website encountered an unexpected error. Please try again later.');
    $this->assertSession()->pageTextContains('Access denied for user');
    $this->assertSession()->responseContains('<pre class="backtrace">');
  }

  /**
   * Tests cases when settings.php doesn't have a hash_salt defined.
   *
   * Tests if the maintenance offline page is served when settings.php
   * originally did have a hash_salt defined, which is emptied out afterwards.
   */
  public function testRemovedHashSalt() {
    // Open a frontpage without any error.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Log in');

    // CASE 1 - a maintenance theme's template is picked up
    // and no errors are displayed.
    $this->prepareCaseSettings('test_theme', ERROR_REPORTING_HIDE, TRUE, FALSE);
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(500);
    // A maintenance theme's offline template should be picked up.
    $this->assertSession()->pageTextContains('Service unavailable');
    $this->assertSession()->responseContains('<h1 class="title test-theme"');
    // A fatal error message and a backtrace should be hidden.
    $this->assertSession()->pageTextContains('The website encountered an unexpected error. Please try again later.');
    $this->assertSession()->pageTextNotContains('Missing $settings[\'hash_salt\'] in settings.php');
    $this->assertSession()->responseNotContains('<pre class="backtrace">');

    // CASE 2 - a maintenance theme's template is picked up
    // and all errors with a backtrace are displayed.
    $this->prepareCaseSettings('test_theme', ERROR_REPORTING_DISPLAY_VERBOSE, TRUE, FALSE);
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(500);
    // A maintenance theme's offline template should be picked up.
    $this->assertSession()->pageTextContains('Service unavailable');
    $this->assertSession()->responseContains('<h1 class="title test-theme"');
    // A fatal error message and a backtrace should be shown.
    $this->assertSession()->pageTextContains('The website encountered an unexpected error. Please try again later.');
    $this->assertSession()->pageTextContains('Missing $settings[\'hash_salt\'] in settings.php');
    $this->assertSession()->responseContains('<pre class="backtrace">');

    // CASE 3 - a system's template is picked up
    // since a maintenance theme doesn't have the template
    // and no errors are displayed.
    $this->prepareCaseSettings('test_subtheme', ERROR_REPORTING_HIDE, TRUE, FALSE);
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(500);
    // A system's offline template should be picked up.
    $this->assertSession()->pageTextContains('Service unavailable');
    $this->assertSession()->responseContains('<h1 class="title"');
    // A fatal error message and a backtrace should be hidden.
    $this->assertSession()->pageTextContains('The website encountered an unexpected error. Please try again later.');
    $this->assertSession()->pageTextNotContains('Missing $settings[\'hash_salt\'] in settings.php');
    $this->assertSession()->responseNotContains('<pre class="backtrace">');

    // CASE 4 - a system's template is picked up
    // since a maintenance theme is not set
    // and all errors with a backtrace are displayed.
    $this->prepareCaseSettings('', ERROR_REPORTING_DISPLAY_VERBOSE, TRUE, FALSE);
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(500);
    // A system's offline template should be picked up.
    $this->assertSession()->pageTextContains('Service unavailable');
    $this->assertSession()->responseContains('<h1 class="title"');
    // A fatal error message and a backtrace should be shown.
    $this->assertSession()->pageTextContains('The website encountered an unexpected error. Please try again later.');
    $this->assertSession()->pageTextContains('Missing $settings[\'hash_salt\'] in settings.php');
    $this->assertSession()->responseContains('<pre class="backtrace">');
  }

}
