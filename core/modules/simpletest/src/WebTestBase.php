<?php

/**
 * @file
 * Contains \Drupal\simpletest\WebTestBase.
 */

namespace Drupal\simpletest;

use Drupal\block\Entity\Block;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Zend\Diactoros\Uri;

/**
 * Test case for typical Drupal tests.
 *
 * @ingroup testing
 */
abstract class WebTestBase extends TestBase {

  use AssertContentTrait;

  use UserCreationTrait {
    createUser as drupalCreateUser;
    createRole as drupalCreateRole;
    createAdminRole as drupalCreateAdminRole;
  }

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * The URL currently loaded in the internal browser.
   *
   * @var string
   */
  protected $url;

  /**
   * The handle of the current cURL connection.
   *
   * @var resource
   */
  protected $curlHandle;

  /**
   * Whether or not to assert the presence of the X-Drupal-Ajax-Token.
   *
   * @var bool
   */
  protected $assertAjaxHeader = TRUE;

  /**
   * The headers of the page currently loaded in the internal browser.
   *
   * @var Array
   */
  protected $headers;

  /**
   * The cookies of the page currently loaded in the internal browser.
   *
   * @var array
   */
  protected $cookies = array();

  /**
   * Indicates that headers should be dumped if verbose output is enabled.
   *
   * Headers are dumped to verbose by drupalGet(), drupalHead(), and
   * drupalPostForm().
   *
   * @var bool
   */
  protected $dumpHeaders = FALSE;

  /**
   * The current user logged in using the internal browser.
   *
   * @var \Drupal\Core\Session\AccountInterface|bool
   */
  protected $loggedInUser = FALSE;

  /**
   * The "#1" admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $rootUser;


  /**
   * The current cookie file used by cURL.
   *
   * We do not reuse the cookies in further runs, so we do not need a file
   * but we still need cookie handling, so we set the jar to NULL.
   */
  protected $cookieFile = NULL;

  /**
   * Additional cURL options.
   *
   * \Drupal\simpletest\WebTestBase itself never sets this but always obeys what
   * is set.
   */
  protected $additionalCurlOptions = array();

  /**
   * The original batch, before it was changed for testing purposes.
   *
   * @var array
   */
  protected $originalBatch;

  /**
   * The original user, before it was changed to a clean uid = 1 for testing.
   *
   * @var object
   */
  protected $originalUser = NULL;

  /**
   * The original shutdown handlers array, before it was cleaned for testing.
   *
   * @var array
   */
  protected $originalShutdownCallbacks = array();

  /**
   * The current session ID, if available.
   */
  protected $sessionId = NULL;

  /**
   * Whether the files were copied to the test files directory.
   */
  protected $generatedTestFiles = FALSE;

  /**
   * The maximum number of redirects to follow when handling responses.
   */
  protected $maximumRedirects = 5;

  /**
   * The number of redirects followed during the handling of a request.
   */
  protected $redirectCount;


  /**
   * The number of meta refresh redirects to follow, or NULL if unlimited.
   *
   * @var null|int
   */
  protected $maximumMetaRefreshCount = NULL;

  /**
   * The number of meta refresh redirects followed during ::drupalGet().
   *
   * @var int
   */
  protected $metaRefreshCount = 0;

  /**
   * The kernel used in this test.
   *
   * @var \Drupal\Core\DrupalKernel
   */
  protected $kernel;

  /**
   * The config directories used in this test.
   */
  protected $configDirectories = array();

  /**
   * Cookies to set on curl requests.
   *
   * @var array
   */
  protected $curlCookies = array();

  /**
   * An array of custom translations suitable for drupal_rewrite_settings().
   *
   * @var array
   */
  protected $customTranslations;

  /**
   * The class loader to use for installation and initialization of setup.
   *
   * @var \Symfony\Component\Classloader\Classloader
   */
  protected $classLoader;

  /**
   * Constructor for \Drupal\simpletest\WebTestBase.
   */
  function __construct($test_id = NULL) {
    parent::__construct($test_id);
    $this->skipClasses[__CLASS__] = TRUE;
    $this->classLoader = require DRUPAL_ROOT . '/autoload.php';
  }

  /**
   * Get a node from the database based on its title.
   *
   * @param $title
   *   A node title, usually generated by $this->randomMachineName().
   * @param $reset
   *   (optional) Whether to reset the entity cache.
   *
   * @return \Drupal\node\NodeInterface
   *   A node entity matching $title.
   */
  function drupalGetNodeByTitle($title, $reset = FALSE) {
    if ($reset) {
      \Drupal::entityManager()->getStorage('node')->resetCache();
    }
    $nodes = entity_load_multiple_by_properties('node', array('title' => $title));
    // Load the first node returned from the database.
    $returned_node = reset($nodes);
    return $returned_node;
  }

  /**
   * Creates a node based on default settings.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the node, as used in
   *   entity_create(). Override the defaults by specifying the key and value
   *   in the array, for example:
   *   @code
   *     $this->drupalCreateNode(array(
   *       'title' => t('Hello, world!'),
   *       'type' => 'article',
   *     ));
   *   @endcode
   *   The following defaults are provided:
   *   - body: Random string using the default filter format:
   *     @code
   *       $settings['body'][0] = array(
   *         'value' => $this->randomMachineName(32),
   *         'format' => filter_default_format(),
   *       );
   *     @endcode
   *   - title: Random string.
   *   - type: 'page'.
   *   - uid: The currently logged in user, or anonymous.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node entity.
   */
  protected function drupalCreateNode(array $settings = array()) {
    // Populate defaults array.
    $settings += array(
      'body'      => array(array(
        'value' => $this->randomMachineName(32),
        'format' => filter_default_format(),
      )),
      'title'     => $this->randomMachineName(8),
      'type'      => 'page',
      'uid'       => \Drupal::currentUser()->id(),
    );
    $node = entity_create('node', $settings);
    $node->save();

    return $node;
  }

  /**
   * Creates a custom content type based on default settings.
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *   Example: 'type' => 'foo'.
   *
   * @return \Drupal\node\Entity\NodeType
   *   Created content type.
   */
  protected function drupalCreateContentType(array $values = array()) {
    // Find a non-existent random type name.
    if (!isset($values['type'])) {
      do {
        $id = strtolower($this->randomMachineName(8));
      } while (NodeType::load($id));
    }
    else {
      $id = $values['type'];
    }
    $values += array(
      'type' => $id,
      'name' => $id,
    );
    $type = entity_create('node_type', $values);
    $status = $type->save();
    node_add_body_field($type);
    \Drupal::service('router.builder')->rebuild();

    $this->assertEqual($status, SAVED_NEW, SafeMarkup::format('Created content type %type.', array('%type' => $type->id())));

    return $type;
  }

  /**
   * Builds the renderable view of an entity.
   *
   * Entities postpone the composition of their renderable arrays to #pre_render
   * functions in order to maximize cache efficacy. This means that the full
   * renderable array for an entity is constructed in drupal_render(). Some
   * tests require the complete renderable array for an entity outside of the
   * drupal_render process in order to verify the presence of specific values.
   * This method isolates the steps in the render process that produce an
   * entity's renderable array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to prepare a renderable array for.
   * @param string $view_mode
   *   (optional) The view mode that should be used to build the entity.
   * @param null $langcode
   *   (optional) For which language the entity should be prepared, defaults to
   *   the current content language.
   * @param bool $reset
   *   (optional) Whether to clear the cache for this entity.
   * @return array
   *
   * @see drupal_render()
   */
  protected function drupalBuildEntityView(EntityInterface $entity, $view_mode = 'full', $langcode = NULL, $reset = FALSE) {
    $ensure_fully_built = function(&$elements) use (&$ensure_fully_built) {
      // If the default values for this element have not been loaded yet, populate
      // them.
      if (isset($elements['#type']) && empty($elements['#defaults_loaded'])) {
        $elements += \Drupal::service('element_info')->getInfo($elements['#type']);
      }

      // Make any final changes to the element before it is rendered. This means
      // that the $element or the children can be altered or corrected before the
      // element is rendered into the final text.
      if (isset($elements['#pre_render'])) {
        foreach ($elements['#pre_render'] as $callable) {
          $elements = call_user_func($callable, $elements);
        }
      }

      // And recurse.
      $children = Element::children($elements, TRUE);
      foreach ($children as $key) {
        $ensure_fully_built($elements[$key]);
      }
    };

    $render_controller = $this->container->get('entity.manager')->getViewBuilder($entity->getEntityTypeId());
    if ($reset) {
      $render_controller->resetCache(array($entity->id()));
    }
    $build = $render_controller->view($entity, $view_mode, $langcode);
    $ensure_fully_built($build);

    return $build;
  }

  /**
   * Creates a block instance based on default settings.
   *
   * @param string $plugin_id
   *   The plugin ID of the block type for this block instance.
   * @param array $settings
   *   (optional) An associative array of settings for the block entity.
   *   Override the defaults by specifying the key and value in the array, for
   *   example:
   *   @code
   *     $this->drupalPlaceBlock('system_powered_by_block', array(
   *       'label' => t('Hello, world!'),
   *     ));
   *   @endcode
   *   The following defaults are provided:
   *   - label: Random string.
   *   - ID: Random string.
   *   - region: 'sidebar_first'.
   *   - theme: The default theme.
   *   - visibility: Empty array.
   *   - cache: array('max_age' => Cache::PERMANENT).
   *
   * @return \Drupal\block\Entity\Block
   *   The block entity.
   *
   * @todo
   *   Add support for creating custom block instances.
   */
  protected function drupalPlaceBlock($plugin_id, array $settings = array()) {
    $settings += array(
      'plugin' => $plugin_id,
      'region' => 'sidebar_first',
      'id' => strtolower($this->randomMachineName(8)),
      'theme' => $this->config('system.theme')->get('default'),
      'label' => $this->randomMachineName(8),
      'visibility' => array(),
      'weight' => 0,
      'cache' => array(
        'max_age' => Cache::PERMANENT,
      ),
    );
    $values = [];
    foreach (array('region', 'id', 'theme', 'plugin', 'weight', 'visibility') as $key) {
      $values[$key] = $settings[$key];
      // Remove extra values that do not belong in the settings array.
      unset($settings[$key]);
    }
    foreach ($values['visibility'] as $id => $visibility) {
      $values['visibility'][$id]['id'] = $id;
    }
    $values['settings'] = $settings;
    $block = entity_create('block', $values);
    $block->save();
    return $block;
  }

  /**
   * Checks to see whether a block appears on the page.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block entity to find on the page.
   */
  protected function assertBlockAppears(Block $block) {
    $result = $this->findBlockInstance($block);
    $this->assertTrue(!empty($result), format_string('Ensure the block @id appears on the page', array('@id' => $block->id())));
  }

  /**
   * Checks to see whether a block does not appears on the page.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block entity to find on the page.
   */
  protected function assertNoBlockAppears(Block $block) {
    $result = $this->findBlockInstance($block);
    $this->assertFalse(!empty($result), format_string('Ensure the block @id does not appear on the page', array('@id' => $block->id())));
  }

  /**
   * Find a block instance on the page.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block entity to find on the page.
   *
   * @return array
   *   The result from the xpath query.
   */
  protected function findBlockInstance(Block $block) {
    return $this->xpath('//div[@id = :id]', array(':id' => 'block-' . $block->id()));
  }

