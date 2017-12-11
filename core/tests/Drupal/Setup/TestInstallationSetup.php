<?php

namespace Drupal\Setup;

use Drupal\Core\Database\Database;
use Drupal\Core\Test\FunctionalTestSetupTrait;
use Drupal\Core\Test\TestSetupTrait;
use Drupal\Tests\RandomGeneratorTrait;
use Drupal\Tests\SessionTestTrait;

/**
 * Provides a class used by setup-drupal-test.php to install Drupal for tests.
 *
 * @internal
 */
class TestInstallationSetup {

  use FunctionalTestSetupTrait;
  use RandomGeneratorTrait;
  use SessionTestTrait;
  use TestSetupTrait;

  /**
   * The install profile to use.
   *
   * @var string
   */
  protected $profile;

  /**
   * Time limit in seconds for the test.
   *
   * @var int
   */
  protected $timeLimit = 500;

  /**
   * The database prefix of this test run.
   *
   * @var string
   */
  protected $databasePrefix;

  /**
   * Creates a test drupal installation.
   *
   * @param string $profile
   *   (optional) The installation profile to use.
   * @param string $setup_file
   *   (optional) Setup file. A PHP file to setup configuration used by the
   *   test.
   */
  public function setup($profile = 'testing', $setup_file = NULL) {
    $this->profile = $profile;
    $this->setupBaseUrl();
    $this->prepareEnvironment();
    $this->installDrupal();

    if ($setup_file) {
      $this->executeSetupFile($setup_file);
    }
  }

  /**
   * Gets the database prefix.
   *
   * @return string
   */
  public function getDatabasePrefix() {
    return $this->databasePrefix;
  }

  /**
   * Installs Drupal into the Simpletest site.
   */
  protected function installDrupal() {
    $this->initUserSession();
    $this->prepareSettings();
    $this->doInstall();
    $this->initSettings();
    $container = $this->initKernel(\Drupal::request());
    $this->initConfig($container);
    $this->installModulesFromClassProperty($container);
    $this->rebuildAll();
  }

  /**
   * Uses the setup file to configure Drupal.
   *
   * @param string $setup_file
   *   The setup file.
   */
  protected function executeSetupFile($setup_file) {
    $classes = static::fileGetPhpClasses($setup_file);

    if (empty($classes)) {
      throw new \InvalidArgumentException(sprintf('You need to define a class implementing \Drupal\Setup\TestSetupInterface'));
    }
    if (count($classes) > 1) {
      throw new \InvalidArgumentException(sprintf('You need to define a single class implementing \Drupal\Setup\TestSetupInterface'));
    }
    if (!is_subclass_of($classes[0], TestSetupInterface::class)) {
      throw new \InvalidArgumentException(sprintf('You need to define a class implementing \Drupal\Setup\TestSetupInterface'));
    }

    require_once $setup_file;

    /** @var \Drupal\Setup\TestSetupInterface $instance */
    $instance = new $classes[0];
    $instance->setup();
  }

  /**
   * Gets the PHP classes contained in a php file.
   *
   * @param string $filepath
   *   The file path.
   *
   * @return string[]
   *   An array of PHP classes.
   */
  protected static function fileGetPhpClasses($filepath) {
    $php_code = file_get_contents($filepath);
    $classes = static::extractClassesFromPhp($php_code);
    return $classes;
  }

  /**
   * @param string $php_code
   *   PHP code to parse.
   *
   * @return string[]
   *   An array of PHP classes.
   */
  protected static function extractClassesFromPhp($php_code) {
    $classes = array();
    $tokens = token_get_all($php_code);
    $count = count($tokens);
    for ($i = 2; $i < $count; $i++) {
      if ($tokens[$i - 2][0] == T_CLASS
        && $tokens[$i - 1][0] == T_WHITESPACE
        && $tokens[$i][0] == T_STRING
      ) {

        $class_name = $tokens[$i][1];
        $classes[] = $class_name;
      }
    }
    return $classes;
  }

  /**
   * {@inheritdoc}
   */
  protected function installParameters() {
    $connection_info = Database::getConnectionInfo();
    $driver = $connection_info['default']['driver'];
    $connection_info['default']['prefix'] = $connection_info['default']['prefix']['default'];
    unset($connection_info['default']['driver']);
    unset($connection_info['default']['namespace']);
    unset($connection_info['default']['pdo']);
    unset($connection_info['default']['init_commands']);
    $parameters = [
      'interactive' => FALSE,
      'parameters' => [
        'profile' => $this->profile,
        'langcode' => 'en',
      ],
      'forms' => [
        'install_settings_form' => [
          'driver' => $driver,
          $driver => $connection_info['default'],
        ],
        'install_configure_form' => [
          'site_name' => 'Drupal',
          'site_mail' => 'simpletest@example.com',
          'account' => [
            'name' => $this->rootUser->name,
            'mail' => $this->rootUser->getEmail(),
            'pass' => [
              'pass1' => $this->rootUser->pass_raw,
              'pass2' => $this->rootUser->pass_raw,
            ],
          ],
          // form_type_checkboxes_value() requires NULL instead of FALSE values
          // for programmatic form submissions to disable a checkbox.
          'enable_update_status_module' => NULL,
          'enable_update_status_emails' => NULL,
        ],
      ],
    ];
    return $parameters;
  }

}
