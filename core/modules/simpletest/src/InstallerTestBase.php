<?php

namespace Drupal\simpletest;

@trigger_error(__NAMESPACE__ . '\InstallerTestBase is deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\FunctionalTests\Installer\InstallerTestBase, see https://www.drupal.org/node/2988752.', E_USER_DEPRECATED);

use Drupal\Core\DrupalKernel;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Site\Settings;
use Drupal\Tests\RequirementsPageTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Base class for testing the interactive installer.
 *
 * @deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0.
 * Use \Drupal\FunctionalTests\Installer\InstallerTestBase. See
 * https://www.drupal.org/node/2988752
 */
abstract class InstallerTestBase extends WebTestBase {

  use RequirementsPageTrait;

  /**
   * Custom settings.php values to write for a test run.
   *
   * @var array
   *   An array of settings to write out, in the format expected by
   *   drupal_rewrite_settings().
   */
  protected $settings = [];

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
  protected $parameters = [];

  /**
   * A string translation map used for translated installer screens.
   *
   * Keys are English strings, values are translated strings.
   *
   * @var array
   */
  protected $translations = [
    'Save and continue' => 'Save and continue',
  ];

  /**
   * Whether the installer has completed.
   *
   * @var bool
   */
  protected $isInstalled = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->isInstalled = FALSE;

    // Define information about the user 1 account.
    $this->rootUser = new UserSession([
      'uid' => 1,
      'name' => 'admin',
      'mail' => 'admin@example.com',
      'pass_raw' => $this->randomMachineName(),
    ]);

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

    // Set up a minimal container (required by WebTestBase). Set cookie and
    // server information so that XDebug works.
    // @see install_begin_request()
    $request = Request::create($GLOBALS['base_url'] . '/core/install.php', 'GET', [], $_COOKIE, [], $_SERVER);
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
      ->register('string_translation', 'Drupal\Core\StringTranslation\TranslationManager')
      ->addArgument(new Reference('language.default'));
    $this->container
      ->set('app.root', DRUPAL_ROOT);
    \Drupal::setContainer($this->container);

    $this->visitInstaller();

    // Select language.
    $this->setUpLanguage();

    // Select profile.
    $this->setUpProfile();

    // Address the requirements problem screen, if any.
    $this->setUpRequirementsProblem();

    // Configure settings.
    $this->setUpSettings();

    // @todo Allow test classes based on this class to act on further installer
    //   screens.

    // Configure site.
    $this->setUpSite();

    if ($this->isInstalled) {
      // Import new settings.php written by the installer.
      $request = Request::createFromGlobals();
      $class_loader = require $this->container->get('app.root') . '/autoload.php';
      Settings::initialize($this->container->get('app.root'), DrupalKernel::findSitePath($request), $class_loader);
      $this->configDirectories['sync'] = Settings::get('config_sync_directory');

      // After writing settings.php, the installer removes write permissions
      // from the site directory. To allow drupal_generate_test_ua() to write
      // a file containing the private key for drupal_valid_test_ua(), the site
      // directory has to be writable.
      // WebTestBase::tearDown() will delete the entire test site directory.
      // Not using File API; a potential error must trigger a PHP warning.
      chmod($this->container->get('app.root') . '/' . $this->siteDirectory, 0777);
      $this->kernel = DrupalKernel::createFromRequest($request, $class_loader, 'prod', FALSE);
      $this->kernel->boot();
      $this->kernel->preHandle($request);
      $this->container = $this->kernel->getContainer();
      // Ensure our request includes the session if appropriate.
      if (PHP_SAPI !== 'cli') {
        $request->setSession($this->container->get('session'));
      }

      // Manually configure the test mail collector implementation to prevent
      // tests from sending out emails and collect them in state instead.
      $this->container->get('config.factory')
        ->getEditable('system.mail')
        ->set('interface.default', 'test_mail_collector')
        ->save();
    }
  }

  /**
   * Visits the interactive installer.
   */
  protected function visitInstaller() {
    $this->drupalGet($GLOBALS['base_url'] . '/core/install.php');
  }

  /**
   * Installer step: Select language.
   */
  protected function setUpLanguage() {
    $edit = [
      'langcode' => $this->langcode,
    ];
    $this->drupalPostForm(NULL, $edit, $this->translations['Save and continue']);
  }

  /**
   * Installer step: Select installation profile.
   */
  protected function setUpProfile() {
    $edit = [
      'profile' => $this->profile,
    ];
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
   * Installer step: Requirements problem.
   *
   * Override this method to test specific requirements warnings or errors
   * during the installer.
   *
   * @see system_requirements()
   */
  protected function setUpRequirementsProblem() {
    // Do nothing.
  }

  /**
   * Final installer step: Configure site.
   */
  protected function setUpSite() {
    $edit = $this->translatePostValues($this->parameters['forms']['install_configure_form']);
    $this->drupalPostForm(NULL, $edit, $this->translations['Save and continue']);
    // If we've got to this point the site is installed using the regular
    // installation workflow.
    $this->isInstalled = TRUE;
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