  /**
   * Gets a list of files that can be used in tests.
   *
   * The first time this method is called, it will call
   * simpletest_generate_file() to generate binary and ASCII text files in the
   * public:// directory. It will also copy all files in
   * core/modules/simpletest/files to public://. These contain image, SQL, PHP,
   * JavaScript, and HTML files.
   *
   * All filenames are prefixed with their type and have appropriate extensions:
   * - text-*.txt
   * - binary-*.txt
   * - html-*.html and html-*.txt
   * - image-*.png, image-*.jpg, and image-*.gif
   * - javascript-*.txt and javascript-*.script
   * - php-*.txt and php-*.php
   * - sql-*.txt and sql-*.sql
   *
   * Any subsequent calls will not generate any new files, or copy the files
   * over again. However, if a test class adds a new file to public:// that
   * is prefixed with one of the above types, it will get returned as well, even
   * on subsequent calls.
   *
   * @param $type
   *   File type, possible values: 'binary', 'html', 'image', 'javascript',
   *   'php', 'sql', 'text'.
   * @param $size
   *   (optional) File size in bytes to match. Defaults to NULL, which will not
   *   filter the returned list by size.
   *
   * @return
   *   List of files in public:// that match the filter(s).
   */
  protected function drupalGetTestFiles($type, $size = NULL) {
    if (empty($this->generatedTestFiles)) {
      // Generate binary test files.
      $lines = array(64, 1024);
      $count = 0;
      foreach ($lines as $line) {
        simpletest_generate_file('binary-' . $count++, 64, $line, 'binary');
      }

      // Generate ASCII text test files.
      $lines = array(16, 256, 1024, 2048, 20480);
      $count = 0;
      foreach ($lines as $line) {
        simpletest_generate_file('text-' . $count++, 64, $line, 'text');
      }

      // Copy other test files from simpletest.
      $original = drupal_get_path('module', 'simpletest') . '/files';
      $files = file_scan_directory($original, '/(html|image|javascript|php|sql)-.*/');
      foreach ($files as $file) {
        file_unmanaged_copy($file->uri, PublicStream::basePath());
      }

      $this->generatedTestFiles = TRUE;
    }

    $files = array();
    // Make sure type is valid.
    if (in_array($type, array('binary', 'html', 'image', 'javascript', 'php', 'sql', 'text'))) {
      $files = file_scan_directory('public://', '/' . $type . '\-.*/');

      // If size is set then remove any files that are not of that size.
      if ($size !== NULL) {
        foreach ($files as $file) {
          $stats = stat($file->uri);
          if ($stats['size'] != $size) {
            unset($files[$file->uri]);
          }
        }
      }
    }
    usort($files, array($this, 'drupalCompareFiles'));
    return $files;
  }

  /**
   * Compare two files based on size and file name.
   */
  protected function drupalCompareFiles($file1, $file2) {
    $compare_size = filesize($file1->uri) - filesize($file2->uri);
    if ($compare_size) {
      // Sort by file size.
      return $compare_size;
    }
    else {
      // The files were the same size, so sort alphabetically.
      return strnatcmp($file1->name, $file2->name);
    }
  }

  /**
   * Log in a user with the internal browser.
   *
   * If a user is already logged in, then the current user is logged out before
   * logging in the specified user.
   *
   * Please note that neither the current user nor the passed-in user object is
   * populated with data of the logged in user. If you need full access to the
   * user object after logging in, it must be updated manually. If you also need
   * access to the plain-text password of the user (set by drupalCreateUser()),
   * e.g. to log in the same user again, then it must be re-assigned manually.
   * For example:
   * @code
   *   // Create a user.
   *   $account = $this->drupalCreateUser(array());
   *   $this->drupalLogin($account);
   *   // Load real user object.
   *   $pass_raw = $account->pass_raw;
   *   $account = User::load($account->id());
   *   $account->pass_raw = $pass_raw;
   * @endcode
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User object representing the user to log in.
   *
   * @see drupalCreateUser()
   */
  protected function drupalLogin(AccountInterface $account) {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    $edit = array(
      'name' => $account->getUsername(),
      'pass' => $account->pass_raw
    );
    $this->drupalPostForm('user/login', $edit, t('Log in'));

    // @see WebTestBase::drupalUserIsLoggedIn()
    if (isset($this->sessionId)) {
      $account->session_id = $this->sessionId;
    }
    $pass = $this->assert($this->drupalUserIsLoggedIn($account), format_string('User %name successfully logged in.', array('%name' => $account->getUsername())), 'User login');
    if ($pass) {
      $this->loggedInUser = $account;
      $this->container->get('current_user')->setAccount($account);
    }
  }

  /**
   * Returns whether a given user account is logged in.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account object to check.
   */
  protected function drupalUserIsLoggedIn($account) {
    $logged_in = FALSE;

    if (isset($account->session_id)) {
      $session_handler = $this->container->get('session_handler.storage');
      $logged_in = (bool) $session_handler->read($account->session_id);
    }

    return $logged_in;
  }

  /**
   * Logs a user out of the internal browser and confirms.
   *
   * Confirms logout by checking the login page.
   */
  protected function drupalLogout() {
    // Make a request to the logout page, and redirect to the user page, the
    // idea being if you were properly logged out you should be seeing a login
    // screen.
    $this->drupalGet('user/logout', array('query' => array('destination' => 'user/login')));
    $this->assertResponse(200, 'User was logged out.');
    $pass = $this->assertField('name', 'Username field found.', 'Logout');
    $pass = $pass && $this->assertField('pass', 'Password field found.', 'Logout');

    if ($pass) {
      // @see WebTestBase::drupalUserIsLoggedIn()
      unset($this->loggedInUser->session_id);
      $this->loggedInUser = FALSE;
      $this->container->get('current_user')->setAccount(new AnonymousUserSession());
    }
  }

  /**
   * Sets up a Drupal site for running functional and integration tests.
   *
   * Installs Drupal with the installation profile specified in
   * \Drupal\simpletest\WebTestBase::$profile into the prefixed database.
   *
   * Afterwards, installs any additional modules specified in the static
   * \Drupal\simpletest\WebTestBase::$modules property of each class in the
   * class hierarchy.
   *
   * After installation all caches are flushed and several configuration values
   * are reset to the values of the parent site executing the test, since the
   * default values may be incompatible with the environment in which tests are
   * being executed.
   */
  protected function setUp() {
    // Preserve original batch for later restoration.
    $this->setBatch();

    // Initialize user 1 and session name.
    $this->initUserSession();

    // Get parameters for install_drupal() before removing global variables.
    $parameters = $this->installParameters();

    // Prepare the child site settings.
    $this->prepareSettings();

    // Execute the non-interactive installer.
    $this->doInstall($parameters);

    // Import new settings.php written by the installer.
    $this->initSettings();

    // Initialize the request and container post-install.
    $container = $this->initKernel(\Drupal::request());

    // Initialize and override certain configurations.
    $this->initConfig($container);

    // Collect modules to install.
    $this->installModulesFromClassProperty($container);

    // Restore the original batch.
    $this->restoreBatch();

    // Reset/rebuild everything.
    $this->rebuildAll();
  }

  /**
   * Execute the non-interactive installer.
   *
   * @param array $parameters
   *   Parameters to pass to install_drupal().
   *
   * @see install_drupal()
   */
  protected function doInstall(array $parameters = []) {
    require_once DRUPAL_ROOT . '/core/includes/install.core.inc';
    install_drupal($this->classLoader, $this->installParameters());
  }

