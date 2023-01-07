<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Site\Settings;
use Drupal\Core\Test\HttpClientMiddleware\TestHttpClientMiddleware;
use Drupal\Core\Utility\PhpRequirements;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;
use GuzzleHttp\HandlerStack;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Base class for testing the interactive installer.
 */
abstract class InstallerTestBase extends BrowserTestBase {

  use RequirementsPageTrait;

  /**
   * Custom settings.php values to write for a test run.
   *
   * @var array
   *   An array of settings to write out, in the format expected by
   *   SettingsEditor::rewrite().
   *
   * @see \Drupal\Core\Site\SettingsEditor::rewrite()
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
   * @see FunctionalTestSetupTrait::installParameters()
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
  protected function installParameters() {
    $params = parent::installParameters();
    // Set the checkbox values to FALSE so that
    // \Drupal\Tests\BrowserTestBase::translatePostValues() does not remove
    // them.
    $params['forms']['install_configure_form']['enable_update_status_module'] = FALSE;
    $params['forms']['install_configure_form']['enable_update_status_emails'] = FALSE;
    return $params;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUpAppRoot();

    $this->isInstalled = FALSE;

    $this->setupBaseUrl();

    $this->prepareDatabasePrefix();

    // Install Drupal test site.
    $this->prepareEnvironment();

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

    // Note that FunctionalTestSetupTrait::installParameters() returns form
    // input values suitable for a programmed
    // \Drupal::formBuilder()->submitForm().
    // @see InstallerTestBase::translatePostValues()
    $this->parameters = $this->installParameters();

    // Set up a minimal container (required by BrowserTestBase). Set cookie and
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
      ->register('http_client', 'GuzzleHttp\Client')
      ->setFactory('http_client_factory:fromOptions');
    $this->container
      ->register('http_client_factory', 'Drupal\Core\Http\ClientFactory')
      ->setArguments([new Reference('http_handler_stack')]);
    $handler_stack = HandlerStack::create();
    $test_http_client_middleware = new TestHttpClientMiddleware();
    $handler_stack->push($test_http_client_middleware(), 'test.http_client.middleware');
    $this->container
      ->set('http_handler_stack', $handler_stack);

    $this->container
      ->setParameter('app.root', DRUPAL_ROOT);
    \Drupal::setContainer($this->container);

    // Setup Mink.
    $this->initMink();

    // Set up the browser test output file.
    $this->initBrowserOutputFile();

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
      $class_loader = require $this->container->getParameter('app.root') . '/autoload.php';
      Settings::initialize($this->container->getParameter('app.root'), DrupalKernel::findSitePath($request), $class_loader);

      // After writing settings.php, the installer removes write permissions
      // from the site directory. To allow drupal_generate_test_ua() to write
      // a file containing the private key for drupal_valid_test_ua(), the site
      // directory has to be writable.
      // BrowserTestBase::tearDown() will delete the entire test site directory.
      // Not using File API; a potential error must trigger a PHP warning.
      chmod($this->container->getParameter('app.root') . '/' . $this->siteDirectory, 0777);
      $this->kernel = DrupalKernel::createFromRequest($request, $class_loader, 'prod', FALSE);
      $this->kernel->boot();
      $this->kernel->preHandle($request);
      $this->container = $this->kernel->getContainer();

      // Manually configure the test mail collector implementation to prevent
      // tests from sending out emails and collect them in state instead.
      $this->container->get('config.factory')
        ->getEditable('system.mail')
        ->set('interface.default', 'test_mail_collector')
        ->save();

      $this->installDefaultThemeFromClassProperty($this->container);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function initFrontPage() {
    // We don't want to visit the front page with the installer when
    // initializing Mink, so we do nothing here.
  }

  /**
   * Visits the interactive installer.
   */
  protected function visitInstaller() {
    $this->drupalGet($GLOBALS['base_url'] . '/core/install.php');
  }

  /**
   * Installer step: Select language.
   *
   * @see \Drupal\Core\Installer\Form\SelectLanguageForm
   */
  protected function setUpLanguage() {
    $edit = [
      'langcode' => $this->langcode,
    ];
    // The 'Select Language' step is always English.
    $this->submitForm($edit, 'Save and continue');
  }

  /**
   * Installer step: Select installation profile.
   */
  protected function setUpProfile() {
    $edit = [
      'profile' => $this->profile,
    ];
    $this->submitForm($edit, $this->translations['Save and continue']);
  }

  /**
   * Installer step: Configure settings.
   */
  protected function setUpSettings() {
    $edit = $this->translatePostValues($this->parameters['forms']['install_settings_form']);
    $this->submitForm($edit, $this->translations['Save and continue']);
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
    if (version_compare(phpversion(), PhpRequirements::getMinimumSupportedPhp()) < 0) {
      $this->continueOnExpectedWarnings(['PHP']);
    }
  }

  /**
   * Final installer step: Configure site.
   */
  protected function setUpSite() {
    $edit = $this->translatePostValues($this->parameters['forms']['install_configure_form']);
    $this->submitForm($edit, $this->translations['Save and continue']);
    // If we've got to this point the site is installed using the regular
    // installation workflow.
    $this->isInstalled = TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * FunctionalTestSetupTrait::refreshVariables() tries to operate on persistent
   * storage, which is only available after the installer completed.
   */
  protected function refreshVariables() {
    if ($this->isInstalled) {
      parent::refreshVariables();
    }
  }

}
