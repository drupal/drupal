<?php

/**
 * @file
 * Contains \Drupal\simpletest\InstallerTestBase.
 */

namespace Drupal\simpletest;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
    $this->rootUser = new UserSession(array(
      'uid' => 1,
      'name' => 'admin',
      'mail' => 'admin@example.com',
      'pass_raw' => $this->randomMachineName(),
    ));

    // If any $settings are defined for this test, copy and prepare an actual
    // settings.php, so as to resemble a regular installation.
    if (!empty($this->settings)) {
      // Not using File API; a potential error must trigger a PHP warning.
      copy(DRUPAL_ROOT . '/sites/default/default.settings.php', DRUPAL_ROOT . '/' . $this->siteDirectory . '/settings.php');
      $this->writeSettings($this->settings);
    }

    // Note that WebTestBase::installParameters() returns form input values
    // suitable for a programmed \Drupal::formBuilder()->submitForm().
    // @see WebTestBase::translatePostValues()
    $this->parameters = $this->installParameters();

    // Set up a minimal container (required by WebTestBase).
    // @see install_begin_request()
    $request = Request::create($GLOBALS['base_url'] . '/core/install.php');
    $this->container = new ContainerBuilder();
    $request_stack = new RequestStack();
    $request_stack->push($request);
    $this->container
      ->set('request_stack', $request_stack);
    $this->container
      ->setParameter('language.default_values', Language::$defaultValues);
    $this->container
      ->register('language.default', 'Drupal\Core\Language\LanguageDefault')
      ->addArgument('%language.default_values%');
    $this->container
      ->register('language_manager', 'Drupal\Core\Language\LanguageManager')
      ->addArgument(new Reference('language.default'));
    $this->container
      ->register('string_translation', 'Drupal\Core\StringTranslation\TranslationManager')
      ->addArgument(new Reference('language_manager'));
    $this->container
      ->set('app.root', DRUPAL_ROOT);
    \Drupal::setContainer($this->container);

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
    $request = Request::createFromGlobals();
    $class_loader = require $this->container->get('app.root') . '/core/vendor/autoload.php';
    Settings::initialize($this->container->get('app.root'), DrupalKernel::findSitePath($request), $class_loader);
    foreach ($GLOBALS['config_directories'] as $type => $path) {
      $this->configDirectories[$type] = $path;
    }

    // After writing settings.php, the installer removes write permissions
    // from the site directory. To allow drupal_generate_test_ua() to write
    // a file containing the private key for drupal_valid_test_ua(), the site
    // directory has to be writable.
    // WebTestBase::tearDown() will delete the entire test site directory.
    // Not using File API; a potential error must trigger a PHP warning.
    chmod($this->container->get('app.root') . '/' . $this->siteDirectory, 0777);
    $this->kernel = DrupalKernel::createFromRequest($request, $class_loader, 'prod', FALSE);
    $this->kernel->prepareLegacyRequest($request);
    $this->container = $this->kernel->getContainer();
    $config = $this->container->get('config.factory');

    // Manually configure the test mail collector implementation to prevent
    // tests from sending out e-mails and collect them in state instead.
    $config->getEditable('system.mail')
      ->set('interface.default', 'test_mail_collector')
      ->save();

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
