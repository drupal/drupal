<?php

namespace Drupal\FunctionalJavascriptTests\Core\Installer\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Test\HttpClientMiddleware\TestHttpClientMiddleware;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use GuzzleHttp\HandlerStack;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the select profile form.
 *
 * @group Installer
 */
class SelectProfileFormTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
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

    // Create a regular settings.php for testing.
    $this->prepareSettings();
    // @todo Using the APCu file cache causes problems in this test when
    //   combined with Symfony 4.4. This seems to be a memory issue caused by
    //   excessive deprecation errors caused by
    //   https://github.com/symfony/symfony/commit/7c01c4c80c69159b2b39ea8bc53431196d7b29fb
    //   The deprecations will be fixed in https://www.drupal.org/project/drupal/issues/3074585
    $settings['settings']['file_cache']['file_cache_disable'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

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
  }

  /**
   * {@inheritdoc}
   */
  protected function initMink() {
    // The temporary files directory doesn't exist yet, as install_base_system()
    // has not run. We need to create the template cache directory recursively.
    $path = $this->tempFilesDirectory . DIRECTORY_SEPARATOR . 'browsertestbase-templatecache';
    if (!file_exists($path)) {
      mkdir($path, 0777, TRUE);
    }

    parent::initMink();
  }

  /**
   * {@inheritdoc}
   *
   * BrowserTestBase::refreshVariables() tries to operate on persistent storage,
   * which is only available after the installer completed.
   */
  protected function refreshVariables() {
    // Intentionally empty as the site is not yet installed.
  }

  /**
   * Tests a warning message is displayed when the Umami profile is selected.
   */
  public function testUmamiProfileWarningMessage() {
    $this->drupalGet($GLOBALS['base_url'] . '/core/install.php');
    $edit = [
      'langcode' => 'en',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and continue');
    $page = $this->getSession()->getPage();
    $warning_message = $page->find('css', '.description .messages--warning');
    $this->assertFalse($warning_message->isVisible());
    $page->selectFieldOption('profile', 'demo_umami');
    $this->assertTrue($warning_message->isVisible());
  }

}