  /**
   * Prepares site settings and services before installation.
   */
  protected function prepareSettings() {
    // Prepare installer settings that are not install_drupal() parameters.
    // Copy and prepare an actual settings.php, so as to resemble a regular
    // installation.
    // Not using File API; a potential error must trigger a PHP warning.
    $directory = DRUPAL_ROOT . '/' . $this->siteDirectory;
    copy(DRUPAL_ROOT . '/sites/default/default.settings.php', $directory . '/settings.php');

    // All file system paths are created by System module during installation.
    // @see system_requirements()
    // @see TestBase::prepareEnvironment()
    $settings['settings']['file_public_path'] = (object) [
      'value' => $this->publicFilesDirectory,
      'required' => TRUE,
    ];
    $settings['settings']['file_private_path'] = (object) [
      'value' => $this->privateFilesDirectory,
      'required' => TRUE,
    ];
    // Save the original site directory path, so that extensions in the
    // site-specific directory can still be discovered in the test site
    // environment.
    // @see \Drupal\Core\Extension\ExtensionDiscovery::scan()
    $settings['settings']['test_parent_site'] = (object) [
      'value' => $this->originalSite,
      'required' => TRUE,
    ];
    // Add the parent profile's search path to the child site's search paths.
    // @see \Drupal\Core\Extension\ExtensionDiscovery::getProfileDirectories()
    $settings['conf']['simpletest.settings']['parent_profile'] = (object) [
      'value' => $this->originalProfile,
      'required' => TRUE,
    ];
    $settings['settings']['apcu_ensure_unique_prefix'] = (object) [
      'value' => FALSE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    // Allow for test-specific overrides.
    $settings_testing_file = DRUPAL_ROOT . '/' . $this->originalSite . '/settings.testing.php';
    if (file_exists($settings_testing_file)) {
      // Copy the testing-specific settings.php overrides in place.
      copy($settings_testing_file, $directory . '/settings.testing.php');
      // Add the name of the testing class to settings.php and include the
      // testing specific overrides
      file_put_contents($directory . '/settings.php', "\n\$test_class = '" . get_class($this) ."';\n" . 'include DRUPAL_ROOT . \'/\' . $site_path . \'/settings.testing.php\';' ."\n", FILE_APPEND);
    }
    $settings_services_file = DRUPAL_ROOT . '/' . $this->originalSite . '/testing.services.yml';
    if (!file_exists($settings_services_file)) {
      // Otherwise, use the default services as a starting point for overrides.
      $settings_services_file = DRUPAL_ROOT . '/sites/default/default.services.yml';
    }
    // Copy the testing-specific service overrides in place.
    copy($settings_services_file, $directory . '/services.yml');
    if ($this->strictConfigSchema) {
      // Add a listener to validate configuration schema on save.
      $yaml = new \Symfony\Component\Yaml\Yaml();
      $content = file_get_contents($directory . '/services.yml');
      $services = $yaml->parse($content);
      $services['services']['simpletest.config_schema_checker'] = [
        'class' => 'Drupal\Core\Config\Testing\ConfigSchemaChecker',
        'arguments' => ['@config.typed'],
        'tags' => [['name' => 'event_subscriber']]
      ];
      file_put_contents($directory . '/services.yml', $yaml->dump($services));
    }
    // Since Drupal is bootstrapped already, install_begin_request() will not
    // bootstrap again. Hence, we have to reload the newly written custom
    // settings.php manually.
    Settings::initialize(DRUPAL_ROOT, $this->siteDirectory, $this->classLoader);
  }

  /**
   * Initialize settings created during install.
   */
  protected function initSettings() {
    Settings::initialize(DRUPAL_ROOT, $this->siteDirectory, $this->classLoader);
    foreach ($GLOBALS['config_directories'] as $type => $path) {
      $this->configDirectories[$type] = $path;
    }

    // After writing settings.php, the installer removes write permissions
    // from the site directory. To allow drupal_generate_test_ua() to write
    // a file containing the private key for drupal_valid_test_ua(), the site
    // directory has to be writable.
    // TestBase::restoreEnvironment() will delete the entire site directory.
    // Not using File API; a potential error must trigger a PHP warning.
    chmod(DRUPAL_ROOT . '/' . $this->siteDirectory, 0777);
  }

  /**
   * Initialize various configurations post-installation.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   */
  protected function initConfig(ContainerInterface $container) {
    $config = $container->get('config.factory');

    // Manually create and configure private and temporary files directories.
    // While these could be preset/enforced in settings.php like the public
    // files directory above, some tests expect them to be configurable in the
    // UI. If declared in settings.php, they would no longer be configurable.
    file_prepare_directory($this->privateFilesDirectory, FILE_CREATE_DIRECTORY);
    file_prepare_directory($this->tempFilesDirectory, FILE_CREATE_DIRECTORY);
    $config->getEditable('system.file')
      ->set('path.temporary', $this->tempFilesDirectory)
      ->save();

    // Manually configure the test mail collector implementation to prevent
    // tests from sending out emails and collect them in state instead.
    // While this should be enforced via settings.php prior to installation,
    // some tests expect to be able to test mail system implementations.
    $config->getEditable('system.mail')
      ->set('interface.default', 'test_mail_collector')
      ->save();

    // By default, verbosely display all errors and disable all production
    // environment optimizations for all tests to avoid needless overhead and
    // ensure a sane default experience for test authors.
    // @see https://www.drupal.org/node/2259167
    $config->getEditable('system.logging')
      ->set('error_level', 'verbose')
      ->save();
    $config->getEditable('system.performance')
      ->set('css.preprocess', FALSE)
      ->set('js.preprocess', FALSE)
      ->save();

    // Set an explicit time zone to not rely on the system one, which may vary
    // from setup to setup. The Australia/Sydney time zone is chosen so all
    // tests are run using an edge case scenario (UTC+10 and DST). This choice
    // is made to prevent time zone related regressions and reduce the
    // fragility of the testing system in general.
    $config->getEditable('system.date')
      ->set('timezone.default', 'Australia/Sydney')
      ->save();
  }

  /**
   * Reset and rebuild the environment after setup.
   */
  protected function rebuildAll() {
    // Reset/rebuild all data structures after enabling the modules, primarily
    // to synchronize all data structures and caches between the test runner and
    // the child site.
    // @see \Drupal\Core\DrupalKernel::bootCode()
    // @todo Test-specific setUp() methods may set up further fixtures; find a
    //   way to execute this after setUp() is done, or to eliminate it entirely.
    $this->resetAll();
    $this->kernel->prepareLegacyRequest(\Drupal::request());

    // Explicitly call register() again on the container registered in \Drupal.
    // @todo This should already be called through
    //   DrupalKernel::prepareLegacyRequest() -> DrupalKernel::boot() but that
    //   appears to be calling a different container.
    $this->container->get('stream_wrapper_manager')->register();
  }

  /**
   * Returns the parameters that will be used when Simpletest installs Drupal.
   *
   * @see install_drupal()
   * @see install_state_defaults()
   *
   * @return array
   *   Array of parameters for use in install_drupal().
   */
  protected function installParameters() {
    $connection_info = Database::getConnectionInfo();
    $driver = $connection_info['default']['driver'];
    $connection_info['default']['prefix'] = $connection_info['default']['prefix']['default'];
    unset($connection_info['default']['driver']);
    unset($connection_info['default']['namespace']);
    unset($connection_info['default']['pdo']);
    unset($connection_info['default']['init_commands']);
    // Remove database connection info that is not used by SQLite.
    if ($driver == 'sqlite') {
      unset($connection_info['default']['username']);
      unset($connection_info['default']['password']);
      unset($connection_info['default']['host']);
      unset($connection_info['default']['port']);
    }
    $parameters = array(
      'interactive' => FALSE,
      'parameters' => array(
        'profile' => $this->profile,
        'langcode' => 'en',
      ),
      'forms' => array(
        'install_settings_form' => array(
          'driver' => $driver,
          $driver => $connection_info['default'],
        ),
        'install_configure_form' => array(
          'site_name' => 'Drupal',
          'site_mail' => 'simpletest@example.com',
          'account' => array(
            'name' => $this->rootUser->name,
            'mail' => $this->rootUser->getEmail(),
            'pass' => array(
              'pass1' => $this->rootUser->pass_raw,
              'pass2' => $this->rootUser->pass_raw,
            ),
          ),
          // \Drupal\Core\Render\Element\Checkboxes::valueCallback() requires
          // NULL instead of FALSE values for programmatic form submissions to
          // disable a checkbox.
          'update_status_module' => array(
            1 => NULL,
            2 => NULL,
          ),
        ),
      ),
    );

    // If we only have one db driver available, we cannot set the driver.
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    if (count($this->getDatabaseTypes()) == 1) {
      unset($parameters['forms']['install_settings_form']['driver']);
    }
    return $parameters;
  }

  /**
   * Preserve the original batch, and instantiate the test batch.
   */
  protected function setBatch() {
    // When running tests through the Simpletest UI (vs. on the command line),
    // Simpletest's batch conflicts with the installer's batch. Batch API does
    // not support the concept of nested batches (in which the nested is not
    // progressive), so we need to temporarily pretend there was no batch.
    // Backup the currently running Simpletest batch.
    $this->originalBatch = batch_get();

    // Reset the static batch to remove Simpletest's batch operations.
    $batch = &batch_get();
    $batch = [];
  }

  /**
   * Restore the original batch.
   *
   * @see ::setBatch
   */
  protected function restoreBatch() {
    // Restore the original Simpletest batch.
    $batch = &batch_get();
    $batch = $this->originalBatch;
  }

  /**
   * Initializes user 1 for the site to be installed.
   */
  protected function initUserSession() {
    // Define information about the user 1 account.
    $this->rootUser = new UserSession(array(
      'uid' => 1,
      'name' => 'admin',
      'mail' => 'admin@example.com',
      'pass_raw' => $this->randomMachineName(),
    ));

    // The child site derives its session name from the database prefix when
    // running web tests.
    $this->generateSessionName($this->databasePrefix);
  }

  /**
   * Initializes the kernel after installation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The container.
   */
  protected function initKernel(Request $request) {
    $this->kernel = DrupalKernel::createFromRequest($request, $this->classLoader, 'prod', TRUE);
    $this->kernel->prepareLegacyRequest($request);
    // Force the container to be built from scratch instead of loaded from the
    // disk. This forces us to not accidentally load the parent site.
    return $this->kernel->rebuildContainer();
  }

  /**
   * Install modules defined by `static::$modules`.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   */
  protected function installModulesFromClassProperty(ContainerInterface $container) {
    $class = get_class($this);
    $modules = [];
    while ($class) {
      if (property_exists($class, 'modules')) {
        $modules = array_merge($modules, $class::$modules);
      }
      $class = get_parent_class($class);
    }
    if ($modules) {
      $modules = array_unique($modules);
      try {
        $success = $container->get('module_installer')->install($modules, TRUE);
        $this->assertTrue($success, SafeMarkup::format('Enabled modules: %modules', ['%modules' => implode(', ', $modules)]));
      }
      catch (MissingDependencyException $e) {
        // The exception message has all the details.
        $this->fail($e->getMessage());
      }

      $this->rebuildContainer();
    }
  }

  /**
   * Returns all supported database driver installer objects.
   *
   * This wraps drupal_get_database_types() for use without a current container.
   *
   * @return \Drupal\Core\Database\Install\Tasks[]
   *   An array of available database driver installer objects.
   */
  protected function getDatabaseTypes() {
    \Drupal::setContainer($this->originalContainer);
    $database_types = drupal_get_database_types();
    \Drupal::unsetContainer();
    return $database_types;
  }

  /**
   * Rewrites the settings.php file of the test site.
   *
   * @param array $settings
   *   An array of settings to write out, in the format expected by
   *   drupal_rewrite_settings().
   *
   * @see drupal_rewrite_settings()
   */
  protected function writeSettings(array $settings) {
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    $filename = $this->siteDirectory . '/settings.php';
    // system_requirements() removes write permissions from settings.php
    // whenever it is invoked.
    // Not using File API; a potential error must trigger a PHP warning.
    chmod($filename, 0666);
    drupal_rewrite_settings($settings, $filename);
  }

  /**
   * Changes parameters in the services.yml file.
   *
   * @param $name
   *   The name of the parameter.
   * @param $value
   *   The value of the parameter.
   */
  protected function setContainerParameter($name, $value) {
    $filename = $this->siteDirectory . '/services.yml';
    chmod($filename, 0666);

    $services = Yaml::decode(file_get_contents($filename));
    $services['parameters'][$name] = $value;
    file_put_contents($filename, Yaml::encode($services));

    // Ensure that the cache is deleted for the yaml file loader.
    $file_cache = FileCacheFactory::get('container_yaml_loader');
    $file_cache->delete($filename);
  }

  /**
   * Queues custom translations to be written to settings.php.
   *
   * Use WebTestBase::writeCustomTranslations() to apply and write the queued
   * translations.
   *
   * @param string $langcode
   *   The langcode to add translations for.
   * @param array $values
   *   Array of values containing the untranslated string and its translation.
   *   For example:
   *   @code
   *   array(
   *     '' => array('Sunday' => 'domingo'),
   *     'Long month name' => array('March' => 'marzo'),
   *   );
   *   @endcode
   *   Pass an empty array to remove all existing custom translations for the
   *   given $langcode.
   */
  protected function addCustomTranslations($langcode, array $values) {
    // If $values is empty, then the test expects all custom translations to be
    // cleared.
    if (empty($values)) {
      $this->customTranslations[$langcode] = array();
    }
    // Otherwise, $values are expected to be merged into previously passed
    // values, while retaining keys that are not explicitly set.
    else {
      foreach ($values as $context => $translations) {
        foreach ($translations as $original => $translation) {
          $this->customTranslations[$langcode][$context][$original] = $translation;
        }
      }
    }
  }

  /**
   * Writes custom translations to the test site's settings.php.
   *
   * Use TestBase::addCustomTranslations() to queue custom translations before
   * calling this method.
   */
  protected function writeCustomTranslations() {
    $settings = array();
    foreach ($this->customTranslations as $langcode => $values) {
      $settings_key = 'locale_custom_strings_' . $langcode;

      // Update in-memory settings directly.
      $this->settingsSet($settings_key, $values);

      $settings['settings'][$settings_key] = (object) array(
        'value' => $values,
        'required' => TRUE,
      );
    }
    // Only rewrite settings if there are any translation changes to write.
    if (!empty($settings)) {
      $this->writeSettings($settings);
    }
  }

  /**
   * Rebuilds \Drupal::getContainer().
   *
   * Use this to update the test process's kernel with a new service container.
   * For example, when the list of enabled modules is changed via the internal
   * browser the test process's kernel has a service container with an out of
   * date module list.
   *
   * @see TestBase::prepareEnvironment()
   * @see TestBase::restoreEnvironment()
   *
   * @todo Fix https://www.drupal.org/node/2021959 so that module enable/disable
   *   changes are immediately reflected in \Drupal::getContainer(). Until then,
   *   tests can invoke this workaround when requiring services from newly
   *   enabled modules to be immediately available in the same request.
   */
  protected function rebuildContainer() {
    // Rebuild the kernel and bring it back to a fully bootstrapped state.
    $this->container = $this->kernel->rebuildContainer();

    // Make sure the url generator has a request object, otherwise calls to
    // $this->drupalGet() will fail.
    $this->prepareRequestForGenerator();
  }

  /**
   * Resets all data structures after having enabled new modules.
   *
   * This method is called by \Drupal\simpletest\WebTestBase::setUp() after
   * enabling the requested modules. It must be called again when additional
   * modules are enabled later.
   */
  protected function resetAll() {
    // Clear all database and static caches and rebuild data structures.
    drupal_flush_all_caches();
    $this->container = \Drupal::getContainer();

    // Reset static variables and reload permissions.
    $this->refreshVariables();
  }

  /**
   * Refreshes in-memory configuration and state information.
   *
   * Useful after a page request is made that changes configuration or state in
   * a different thread.
   *
   * In other words calling a settings page with $this->drupalPostForm() with a
   * changed value would update configuration to reflect that change, but in the
   * thread that made the call (thread running the test) the changed values
   * would not be picked up.
   *
   * This method clears the cache and loads a fresh copy.
   */
  protected function refreshVariables() {
    // Clear the tag cache.
    \Drupal::service('cache_tags.invalidator')->resetChecksums();
    foreach (Cache::getBins() as $backend) {
      if (is_callable(array($backend, 'reset'))) {
        $backend->reset();
      }
    }

    $this->container->get('config.factory')->reset();
    $this->container->get('state')->resetCache();
  }

  /**
   * Cleans up after testing.
   *
   * Deletes created files and temporary files directory, deletes the tables
   * created by setUp(), and resets the database prefix.
   */
  protected function tearDown() {
    // Destroy the testing kernel.
    if (isset($this->kernel)) {
      $this->kernel->shutdown();
    }
    parent::tearDown();

    // Ensure that the maximum meta refresh count is reset.
    $this->maximumMetaRefreshCount = NULL;

    // Ensure that internal logged in variable and cURL options are reset.
    $this->loggedInUser = FALSE;
    $this->additionalCurlOptions = array();

    // Close the CURL handler and reset the cookies array used for upgrade
    // testing so test classes containing multiple tests are not polluted.
    $this->curlClose();
    $this->curlCookies = array();
    $this->cookies = array();
  }

  /**
   * Initializes the cURL connection.
   *
   * If the simpletest_httpauth_credentials variable is set, this function will
   * add HTTP authentication headers. This is necessary for testing sites that
   * are protected by login credentials from public access.
   * See the description of $curl_options for other options.
   */
  protected function curlInitialize() {
    global $base_url;

    if (!isset($this->curlHandle)) {
      $this->curlHandle = curl_init();

      // Some versions/configurations of cURL break on a NULL cookie jar, so
      // supply a real file.
      if (empty($this->cookieFile)) {
        $this->cookieFile = $this->publicFilesDirectory . '/cookie.jar';
      }

      $curl_options = array(
        CURLOPT_COOKIEJAR => $this->cookieFile,
        CURLOPT_URL => $base_url,
        CURLOPT_FOLLOWLOCATION => FALSE,
        CURLOPT_RETURNTRANSFER => TRUE,
        // Required to make the tests run on HTTPS.
        CURLOPT_SSL_VERIFYPEER => FALSE,
        // Required to make the tests run on HTTPS.
        CURLOPT_SSL_VERIFYHOST => FALSE,
        CURLOPT_HEADERFUNCTION => array(&$this, 'curlHeaderCallback'),
        CURLOPT_USERAGENT => $this->databasePrefix,
        // Disable support for the @ prefix for uploading files.
        CURLOPT_SAFE_UPLOAD => TRUE,
      );
      if (isset($this->httpAuthCredentials)) {
        $curl_options[CURLOPT_HTTPAUTH] = $this->httpAuthMethod;
        $curl_options[CURLOPT_USERPWD] = $this->httpAuthCredentials;
      }
      // curl_setopt_array() returns FALSE if any of the specified options
      // cannot be set, and stops processing any further options.
      $result = curl_setopt_array($this->curlHandle, $this->additionalCurlOptions + $curl_options);
      if (!$result) {
        throw new \UnexpectedValueException('One or more cURL options could not be set.');
      }
    }
    // We set the user agent header on each request so as to use the current
    // time and a new uniqid.
    if (preg_match('/simpletest\d+/', $this->databasePrefix, $matches)) {
      curl_setopt($this->curlHandle, CURLOPT_USERAGENT, drupal_generate_test_ua($matches[0]));
    }
  }

  /**
   * Initializes and executes a cURL request.
   *
   * @param $curl_options
   *   An associative array of cURL options to set, where the keys are constants
   *   defined by the cURL library. For a list of valid options, see
   *   http://www.php.net/manual/function.curl-setopt.php
   * @param $redirect
   *   FALSE if this is an initial request, TRUE if this request is the result
   *   of a redirect.
   *
   * @return
   *   The content returned from the call to curl_exec().
   *
   * @see curlInitialize()
   */
  protected function curlExec($curl_options, $redirect = FALSE) {
    $this->curlInitialize();

    if (!empty($curl_options[CURLOPT_URL])) {
      // cURL incorrectly handles URLs with a fragment by including the
      // fragment in the request to the server, causing some web servers
      // to reject the request citing "400 - Bad Request". To prevent
      // this, we strip the fragment from the request.
      // TODO: Remove this for Drupal 8, since fixed in curl 7.20.0.
      if (strpos($curl_options[CURLOPT_URL], '#')) {
        $original_url = $curl_options[CURLOPT_URL];
        $curl_options[CURLOPT_URL] = strtok($curl_options[CURLOPT_URL], '#');
      }
    }

    $url = empty($curl_options[CURLOPT_URL]) ? curl_getinfo($this->curlHandle, CURLINFO_EFFECTIVE_URL) : $curl_options[CURLOPT_URL];

    if (!empty($curl_options[CURLOPT_POST])) {
      // This is a fix for the Curl library to prevent Expect: 100-continue
      // headers in POST requests, that may cause unexpected HTTP response
      // codes from some webservers (like lighttpd that returns a 417 error
      // code). It is done by setting an empty "Expect" header field that is
      // not overwritten by Curl.
      $curl_options[CURLOPT_HTTPHEADER][] = 'Expect:';
    }

    $cookies = array();
    if (!empty($this->curlCookies)) {
      $cookies = $this->curlCookies;
    }
    // In order to debug web tests you need to either set a cookie, have the
    // Xdebug session in the URL or set an environment variable in case of CLI
    // requests. If the developer listens to connection on the parent site, by
    // default the cookie is not forwarded to the client side, so you cannot
    // debug the code running on the child site. In order to make debuggers work
    // this bit of information is forwarded. Make sure that the debugger listens
    // to at least three external connections.
    $request = \Drupal::request();
    $cookie_params = $request->cookies;
    if ($cookie_params->has('XDEBUG_SESSION')) {
      $cookies[] = 'XDEBUG_SESSION=' . $cookie_params->get('XDEBUG_SESSION');
    }
    // For CLI requests, the information is stored in $_SERVER.
    $server = $request->server;
    if ($server->has('XDEBUG_CONFIG')) {
      // $_SERVER['XDEBUG_CONFIG'] has the form "key1=value1 key2=value2 ...".
      $pairs = explode(' ', $server->get('XDEBUG_CONFIG'));
      foreach ($pairs as $pair) {
        list($key, $value) = explode('=', $pair);
        // Account for key-value pairs being separated by multiple spaces.
        if (trim($key, ' ') == 'idekey') {
          $cookies[] = 'XDEBUG_SESSION=' . trim($value, ' ');
        }
      }
    }

    // Merge additional cookies in.
    if (!empty($cookies)) {
      $curl_options += array(
        CURLOPT_COOKIE => '',
      );
      // Ensure any existing cookie data string ends with the correct separator.
      if (!empty($curl_options[CURLOPT_COOKIE])) {
        $curl_options[CURLOPT_COOKIE] = rtrim($curl_options[CURLOPT_COOKIE], '; ') . '; ';
      }
      $curl_options[CURLOPT_COOKIE] .= implode('; ', $cookies) . ';';
    }

    curl_setopt_array($this->curlHandle, $this->additionalCurlOptions + $curl_options);

    if (!$redirect) {
      // Reset headers, the session ID and the redirect counter.
      $this->sessionId = NULL;
      $this->headers = array();
      $this->redirectCount = 0;
    }

    $content = curl_exec($this->curlHandle);
    $status = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);

    // cURL incorrectly handles URLs with fragments, so instead of
    // letting cURL handle redirects we take of them ourselves to
    // to prevent fragments being sent to the web server as part
    // of the request.
    // TODO: Remove this for Drupal 8, since fixed in curl 7.20.0.
    if (in_array($status, array(300, 301, 302, 303, 305, 307)) && $this->redirectCount < $this->maximumRedirects) {
      if ($this->drupalGetHeader('location')) {
        $this->redirectCount++;
        $curl_options = array();
        $curl_options[CURLOPT_URL] = $this->drupalGetHeader('location');
        $curl_options[CURLOPT_HTTPGET] = TRUE;
        return $this->curlExec($curl_options, TRUE);
      }
    }

    $this->setRawContent($content);
    $this->url = isset($original_url) ? $original_url : curl_getinfo($this->curlHandle, CURLINFO_EFFECTIVE_URL);

    $message_vars = array(
      '!method' => !empty($curl_options[CURLOPT_NOBODY]) ? 'HEAD' : (empty($curl_options[CURLOPT_POSTFIELDS]) ? 'GET' : 'POST'),
      '@url' => isset($original_url) ? $original_url : $url,
      '@status' => $status,
      '!length' => format_size(strlen($this->getRawContent()))
    );
    $message = SafeMarkup::format('!method @url returned @status (!length).', $message_vars);
    $this->assertTrue($this->getRawContent() !== FALSE, $message, 'Browser');
    return $this->getRawContent();
  }

