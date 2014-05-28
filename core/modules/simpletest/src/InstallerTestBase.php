<?php

/**
 * @file
 * Contains \Drupal\simpletest\InstallerTestBase.
 */

namespace Drupal\simpletest;

use Drupal\Core\Session\UserSession;

/**
 * Base class for testing the interactive installer.
 */
abstract class InstallerTestBase extends WebTestBase {

  /**
   * Custom settings.php values to write for a test run.
   *
   * @var array
   *   An array of settings to write out, in the format expected by
   *   drupal_rewrite_settings().
   */
  protected $settings = array();

  /**
   * The language code in which to install Drupal.
   *
   * @var string
   */
  protected $langcode = 'en';

  /**
   * The installation profile to install.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Additional parameters to use for installer screens.
   *
   * @see WebTestBase::installParameters()
   *
   * @var array
   */
  protected $parameters = array();

  /**
   * A string translation map used for translated installer screens.
   *
   * Keys are English strings, values are translated strings.
   *
   * @var array
   */
  protected $translations = array(
    'Save and continue' => 'Save and continue',
  );

  /**
   * Whether the installer has completed.
   *
   * @var bool
   */
  protected $isInstalled = FALSE;

  /**
   * Overrides WebTestBase::setUp().
   */
  protected function setUp() {
    $this->isInstalled = FALSE;

    // Define information about the user 1 account.
    $this->root_user = new UserSession(array(
      'uid' => 1,
      'name' => 'admin',
      'mail' => 'admin@example.com',
      'pass_raw' => $this->randomName(),
    ));

    // If any $settings are defined for this test, copy and prepare an actual
    // settings.php, so as to resemble a regular installation.
    if (!empty($this->settings)) {
      // Not using File API; a potential error must trigger a PHP warning.
      copy(DRUPAL_ROOT . '/sites/default/default.settings.php', DRUPAL_ROOT . '/' . $this->siteDirectory . '/settings.php');
      $this->writeSettings($this->settings);
    }

    // Note that WebTestBase::installParameters() returns form input values
    // suitable for a programmed drupal_form_submit().
    // @see WebTestBase::translatePostValues()
    $this->parameters = $this->installParameters();

    $this->drupalGet($GLOBALS['base_url'] . '/core/install.php');

    // Select language.
    $this->setUpLanguage();

    // Select profile.
    $this->setUpProfile();

    // Configure settings.
    $this->setUpSettings();

    // @todo Allow test classes based on this class to act on further installer
    //   screens.

    // Configure site.
    $this->setUpSite();

    // Import new settings.php written by the installer.
    drupal_settings_initialize();
    foreach ($GLOBALS['config_directories'] as $type => $path) {
      $this->configDirectories[$type] = $path;
    }

    // After writing settings.php, the installer removes write permissions
    // from the site directory. To allow drupal_generate_test_ua() to write
    // a file containing the private key for drupal_valid_test_ua(), the site
    // directory has to be writable.
    // WebTestBase::tearDown() will delete the entire test site directory.
    // Not using File API; a potential error must trigger a PHP warning.
    chmod(DRUPAL_ROOT . '/' . $this->siteDirectory, 0777);

    $this->rebuildContainer();

    // Manually configure the test mail collector implementation to prevent
    // tests from sending out e-mails and collect them in state instead.
    \Drupal::config('system.mail')
      ->set('interface.default', 'test_mail_collector')
      ->save();

    // When running from run-tests.sh we don't get an empty current path which
    // would indicate we're on the home page.
    $path = current_path();
    if (empty($path)) {
      _current_path('run-tests');
    }

    $this->isInstalled = TRUE;
  }

  /**
   * Installer step: Select language.
   */
  protected function setUpLanguage() {
    $edit = array(
      'langcode' => $this->langcode,
    );
    $this->drupalPostForm(NULL, $edit, $this->translations['Save and continue']);
  }

  /**
   * Installer step: Select installation profile.
   */
  protected function setUpProfile() {
    $edit = array(
      'profile' => $this->profile,
    );
    $this->drupalPostForm(NULL, $edit, $this->translations['Save and continue']);
  }

  /**
   * Installer step: Configure settings.
   */
  protected function setUpSettings() {
    $edit = $this->translatePostValues($this->parameters['forms']['install_settings_form']);
    $this->drupalPostForm(NULL, $edit, $this->translations['Save and continue']);
  }

  /**
   * Installer step: Configure site.
   */
  protected function setUpSite() {
    $edit = $this->translatePostValues($this->parameters['forms']['install_configure_form']);
    $this->drupalPostForm(NULL, $edit, $this->translations['Save and continue']);
  }

  /**
   * {@inheritdoc}
   *
   * WebTestBase::refreshVariables() tries to operate on persistent storage,
   * which is only available after the installer completed.
   */
  protected function refreshVariables() {
    if ($this->isInstalled) {
      parent::refreshVariables();
    }
  }

}
