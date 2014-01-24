<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\InstallerTranslationTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\Component\Utility\NestedArray;
use Drupal\system\Tests\InstallerTest;

/**
 * Tests the installer translation detection.
 */
class InstallerTranslationTest extends InstallerTest {

  /**
   * Whether the installer has completed.
   *
   * @var bool
   */
  protected $isInstalled = FALSE;

  public static function getInfo() {
    return array(
      'name' => 'Installer translation test',
      'description' => 'Selects German as the installation language and verifies the following page is not in English.',
      'group' => 'Installer',
    );
  }

  protected function setUp() {
    $this->isInstalled = FALSE;

    $variable_groups = array(
      'system.file' => array(
        'path.private' =>  $this->private_files_directory,
        'path.temporary' => $this->temp_files_directory,
      ),
      'locale.settings' => array(
        'translation.path' => $this->translation_files_directory,
      ),
    );
    foreach ($variable_groups as $config_base => $variables) {
      foreach ($variables as $name => $value) {
        NestedArray::setValue($GLOBALS['conf'], array_merge(array($config_base), explode('.', $name)), $value);
      }
    }
    $settings['conf_path'] = (object) array(
      'value' => $this->public_files_directory,
      'required' => TRUE,
    );
    $settings['config_directories'] = (object) array(
      'value' => array(),
      'required' => TRUE,
    );
    $this->writeSettings($settings);

    // Submit the installer with German language.
    $this->drupalPostForm($GLOBALS['base_url'] . '/core/install.php', array('langcode' => 'de'), 'Save and continue');

    // On the following page where installation profile is being selected the
    // interface should be already translated, so there is no "Set up database"
    // text anymore.
    $this->assertNoText('Set up database', '"Set up database" string was not found.');

    // After this assertion all we needed to test is tested, but the test
    // expects the installation to succeed. If the test would finish here, an
    // exception would occur. That is why the full installation has to be
    // finished in the further steps.

    // Get the "Save and continue" submit button translated value from the
    // translated interface.
    $submit_value = (string) current($this->xpath('//input[@type="submit"]/@value'));

    // Submit the standard profile installation.
    $this->drupalPostForm(NULL, array('profile' => 'standard'), $submit_value);

    // Submit the next step.
    $this->drupalPostForm(NULL, array(), $submit_value);

    // Reload config directories.
    include $this->public_files_directory . '/settings.php';
    foreach ($config_directories as $type => $path) {
      $GLOBALS['config_directories'][$type] = $path;
    }
    $this->rebuildContainer();

    foreach ($variable_groups as $config_base => $variables) {
      $config = \Drupal::config($config_base);
      foreach ($variables as $name => $value) {
        $config->set($name, $value);
      }
      $config->save();
    }

    // Submit site configuration form.
    $this->drupalPostForm(NULL, array(
      'site_mail' => 'admin@test.de',
      'account[name]' => 'admin',
      'account[mail]' => 'admin@test.de',
      'account[pass][pass1]' => '123',
      'account[pass][pass2]' => '123',
      'site_default_country' => 'DE',
    ), $submit_value);

    // Use the test mail class instead of the default mail handler class.
    \Drupal::config('system.mail')->set('interface.default', 'Drupal\Core\Mail\TestMailCollector')->save();

    // When running from run-tests.sh we don't get an empty current path which
    // would indicate we're on the home page.
    $path = current_path();
    if (empty($path)) {
      _current_path('run-tests');
    }

    $this->isInstalled = TRUE;
  }

}