  /**
   * Reads headers and registers errors received from the tested site.
   *
   * @param $curlHandler
   *   The cURL handler.
   * @param $header
   *   An header.
   *
   * @see _drupal_log_error().
   */
  protected function curlHeaderCallback($curlHandler, $header) {
    // Header fields can be extended over multiple lines by preceding each
    // extra line with at least one SP or HT. They should be joined on receive.
    // Details are in RFC2616 section 4.
    if ($header[0] == ' ' || $header[0] == "\t") {
      // Normalize whitespace between chucks.
      $this->headers[] = array_pop($this->headers) . ' ' . trim($header);
    }
    else {
      $this->headers[] = $header;
    }

    // Errors are being sent via X-Drupal-Assertion-* headers,
    // generated by _drupal_log_error() in the exact form required
    // by \Drupal\simpletest\WebTestBase::error().
    if (preg_match('/^X-Drupal-Assertion-[0-9]+: (.*)$/', $header, $matches)) {
      // Call \Drupal\simpletest\WebTestBase::error() with the parameters from
      // the header.
      call_user_func_array(array(&$this, 'error'), unserialize(urldecode($matches[1])));
    }

    // Save cookies.
    if (preg_match('/^Set-Cookie: ([^=]+)=(.+)/', $header, $matches)) {
      $name = $matches[1];
      $parts = array_map('trim', explode(';', $matches[2]));
      $value = array_shift($parts);
      $this->cookies[$name] = array('value' => $value, 'secure' => in_array('secure', $parts));
      if ($name === $this->getSessionName()) {
        if ($value != 'deleted') {
          $this->sessionId = $value;
        }
        else {
          $this->sessionId = NULL;
        }
      }
    }

    // This is required by cURL.
    return strlen($header);
  }

  /**
   * Close the cURL handler and unset the handler.
   */
  protected function curlClose() {
    if (isset($this->curlHandle)) {
      curl_close($this->curlHandle);
      unset($this->curlHandle);
    }
  }

  /**
   * Returns whether the test is being executed from within a test site.
   *
   * Mainly used by recursive tests (i.e. to test the testing framework).
   *
   * @return bool
   *   TRUE if this test was instantiated in a request within the test site,
   *   FALSE otherwise.
   *
   * @see \Drupal\Core\DrupalKernel::bootConfiguration()
   */
  protected function isInChildSite() {
    return DRUPAL_TEST_IN_CHILD_SITE;
  }

  /**
   * Retrieves a Drupal path or an absolute path.
   *
   * @param \Drupal\Core\Url|string $path
   *   Drupal path or URL to load into internal browser
   * @param $options
   *   Options to be forwarded to the url generator.
   * @param $headers
   *   An array containing additional HTTP request headers, each formatted as
   *   "name: value".
   *
   * @return string
   *   The retrieved HTML string, also available as $this->getRawContent()
   */
  protected function drupalGet($path, array $options = array(), array $headers = array()) {
    // We re-using a CURL connection here. If that connection still has certain
    // options set, it might change the GET into a POST. Make sure we clear out
    // previous options.
    $out = $this->curlExec(array(CURLOPT_HTTPGET => TRUE, CURLOPT_URL => $this->buildUrl($path, $options), CURLOPT_NOBODY => FALSE, CURLOPT_HTTPHEADER => $headers));
    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    // Replace original page output with new output from redirected page(s).
    if ($new = $this->checkForMetaRefresh()) {
      $out = $new;
      // We are finished with all meta refresh redirects, so reset the counter.
      $this->metaRefreshCount = 0;
    }

    if ($path instanceof Url) {
      $path = $path->toString();
    }

    $verbose = 'GET request to: ' . $path .
               '<hr />Ending URL: ' . $this->getUrl();
    if ($this->dumpHeaders) {
      $verbose .= '<hr />Headers: <pre>' . Html::escape(var_export(array_map('trim', $this->headers), TRUE)) . '</pre>';
    }
    $verbose .= '<hr />' . $out;

    $this->verbose($verbose);
    return $out;
  }

  /**
   * Retrieves a Drupal path or an absolute path and JSON decode the result.
   *
   * @param string $path
   *   Path to request AJAX from.
   * @param array $options
   *   Array of URL options.
   * @param array $headers
   *   Array of headers. Eg array('Accept: application/vnd.drupal-ajax').
   *
   * @return array
   *   Decoded json.
   * Requests a Drupal path in JSON format, and JSON decodes the response.
   */
  protected function drupalGetJSON($path, array $options = array(), array $headers = array()) {
    return Json::decode($this->drupalGetWithFormat($path, 'json', $options, $headers));
  }

  /**
   * Retrieves a Drupal path or an absolute path for a given format.
   *
   * @param string $path
   *   Path to request AJAX from.
   * @param string $format
   *   The wanted request format.
   * @param array $options
   *   Array of URL options.
   * @param array $headers
   *   Array of headers.
   *
   * @return mixed
   *   The result of the request.
   */
  protected function drupalGetWithFormat($path, $format, array $options = [], array $headers = []) {
    $options += ['query' => ['_format' => $format]];
    return $this->drupalGet($path, $options, $headers);
  }

  /**
   * Requests a Drupal path in drupal_ajax format and JSON-decodes the response.
   */
  protected function drupalGetAjax($path, array $options = array(), array $headers = array()) {
    if (!isset($options['query'][MainContentViewSubscriber::WRAPPER_FORMAT])) {
      $options['query'][MainContentViewSubscriber::WRAPPER_FORMAT] = 'drupal_ajax';
    }
    return Json::decode($this->drupalGetXHR($path, $options, $headers));
  }

  /**
   * Requests a Drupal path as if it is a XMLHttpRequest.
   */
  protected function drupalGetXHR($path, array $options = array(), array $headers = array()) {
    $headers[] = 'X-Requested-With: XMLHttpRequest';
    return $this->drupalGet($path, $options, $headers);
  }

  /**
   * Executes a form submission.
   *
   * It will be done as usual POST request with SimpleBrowser.
   *
   * @param $path
   *   Location of the post form. Either a Drupal path or an absolute path or
   *   NULL to post to the current page. For multi-stage forms you can set the
   *   path to NULL and have it post to the last received page. Example:
   *
   *   @code
   *   // First step in form.
   *   $edit = array(...);
   *   $this->drupalPostForm('some_url', $edit, t('Save'));
   *
   *   // Second step in form.
   *   $edit = array(...);
   *   $this->drupalPostForm(NULL, $edit, t('Save'));
   *   @endcode
   * @param  $edit
   *   Field data in an associative array. Changes the current input fields
   *   (where possible) to the values indicated.
   *
   *   When working with form tests, the keys for an $edit element should match
   *   the 'name' parameter of the HTML of the form. For example, the 'body'
   *   field for a node has the following HTML:
   *   @code
   *   <textarea id="edit-body-und-0-value" class="text-full form-textarea
   *    resize-vertical" placeholder="" cols="60" rows="9"
   *    name="body[0][value]"></textarea>
   *   @endcode
   *   When testing this field using an $edit parameter, the code becomes:
   *   @code
   *   $edit["body[0][value]"] = 'My test value';
   *   @endcode
   *
   *   A checkbox can be set to TRUE to be checked and should be set to FALSE to
   *   be unchecked. Multiple select fields can be tested using 'name[]' and
   *   setting each of the desired values in an array:
   *   @code
   *   $edit = array();
   *   $edit['name[]'] = array('value1', 'value2');
   *   @endcode
   * @param $submit
   *   Value of the submit button whose click is to be emulated. For example,
   *   t('Save'). The processing of the request depends on this value. For
   *   example, a form may have one button with the value t('Save') and another
   *   button with the value t('Delete'), and execute different code depending
   *   on which one is clicked.
   *
   *   This function can also be called to emulate an Ajax submission. In this
   *   case, this value needs to be an array with the following keys:
   *   - path: A path to submit the form values to for Ajax-specific processing.
   *   - triggering_element: If the value for the 'path' key is a generic Ajax
   *     processing path, this needs to be set to the name of the element. If
   *     the name doesn't identify the element uniquely, then this should
   *     instead be an array with a single key/value pair, corresponding to the
   *     element name and value. The \Drupal\Core\Form\FormAjaxResponseBuilder
   *     uses this to find the #ajax information for the element, including
   *     which specific callback to use for processing the request.
   *
   *   This can also be set to NULL in order to emulate an Internet Explorer
   *   submission of a form with a single text field, and pressing ENTER in that
   *   textfield: under these conditions, no button information is added to the
   *   POST data.
   * @param $options
   *   Options to be forwarded to the url generator.
   * @param $headers
   *   An array containing additional HTTP request headers, each formatted as
   *   "name: value".
   * @param $form_html_id
   *   (optional) HTML ID of the form to be submitted. On some pages
   *   there are many identical forms, so just using the value of the submit
   *   button is not enough. For example: 'trigger-node-presave-assign-form'.
   *   Note that this is not the Drupal $form_id, but rather the HTML ID of the
   *   form, which is typically the same thing but with hyphens replacing the
   *   underscores.
   * @param $extra_post
   *   (optional) A string of additional data to append to the POST submission.
   *   This can be used to add POST data for which there are no HTML fields, as
   *   is done by drupalPostAjaxForm(). This string is literally appended to the
   *   POST data, so it must already be urlencoded and contain a leading "&"
   *   (e.g., "&extra_var1=hello+world&extra_var2=you%26me").
   */
  protected function drupalPostForm($path, $edit, $submit, array $options = array(), array $headers = array(), $form_html_id = NULL, $extra_post = NULL) {
    $submit_matches = FALSE;
    $ajax = is_array($submit);
    if (isset($path)) {
      $this->drupalGet($path, $options);
    }

    if ($this->parse()) {
      $edit_save = $edit;
      // Let's iterate over all the forms.
      $xpath = "//form";
      if (!empty($form_html_id)) {
        $xpath .= "[@id='" . $form_html_id . "']";
      }
      $forms = $this->xpath($xpath);
      foreach ($forms as $form) {
        // We try to set the fields of this form as specified in $edit.
        $edit = $edit_save;
        $post = array();
        $upload = array();
        $submit_matches = $this->handleForm($post, $edit, $upload, $ajax ? NULL : $submit, $form);
        $action = isset($form['action']) ? $this->getAbsoluteUrl((string) $form['action']) : $this->getUrl();
        if ($ajax) {
          if (empty($submit['path'])) {
            throw new \Exception('No #ajax path specified.');
          }
          $action = $this->getAbsoluteUrl($submit['path']);
          // Ajax callbacks verify the triggering element if necessary, so while
          // we may eventually want extra code that verifies it in the
          // handleForm() function, it's not currently a requirement.
          $submit_matches = TRUE;
        }
        // We post only if we managed to handle every field in edit and the
        // submit button matches.
        if (!$edit && ($submit_matches || !isset($submit))) {
          $post_array = $post;
          if ($upload) {
            foreach ($upload as $key => $file) {
              if (is_array($file) && count($file)) {
                // There seems to be no way via php's API to cURL to upload
                // several files with the same post field name. However, Drupal
                // still sees array-index syntax in a similar way.
                for ($i = 0; $i < count($file); $i++) {
                  $postfield = str_replace('[]', '', $key) . '[' . $i . ']';
                  $file_path = $this->container->get('file_system')->realpath($file[$i]);
                  $post[$postfield] = curl_file_create($file_path);
                }
              }
              else {
                $file = $this->container->get('file_system')->realpath($file);
                if ($file && is_file($file)) {
                  $post[$key] = curl_file_create($file);
                }
              }
            }
          }
          else {
            $post = $this->serializePostValues($post) . $extra_post;
          }
          $out = $this->curlExec(array(CURLOPT_URL => $action, CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_HTTPHEADER => $headers));
          // Ensure that any changes to variables in the other thread are picked
          // up.
          $this->refreshVariables();

          // Replace original page output with new output from redirected
          // page(s).
          if ($new = $this->checkForMetaRefresh()) {
            $out = $new;
          }

          if ($path instanceof Url) {
            $path = $path->toString();
          }
          $verbose = 'POST request to: ' . $path;
          $verbose .= '<hr />Ending URL: ' . $this->getUrl();
          if ($this->dumpHeaders) {
            $verbose .= '<hr />Headers: <pre>' . Html::escape(var_export(array_map('trim', $this->headers), TRUE)) . '</pre>';
          }
          $verbose .= '<hr />Fields: ' . highlight_string('<?php ' . var_export($post_array, TRUE), TRUE);
          $verbose .= '<hr />' . $out;

          $this->verbose($verbose);
          return $out;
        }
      }
      // We have not found a form which contained all fields of $edit.
      foreach ($edit as $name => $value) {
        $this->fail(SafeMarkup::format('Failed to set field @name to @value', array('@name' => $name, '@value' => $value)));
      }
      if (!$ajax && isset($submit)) {
        $this->assertTrue($submit_matches, format_string('Found the @submit button', array('@submit' => $submit)));
      }
      $this->fail(format_string('Found the requested form fields at @path', array('@path' => ($path instanceof Url) ? $path->toString() : $path)));
    }
  }

  /**
   * Executes an Ajax form submission.
   *
   * This executes a POST as ajax.js does. The returned JSON data is used to
   * update $this->content via drupalProcessAjaxResponse(). It also returns
   * the array of AJAX commands received.
   *
   * @param $path
   *   Location of the form containing the Ajax enabled element to test. Can be
   *   either a Drupal path or an absolute path or NULL to use the current page.
   * @param $edit
   *   Field data in an associative array. Changes the current input fields
   *   (where possible) to the values indicated.
   * @param $triggering_element
   *   The name of the form element that is responsible for triggering the Ajax
   *   functionality to test. May be a string or, if the triggering element is
   *   a button, an associative array where the key is the name of the button
   *   and the value is the button label. i.e.) array('op' => t('Refresh')).
   * @param $ajax_path
   *   (optional) Override the path set by the Ajax settings of the triggering
   *   element.
   * @param $options
   *   (optional) Options to be forwarded to the url generator.
   * @param $headers
   *   (optional) An array containing additional HTTP request headers, each
   *   formatted as "name: value". Forwarded to drupalPostForm().
   * @param $form_html_id
   *   (optional) HTML ID of the form to be submitted, use when there is more
   *   than one identical form on the same page and the value of the triggering
   *   element is not enough to identify the form. Note this is not the Drupal
   *   ID of the form but rather the HTML ID of the form.
   * @param $ajax_settings
   *   (optional) An array of Ajax settings which if specified will be used in
   *   place of the Ajax settings of the triggering element.
   *
   * @return
   *   An array of Ajax commands.
   *
   * @see drupalPostForm()
   * @see drupalProcessAjaxResponse()
   * @see ajax.js
   */
  protected function drupalPostAjaxForm($path, $edit, $triggering_element, $ajax_path = NULL, array $options = array(), array $headers = array(), $form_html_id = NULL, $ajax_settings = NULL) {

    // Get the content of the initial page prior to calling drupalPostForm(),
    // since drupalPostForm() replaces $this->content.
    if (isset($path)) {
      // Avoid sending the wrapper query argument to drupalGet so we can fetch
      // the form and populate the internal WebTest values.
      $get_options = $options;
      unset($get_options['query'][MainContentViewSubscriber::WRAPPER_FORMAT]);
      $this->drupalGet($path, $get_options);
    }
    $content = $this->content;
    $drupal_settings = $this->drupalSettings;

    // Provide a default value for the wrapper envelope.
    $options['query'][MainContentViewSubscriber::WRAPPER_FORMAT] =
      isset($options['query'][MainContentViewSubscriber::WRAPPER_FORMAT]) ?
        $options['query'][MainContentViewSubscriber::WRAPPER_FORMAT] :
        'drupal_ajax';

    // Get the Ajax settings bound to the triggering element.
    if (!isset($ajax_settings)) {
      if (is_array($triggering_element)) {
        $xpath = '//*[@name="' . key($triggering_element) . '" and @value="' . current($triggering_element) . '"]';
      }
      else {
        $xpath = '//*[@name="' . $triggering_element . '"]';
      }
      if (isset($form_html_id)) {
        $xpath = '//form[@id="' . $form_html_id . '"]' . $xpath;
      }
      $element = $this->xpath($xpath);
      $element_id = (string) $element[0]['id'];
      $ajax_settings = $drupal_settings['ajax'][$element_id];
    }

    // Add extra information to the POST data as ajax.js does.
    $extra_post = array();
    if (isset($ajax_settings['submit'])) {
      foreach ($ajax_settings['submit'] as $key => $value) {
        $extra_post[$key] = $value;
      }
    }
    $extra_post[AjaxResponseSubscriber::AJAX_REQUEST_PARAMETER] = 1;
    $extra_post += $this->getAjaxPageStatePostData();
    // Now serialize all the $extra_post values, and prepend it with an '&'.
    $extra_post = '&' . $this->serializePostValues($extra_post);

    // Unless a particular path is specified, use the one specified by the
    // Ajax settings.
    if (!isset($ajax_path)) {
      if (isset($ajax_settings['url'])) {
        // In order to allow to set for example the wrapper envelope query
        // parameter we need to get the system path again.
        $parsed_url = UrlHelper::parse($ajax_settings['url']);
        $options['query'] = $parsed_url['query'] + $options['query'];
        $options += ['fragment' => $parsed_url['fragment']];

        // We know that $parsed_url['path'] is already with the base path
        // attached.
        $ajax_path = preg_replace(
          '/^' . preg_quote(base_path(), '/') . '/',
          '',
          $parsed_url['path']
        );
      }
    }

    if (empty($ajax_path)) {
      throw new \Exception('No #ajax path specified.');
    }

    $ajax_path = $this->container->get('unrouted_url_assembler')->assemble('base://' . $ajax_path, $options);

    // Submit the POST request.
    $return = Json::decode($this->drupalPostForm(NULL, $edit, array('path' => $ajax_path, 'triggering_element' => $triggering_element), $options, $headers, $form_html_id, $extra_post));
    if ($this->assertAjaxHeader) {
      $this->assertIdentical($this->drupalGetHeader('X-Drupal-Ajax-Token'), '1', 'Ajax response header found.');
    }

    // Change the page content by applying the returned commands.
    if (!empty($ajax_settings) && !empty($return)) {
      $this->drupalProcessAjaxResponse($content, $return, $ajax_settings, $drupal_settings);
    }

    $verbose = 'AJAX POST request to: ' . $path;
    $verbose .= '<br />AJAX controller path: ' . $ajax_path;
    $verbose .= '<hr />Ending URL: ' . $this->getUrl();
    $verbose .= '<hr />' . $this->content;

    $this->verbose($verbose);

    return $return;
  }

  /**
   * Processes an AJAX response into current content.
   *
   * This processes the AJAX response as ajax.js does. It uses the response's
   * JSON data, an array of commands, to update $this->content using equivalent
   * DOM manipulation as is used by ajax.js.
   * It does not apply custom AJAX commands though, because emulation is only
   * implemented for the AJAX commands that ship with Drupal core.
   *
   * @param string $content
   *   The current HTML content.
   * @param array $ajax_response
   *   An array of AJAX commands.
   * @param array $ajax_settings
   *   An array of AJAX settings which will be used to process the response.
   * @param array $drupal_settings
   *   An array of settings to update the value of drupalSettings for the
   *   currently-loaded page.
   *
   * @see drupalPostAjaxForm()
   * @see ajax.js
   */
  protected function drupalProcessAjaxResponse($content, array $ajax_response, array $ajax_settings, array $drupal_settings) {

    // ajax.js applies some defaults to the settings object, so do the same
    // for what's used by this function.
    $ajax_settings += array(
      'method' => 'replaceWith',
    );
    // DOM can load HTML soup. But, HTML soup can throw warnings, suppress
    // them.
    $dom = new \DOMDocument();
    @$dom->loadHTML($content);
    // XPath allows for finding wrapper nodes better than DOM does.
    $xpath = new \DOMXPath($dom);
    foreach ($ajax_response as $command) {
      // Error messages might be not commands.
      if (!is_array($command)) {
        continue;
      }
      switch ($command['command']) {
        case 'settings':
          $drupal_settings = NestedArray::mergeDeepArray([$drupal_settings, $command['settings']], TRUE);
          break;

        case 'insert':
          $wrapperNode = NULL;
          // When a command doesn't specify a selector, use the
          // #ajax['wrapper'] which is always an HTML ID.
          if (!isset($command['selector'])) {
            $wrapperNode = $xpath->query('//*[@id="' . $ajax_settings['wrapper'] . '"]')->item(0);
          }
          // @todo Ajax commands can target any jQuery selector, but these are
          //   hard to fully emulate with XPath. For now, just handle 'head'
          //   and 'body', since these are used by
          //   \Drupal\Core\Ajax\AjaxResponse::ajaxRender().
          elseif (in_array($command['selector'], array('head', 'body'))) {
            $wrapperNode = $xpath->query('//' . $command['selector'])->item(0);
          }
          if ($wrapperNode) {
            // ajax.js adds an enclosing DIV to work around a Safari bug.
            $newDom = new \DOMDocument();
            // DOM can load HTML soup. But, HTML soup can throw warnings,
            // suppress them.
            @$newDom->loadHTML('<div>' . $command['data'] . '</div>');
            // Suppress warnings thrown when duplicate HTML IDs are encountered.
            // This probably means we are replacing an element with the same ID.
            $newNode = @$dom->importNode($newDom->documentElement->firstChild->firstChild, TRUE);
            $method = isset($command['method']) ? $command['method'] : $ajax_settings['method'];
            // The "method" is a jQuery DOM manipulation function. Emulate
            // each one using PHP's DOMNode API.
            switch ($method) {
              case 'replaceWith':
                $wrapperNode->parentNode->replaceChild($newNode, $wrapperNode);
                break;
              case 'append':
                $wrapperNode->appendChild($newNode);
                break;
              case 'prepend':
                // If no firstChild, insertBefore() falls back to
                // appendChild().
                $wrapperNode->insertBefore($newNode, $wrapperNode->firstChild);
                break;
              case 'before':
                $wrapperNode->parentNode->insertBefore($newNode, $wrapperNode);
                break;
              case 'after':
                // If no nextSibling, insertBefore() falls back to
                // appendChild().
                $wrapperNode->parentNode->insertBefore($newNode, $wrapperNode->nextSibling);
                break;
              case 'html':
                foreach ($wrapperNode->childNodes as $childNode) {
                  $wrapperNode->removeChild($childNode);
                }
                $wrapperNode->appendChild($newNode);
                break;
            }
          }
          break;

        // @todo Add suitable implementations for these commands in order to
        //   have full test coverage of what ajax.js can do.
        case 'remove':
          break;
        case 'changed':
          break;
        case 'css':
          break;
        case 'data':
          break;
        case 'restripe':
          break;
        case 'add_css':
          break;
        case 'update_build_id':
          $buildId = $xpath->query('//input[@name="form_build_id" and @value="' . $command['old'] . '"]')->item(0);
          if ($buildId) {
            $buildId->setAttribute('value', $command['new']);
          }
          break;
      }
    }
    $content = $dom->saveHTML();
    $this->setRawContent($content);
    $this->setDrupalSettings($drupal_settings);
  }

  /**
   * Perform a POST HTTP request.
   *
   * @param string $path
   *   Drupal path where the request should be POSTed to. Will be transformed
   *   into an absolute path automatically.
   * @param string $accept
   *   The value for the "Accept" header. Usually either 'application/json' or
   *   'application/vnd.drupal-ajax'.
   * @param array $post
   *   The POST data. When making a 'application/vnd.drupal-ajax' request, the
   *   Ajax page state data should be included. Use getAjaxPageStatePostData()
   *   for that.
   * @param array $options
   *   (optional) Options to be forwarded to the url generator. The 'absolute'
   *   option will automatically be enabled.
   *
   * @return
   *   The content returned from the call to curl_exec().
   *
   * @see WebTestBase::getAjaxPageStatePostData()
   * @see WebTestBase::curlExec()
   */
  protected function drupalPost($path, $accept, array $post, $options = array()) {
    return $this->curlExec(array(
      CURLOPT_URL => $this->buildUrl($path, $options),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $this->serializePostValues($post),
      CURLOPT_HTTPHEADER => array(
        'Accept: ' . $accept,
        'Content-Type: application/x-www-form-urlencoded',
      ),
    ));
  }

  /**
   * Performs a POST HTTP request with a specific format.
   *
   * @param string $path
   *   Drupal path where the request should be POSTed to. Will be transformed
   *   into an absolute path automatically.
   * @param string $format
   *   The request format.
   * @param array $post
   *   The POST data. When making a 'application/vnd.drupal-ajax' request, the
   *   Ajax page state data should be included. Use getAjaxPageStatePostData()
   *   for that.
   * @param array $options
   *   (optional) Options to be forwarded to the url generator. The 'absolute'
   *   option will automatically be enabled.
   *
   * @return string
   *   The content returned from the call to curl_exec().
   *
   * @see WebTestBase::drupalPost
   * @see WebTestBase::getAjaxPageStatePostData()
   * @see WebTestBase::curlExec()
   */
  protected function drupalPostWithFormat($path, $format, array $post, $options = []) {
    $options['query']['_format'] = $format;
    return $this->drupalPost($path, '', $post, $options);
  }

  /**
   * Get the Ajax page state from drupalSettings and prepare it for POSTing.
   *
   * @return array
   *   The Ajax page state POST data.
   */
  protected function getAjaxPageStatePostData() {
    $post = array();
    $drupal_settings = $this->drupalSettings;
    if (isset($drupal_settings['ajaxPageState']['theme'])) {
      $post['ajax_page_state[theme]'] = $drupal_settings['ajaxPageState']['theme'];
    }
    if (isset($drupal_settings['ajaxPageState']['theme_token'])) {
      $post['ajax_page_state[theme_token]'] = $drupal_settings['ajaxPageState']['theme_token'];
    }
    if (isset($drupal_settings['ajaxPageState']['libraries'])) {
      $post['ajax_page_state[libraries]'] = $drupal_settings['ajaxPageState']['libraries'];
    }
    return $post;
  }

  /**
   * Serialize POST HTTP request values.
   *
   * Encode according to application/x-www-form-urlencoded. Both names and
   * values needs to be urlencoded, according to
   * http://www.w3.org/TR/html4/interact/forms.html#h-17.13.4.1
   *
   * @param array $post
   *   The array of values to be POSTed.
   *
   * @return string
   *   The serialized result.
   */
  protected function serializePostValues($post = array()) {
    foreach ($post as $key => $value) {
      $post[$key] = urlencode($key) . '=' . urlencode($value);
    }
    return implode('&', $post);
  }

  /**
   * Transforms a nested array into a flat array suitable for WebTestBase::drupalPostForm().
   *
   * @param array $values
   *   A multi-dimensional form values array to convert.
   *
   * @return array
   *   The flattened $edit array suitable for WebTestBase::drupalPostForm().
   */
  protected function translatePostValues(array $values) {
    $edit = array();
    // The easiest and most straightforward way to translate values suitable for
    // WebTestBase::drupalPostForm() is to actually build the POST data string
    // and convert the resulting key/value pairs back into a flat array.
    $query = http_build_query($values);
    foreach (explode('&', $query) as $item) {
      list($key, $value) = explode('=', $item);
      $edit[urldecode($key)] = urldecode($value);
    }
    return $edit;
  }

  /**
   * Runs cron in the Drupal installed by Simpletest.
   */
  protected function cronRun() {
    $this->drupalGet('cron/' . \Drupal::state()->get('system.cron_key'));
  }

  /**
   * Checks for meta refresh tag and if found call drupalGet() recursively.
   *
   * This function looks for the http-equiv attribute to be set to "Refresh" and
   * is case-sensitive.
   *
   * @return
   *   Either the new page content or FALSE.
   */
  protected function checkForMetaRefresh() {
    if (strpos($this->getRawContent(), '<meta ') && $this->parse() && (!isset($this->maximumMetaRefreshCount) || $this->metaRefreshCount < $this->maximumMetaRefreshCount)) {
      $refresh = $this->xpath('//meta[@http-equiv="Refresh"]');
      if (!empty($refresh)) {
        // Parse the content attribute of the meta tag for the format:
        // "[delay]: URL=[page_to_redirect_to]".
        if (preg_match('/\d+;\s*URL=(?<url>.*)/i', $refresh[0]['content'], $match)) {
          $this->metaRefreshCount++;
          return $this->drupalGet($this->getAbsoluteUrl(Html::decodeEntities($match['url'])));
        }
      }
    }
    return FALSE;
  }

  /**
   * Retrieves only the headers for a Drupal path or an absolute path.
   *
   * @param $path
   *   Drupal path or URL to load into internal browser
   * @param $options
   *   Options to be forwarded to the url generator.
   * @param $headers
   *   An array containing additional HTTP request headers, each formatted as
   *   "name: value".
   *
   * @return
   *   The retrieved headers, also available as $this->getRawContent()
   */
  protected function drupalHead($path, array $options = array(), array $headers = array()) {
    $options['absolute'] = TRUE;
    $url = $this->buildUrl($path, $options);
    $out = $this->curlExec(array(CURLOPT_NOBODY => TRUE, CURLOPT_URL => $url, CURLOPT_HTTPHEADER => $headers));
    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    if ($this->dumpHeaders) {
      $this->verbose('GET request to: ' . $path .
                     '<hr />Ending URL: ' . $this->getUrl() .
                     '<hr />Headers: <pre>' . Html::escape(var_export(array_map('trim', $this->headers), TRUE)) . '</pre>');
    }

    return $out;
  }

  /**
   * Handles form input related to drupalPostForm().
   *
   * Ensure that the specified fields exist and attempt to create POST data in
   * the correct manner for the particular field type.
   *
   * @param $post
   *   Reference to array of post values.
   * @param $edit
   *   Reference to array of edit values to be checked against the form.
   * @param $submit
   *   Form submit button value.
   * @param $form
   *   Array of form elements.
   *
   * @return
   *   Submit value matches a valid submit input in the form.
   */
  protected function handleForm(&$post, &$edit, &$upload, $submit, $form) {
    // Retrieve the form elements.
    $elements = $form->xpath('.//input[not(@disabled)]|.//textarea[not(@disabled)]|.//select[not(@disabled)]');
    $submit_matches = FALSE;
    foreach ($elements as $element) {
      // SimpleXML objects need string casting all the time.
      $name = (string) $element['name'];
      // This can either be the type of <input> or the name of the tag itself
      // for <select> or <textarea>.
      $type = isset($element['type']) ? (string) $element['type'] : $element->getName();
      $value = isset($element['value']) ? (string) $element['value'] : '';
      $done = FALSE;
      if (isset($edit[$name])) {
        switch ($type) {
          case 'text':
          case 'tel':
          case 'textarea':
          case 'url':
          case 'number':
          case 'range':
          case 'color':
          case 'hidden':
          case 'password':
          case 'email':
          case 'search':
          case 'date':
          case 'time':
          case 'datetime':
          case 'datetime-local';
            $post[$name] = $edit[$name];
            unset($edit[$name]);
            break;
          case 'radio':
            if ($edit[$name] == $value) {
              $post[$name] = $edit[$name];
              unset($edit[$name]);
            }
            break;
          case 'checkbox':
            // To prevent checkbox from being checked.pass in a FALSE,
            // otherwise the checkbox will be set to its value regardless
            // of $edit.
            if ($edit[$name] === FALSE) {
              unset($edit[$name]);
              continue 2;
            }
            else {
              unset($edit[$name]);
              $post[$name] = $value;
            }
            break;
          case 'select':
            $new_value = $edit[$name];
            $options = $this->getAllOptions($element);
            if (is_array($new_value)) {
              // Multiple select box.
              if (!empty($new_value)) {
                $index = 0;
                $key = preg_replace('/\[\]$/', '', $name);
                foreach ($options as $option) {
                  $option_value = (string) $option['value'];
                  if (in_array($option_value, $new_value)) {
                    $post[$key . '[' . $index++ . ']'] = $option_value;
                    $done = TRUE;
                    unset($edit[$name]);
                  }
                }
              }
              else {
                // No options selected: do not include any POST data for the
                // element.
                $done = TRUE;
                unset($edit[$name]);
              }
            }
            else {
              // Single select box.
              foreach ($options as $option) {
                if ($new_value == $option['value']) {
                  $post[$name] = $new_value;
                  unset($edit[$name]);
                  $done = TRUE;
                  break;
                }
              }
            }
            break;
          case 'file':
            $upload[$name] = $edit[$name];
            unset($edit[$name]);
            break;
        }
      }
      if (!isset($post[$name]) && !$done) {
        switch ($type) {
          case 'textarea':
            $post[$name] = (string) $element;
            break;
          case 'select':
            $single = empty($element['multiple']);
            $first = TRUE;
            $index = 0;
            $key = preg_replace('/\[\]$/', '', $name);
            $options = $this->getAllOptions($element);
            foreach ($options as $option) {
              // For single select, we load the first option, if there is a
              // selected option that will overwrite it later.
              if ($option['selected'] || ($first && $single)) {
                $first = FALSE;
                if ($single) {
                  $post[$name] = (string) $option['value'];
                }
                else {
                  $post[$key . '[' . $index++ . ']'] = (string) $option['value'];
                }
              }
            }
            break;
          case 'file':
            break;
          case 'submit':
          case 'image':
            if (isset($submit) && $submit == $value) {
              $post[$name] = $value;
              $submit_matches = TRUE;
            }
            break;
          case 'radio':
          case 'checkbox':
            if (!isset($element['checked'])) {
              break;
            }
            // Deliberate no break.
          default:
            $post[$name] = $value;
        }
      }
    }
    // An empty name means the value is not sent.
    unset($post['']);
    return $submit_matches;
  }

  /**
   * Follows a link by complete name.
   *
   * Will click the first link found with this link text by default, or a later
   * one if an index is given. Match is case sensitive with normalized space.
   * The label is translated label.
   *
   * If the link is discovered and clicked, the test passes. Fail otherwise.
   *
   * @param string $label
   *   Text between the anchor tags.
   * @param int $index
   *   Link position counting from zero.
   *
   * @return string|bool
   *   Page contents on success, or FALSE on failure.
   */
  protected function clickLink($label, $index = 0) {
    return $this->clickLinkHelper($label, $index, '//a[normalize-space()=:label]');
  }

  /**
   * Follows a link by partial name.
   *
   *
   * If the link is discovered and clicked, the test passes. Fail otherwise.
   *
   * @param string $label
   *   Text between the anchor tags, uses starts-with().
   * @param int $index
   *   Link position counting from zero.
   *
   * @return string|bool
   *   Page contents on success, or FALSE on failure.
   *
   * @see ::clickLink()
   */
  protected function clickLinkPartialName($label, $index = 0) {
    return $this->clickLinkHelper($label, $index, '//a[starts-with(normalize-space(), :label)]');
  }

  /**
   * Provides a helper for ::clickLink() and ::clickLinkPartialName().
   *
   * @param string $label
   *   Text between the anchor tags, uses starts-with().
   * @param int $index
   *   Link position counting from zero.
   * @param string $pattern
   *   A pattern to use for the XPath.
   *
   * @return bool|string
   *   Page contents on success, or FALSE on failure.
   */
  protected function clickLinkHelper($label, $index, $pattern) {
    $url_before = $this->getUrl();
    $urls = $this->xpath($pattern, array(':label' => $label));
    if (isset($urls[$index])) {
      $url_target = $this->getAbsoluteUrl($urls[$index]['href']);
      $this->pass(SafeMarkup::format('Clicked link %label (@url_target) from @url_before', array('%label' => $label, '@url_target' => $url_target, '@url_before' => $url_before)), 'Browser');
      return $this->drupalGet($url_target);
    }
    $this->fail(SafeMarkup::format('Link %label does not exist on @url_before', array('%label' => $label, '@url_before' => $url_before)), 'Browser');
    return FALSE;
  }

  /**
   * Takes a path and returns an absolute path.
   *
   * This method is implemented in the way that browsers work, see
   * https://url.spec.whatwg.org/#relative-state for more information about the
   * possible cases.
   *
   * @param string $path
   *   A path from the internal browser content.
   *
   * @return string
   *   The $path with $base_url prepended, if necessary.
   */
  protected function getAbsoluteUrl($path) {
    global $base_url, $base_path;

    $parts = parse_url($path);

    // In case the $path has a host, it is already an absolute URL and we are
    // done.
    if (!empty($parts['host'])) {
      return $path;
    }

    // In case the $path contains just a query, we turn it into an absolute URL
    // with the same scheme, host and path, see
    // https://url.spec.whatwg.org/#relative-state.
    if (array_keys($parts) === ['query']) {
      $current_uri = new Uri($this->getUrl());
      return (string) $current_uri->withQuery($parts['query']);
    }

    if (empty($parts['host'])) {
      // Ensure that we have a string (and no xpath object).
      $path = (string) $path;
      // Strip $base_path, if existent.
      $length = strlen($base_path);
      if (substr($path, 0, $length) === $base_path) {
        $path = substr($path, $length);
      }
      // Ensure that we have an absolute path.
      if (empty($path) || $path[0] !== '/') {
        $path = '/' . $path;
      }
      // Finally, prepend the $base_url.
      $path = $base_url . $path;
    }
    return $path;
  }

  /**
   * Gets the HTTP response headers of the requested page.
   *
   * Normally we are only interested in the headers returned by the last
   * request. However, if a page is redirected or HTTP authentication is in use,
   * multiple requests will be required to retrieve the page. Headers from all
   * requests may be requested by passing TRUE to this function.
   *
   * @param $all_requests
   *   Boolean value specifying whether to return headers from all requests
   *   instead of just the last request. Defaults to FALSE.
   *
   * @return
   *   A name/value array if headers from only the last request are requested.
   *   If headers from all requests are requested, an array of name/value
   *   arrays, one for each request.
   *
   *   The pseudonym ":status" is used for the HTTP status line.
   *
   *   Values for duplicate headers are stored as a single comma-separated list.
   */
  protected function drupalGetHeaders($all_requests = FALSE) {
    $request = 0;
    $headers = array($request => array());
    foreach ($this->headers as $header) {
      $header = trim($header);
      if ($header === '') {
        $request++;
      }
      else {
        if (strpos($header, 'HTTP/') === 0) {
          $name = ':status';
          $value = $header;
        }
        else {
          list($name, $value) = explode(':', $header, 2);
          $name = strtolower($name);
        }
        if (isset($headers[$request][$name])) {
          $headers[$request][$name] .= ',' . trim($value);
        }
        else {
          $headers[$request][$name] = trim($value);
        }
      }
    }
    if (!$all_requests) {
      $headers = array_pop($headers);
    }
    return $headers;
  }

  /**
   * Gets the value of an HTTP response header.
   *
   * If multiple requests were required to retrieve the page, only the headers
   * from the last request will be checked by default. However, if TRUE is
   * passed as the second argument, all requests will be processed from last to
   * first until the header is found.
   *
   * @param $name
   *   The name of the header to retrieve. Names are case-insensitive (see RFC
   *   2616 section 4.2).
   * @param $all_requests
   *   Boolean value specifying whether to check all requests if the header is
   *   not found in the last request. Defaults to FALSE.
   *
   * @return
   *   The HTTP header value or FALSE if not found.
   */
  protected function drupalGetHeader($name, $all_requests = FALSE) {
    $name = strtolower($name);
    $header = FALSE;
    if ($all_requests) {
      foreach (array_reverse($this->drupalGetHeaders(TRUE)) as $headers) {
        if (isset($headers[$name])) {
          $header = $headers[$name];
          break;
        }
      }
    }
    else {
      $headers = $this->drupalGetHeaders();
      if (isset($headers[$name])) {
        $header = $headers[$name];
      }
    }
    return $header;
  }

  /**
   * Check if a HTTP response header exists and has the expected value.
   *
   * @param string $header
   *   The header key, example: Content-Type
   * @param string $value
   *   The header value.
   * @param string $message
   *   (optional) A message to display with the assertion.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertHeader($header, $value, $message = '', $group = 'Browser') {
    $header_value = $this->drupalGetHeader($header);
    return $this->assertTrue($header_value == $value, $message ? $message : 'HTTP response header ' . $header . ' with value ' . $value . ' found, actual value: ' . $header_value, $group);
  }

  /**
   * Gets an array containing all emails sent during this test case.
   *
   * @param $filter
   *   An array containing key/value pairs used to filter the emails that are
   *   returned.
   *
   * @return
   *   An array containing email messages captured during the current test.
   */
  protected function drupalGetMails($filter = array()) {
    $captured_emails = \Drupal::state()->get('system.test_mail_collector') ?: array();
    $filtered_emails = array();

    foreach ($captured_emails as $message) {
      foreach ($filter as $key => $value) {
        if (!isset($message[$key]) || $message[$key] != $value) {
          continue 2;
        }
      }
      $filtered_emails[] = $message;
    }

    return $filtered_emails;
  }

  /**
   * Passes if the internal browser's URL matches the given path.
   *
   * @param \Drupal\Core\Url|string $path
   *   The expected system path or URL.
   * @param $options
   *   (optional) Any additional options to pass for $path to the url generator.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertUrl($path, array $options = array(), $message = '', $group = 'Other') {
    if ($path instanceof Url)  {
      $url = $path->setAbsolute()->toString();
    }
    else {
      $options['absolute'] = TRUE;
      $url = $this->container->get('url_generator')->generateFromPath($path, $options);
    }
    if (!$message) {
      $message = SafeMarkup::format('Expected @url matches current URL (@current_url).', array(
        '@url' => var_export($url, TRUE),
        '@current_url' => $this->getUrl(),
      ));
    }
    // Paths in query strings can be encoded or decoded with no functional
    // difference, decode them for comparison purposes.
    $actual_url = urldecode($this->getUrl());
    $expected_url = urldecode($url);
    return $this->assertEqual($actual_url, $expected_url, $message, $group);
  }

  /**
   * Asserts the page responds with the specified response code.
   *
   * @param $code
   *   Response code. For example 200 is a successful page request. For a list
   *   of all codes see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return
   *   Assertion result.
   */
  protected function assertResponse($code, $message = '', $group = 'Browser') {
    $curl_code = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
    $match = is_array($code) ? in_array($curl_code, $code) : $curl_code == $code;
    return $this->assertTrue($match, $message ? $message : SafeMarkup::format('HTTP response expected !code, actual !curl_code', array('!code' => $code, '!curl_code' => $curl_code)), $group);
  }

  /**
   * Asserts the page did not return the specified response code.
   *
   * @param $code
   *   Response code. For example 200 is a successful page request. For a list
   *   of all codes see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return
   *   Assertion result.
   */
  protected function assertNoResponse($code, $message = '', $group = 'Browser') {
    $curl_code = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
    $match = is_array($code) ? in_array($curl_code, $code) : $curl_code == $code;
    return $this->assertFalse($match, $message ? $message : SafeMarkup::format('HTTP response not expected !code, actual !curl_code', array('!code' => $code, '!curl_code' => $curl_code)), $group);
  }

  /**
   * Asserts that the most recently sent email message has the given value.
   *
   * The field in $name must have the content described in $value.
   *
   * @param $name
   *   Name of field or message property to assert. Examples: subject, body,
   *   id, ...
   * @param $value
   *   Value of the field to assert.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Email'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertMail($name, $value = '', $message = '', $group = 'Email') {
    $captured_emails = \Drupal::state()->get('system.test_mail_collector') ?: array();
    $email = end($captured_emails);
    return $this->assertTrue($email && isset($email[$name]) && $email[$name] == $value, $message, $group);
  }

  /**
   * Asserts that the most recently sent email message has the string in it.
   *
   * @param $field_name
   *   Name of field or message property to assert: subject, body, id, ...
   * @param $string
   *   String to search for.
   * @param $email_depth
   *   Number of emails to search for string, starting with most recent.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertMailString($field_name, $string, $email_depth, $message = '', $group = 'Other') {
    $mails = $this->drupalGetMails();
    $string_found = FALSE;
    for ($i = count($mails) -1; $i >= count($mails) - $email_depth && $i >= 0; $i--) {
      $mail = $mails[$i];
      // Normalize whitespace, as we don't know what the mail system might have
      // done. Any run of whitespace becomes a single space.
      $normalized_mail = preg_replace('/\s+/', ' ', $mail[$field_name]);
      $normalized_string = preg_replace('/\s+/', ' ', $string);
      $string_found = (FALSE !== strpos($normalized_mail, $normalized_string));
      if ($string_found) {
        break;
      }
    }
    if (!$message) {
      $message = format_string('Expected text found in @field of email message: "@expected".', array('@field' => $field_name, '@expected' => $string));
    }
    return $this->assertTrue($string_found, $message, $group);
  }

  /**
   * Asserts that the most recently sent email message has the pattern in it.
   *
   * @param $field_name
   *   Name of field or message property to assert: subject, body, id, ...
   * @param $regex
   *   Pattern to search for.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertMailPattern($field_name, $regex, $message = '', $group = 'Other') {
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    $regex_found = preg_match("/$regex/", $mail[$field_name]);
    if (!$message) {
      $message = format_string('Expected text found in @field of email message: "@expected".', array('@field' => $field_name, '@expected' => $regex));
    }
    return $this->assertTrue($regex_found, $message, $group);
  }

  /**
   * Outputs to verbose the most recent $count emails sent.
   *
   * @param $count
   *   Optional number of emails to output.
   */
  protected function verboseEmail($count = 1) {
    $mails = $this->drupalGetMails();
    for ($i = count($mails) -1; $i >= count($mails) - $count && $i >= 0; $i--) {
      $mail = $mails[$i];
      $this->verbose('Email:<pre>' . print_r($mail, TRUE) . '</pre>');
    }
  }

  /**
   * Creates a mock request and sets it on the generator.
   *
   * This is used to manipulate how the generator generates paths during tests.
   * It also ensures that calls to $this->drupalGet() will work when running
   * from run-tests.sh because the url generator no longer looks at the global
   * variables that are set there but relies on getting this information from a
   * request object.
   *
   * @param bool $clean_urls
   *   Whether to mock the request using clean urls.
   * @param $override_server_vars
   *   An array of server variables to override.
   *
   * @return $request
   *   The mocked request object.
   */
  protected function prepareRequestForGenerator($clean_urls = TRUE, $override_server_vars = array()) {
    $request = Request::createFromGlobals();
    $server = $request->server->all();
    if (basename($server['SCRIPT_FILENAME']) != basename($server['SCRIPT_NAME'])) {
      // We need this for when the test is executed by run-tests.sh.
      // @todo Remove this once run-tests.sh has been converted to use a Request
      //   object.
      $cwd = getcwd();
      $server['SCRIPT_FILENAME'] = $cwd . '/' . basename($server['SCRIPT_NAME']);
      $base_path = rtrim($server['REQUEST_URI'], '/');
    }
    else {
      $base_path = $request->getBasePath();
    }
    if ($clean_urls) {
      $request_path = $base_path ? $base_path . '/user' : 'user';
    }
    else {
      $request_path = $base_path ? $base_path . '/index.php/user' : '/index.php/user';
    }
    $server = array_merge($server, $override_server_vars);

    $request = Request::create($request_path, 'GET', array(), array(), array(), $server);
    // Ensure the the request time is REQUEST_TIME to ensure that API calls
    // in the test use the right timestamp.
    $request->server->set('REQUEST_TIME', REQUEST_TIME);
    $this->container->get('request_stack')->push($request);

    // The request context is normally set by the router_listener from within
    // its KernelEvents::REQUEST listener. In the simpletest parent site this
    // event is not fired, therefore it is necessary to updated the request
    // context manually here.
    $this->container->get('router.request_context')->fromRequest($request);

    return $request;
  }

  /**
   * Builds an a absolute URL from a system path or a URL object.
   *
   * @param string|\Drupal\Core\Url $path
   *   A system path or a URL.
   * @param array $options
   *   Options to be passed to Url::fromUri().
   *
   * @return string
   *   An absolute URL stsring.
   */
  protected function buildUrl($path, array $options = array()) {
    if ($path instanceof Url) {
      $url_options = $path->getOptions();
      $options = $url_options + $options;
      $path->setOptions($options);
      return $path->setAbsolute()->toString();
    }
    // The URL generator service is not necessarily available yet; e.g., in
    // interactive installer tests.
    else if ($this->container->has('url_generator')) {
      $options['absolute'] = TRUE;
      return $this->container->get('url_generator')->generateFromPath($path, $options);
    }
    else {
      return $this->getAbsoluteUrl($path);
    }
  }

  /**
   * Asserts whether an expected cache context was present in the last response.
   *
   * @param string $expected_cache_context
   *   The expected cache context.
   */
  protected function assertCacheContext($expected_cache_context) {
    $cache_contexts = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Contexts'));
    $this->assertTrue(in_array($expected_cache_context, $cache_contexts), "'" . $expected_cache_context . "' is present in the X-Drupal-Cache-Contexts header.");
  }

  /**
   * Asserts that a cache context was not present in the last response.
   *
   * @param string $not_expected_cache_context
   *   The expected cache context.
   */
  protected function assertNoCacheContext($not_expected_cache_context) {
    $cache_contexts = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Contexts'));
    $this->assertFalse(in_array($not_expected_cache_context, $cache_contexts), "'" . $not_expected_cache_context . "' is not present in the X-Drupal-Cache-Contexts header.");
  }

  /**
   * Asserts whether an expected cache tag was present in the last response.
   *
   * @param string $expected_cache_tag
   *   The expected cache tag.
   */
  protected function assertCacheTag($expected_cache_tag) {
    $cache_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
    $this->assertTrue(in_array($expected_cache_tag, $cache_tags), "'" . $expected_cache_tag . "' is present in the X-Drupal-Cache-Tags header.");
  }

  /**
   * Asserts whether an expected cache tag was absent in the last response.
   *
   * @param string $cache_tag
   *   The cache tag to check.
   */
  protected function assertNoCacheTag($cache_tag) {
    $cache_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
    $this->assertFalse(in_array($cache_tag, $cache_tags), "'" . $cache_tag . "' is absent in the X-Drupal-Cache-Tags header.");
  }

}
