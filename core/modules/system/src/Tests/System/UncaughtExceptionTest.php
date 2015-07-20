<?php
/**
 * @file
 * Contains \Drupal\system\Tests\System\UncaughtExceptionTest
 */

namespace Drupal\system\Tests\System;


use Drupal\simpletest\WebTestBase;

/**
 * Tests kernel panic when things are really messed up.
 *
 * @group system
 */
class UncaughtExceptionTest extends WebTestBase {

  /**
   * Exceptions thrown by site under test that contain this text are ignored.
   *
   * @var string
   */
  protected $expectedExceptionMessage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('error_service_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $settings_filename = $this->siteDirectory . '/settings.php';
    chmod($settings_filename, 0777);
    $settings_php = file_get_contents($settings_filename);
    $settings_php .= "\ninclude_once 'core/modules/system/src/Tests/Bootstrap/ErrorContainer.php';\n";
    $settings_php .= "\ninclude_once 'core/modules/system/src/Tests/Bootstrap/ExceptionContainer.php';\n";
    file_put_contents($settings_filename, $settings_php);

    $settings = [];
    $settings['config']['system.logging']['error_level'] = (object) [
      'value' => ERROR_REPORTING_DISPLAY_VERBOSE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * Tests uncaught exception handling when system is in a bad state.
   */
  public function testUncaughtException() {
    $this->expectedExceptionMessage = 'Oh oh, bananas in the instruments.';
    \Drupal::state()->set('error_service_test.break_bare_html_renderer', TRUE);

    $this->config('system.logging')
      ->set('error_level', ERROR_REPORTING_HIDE)
      ->save();
    $settings = [];
    $settings['config']['system.logging']['error_level'] = (object) [
      'value' => ERROR_REPORTING_HIDE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $this->drupalGet('');
    $this->assertResponse(500);
    $this->assertText('The website encountered an unexpected error. Please try again later.');
    $this->assertNoText($this->expectedExceptionMessage);

    $this->config('system.logging')
      ->set('error_level', ERROR_REPORTING_DISPLAY_ALL)
      ->save();
    $settings = [];
    $settings['config']['system.logging']['error_level'] = (object) [
      'value' => ERROR_REPORTING_DISPLAY_ALL,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $this->drupalGet('');
    $this->assertResponse(500);
    $this->assertText('The website encountered an unexpected error. Please try again later.');
    $this->assertText($this->expectedExceptionMessage);
  }

  /**
   * Tests a missing dependency on a service.
   */
  public function testMissingDependency() {
    $this->expectedExceptionMessage = 'Argument 1 passed to Drupal\error_service_test\LonelyMonkeyClass::__construct() must be an instance of Drupal\Core\Database\Connection, non';
    $this->drupalGet('broken-service-class');

    $this->assertRaw('The website encountered an unexpected error.');
    $this->assertRaw($this->expectedExceptionMessage);
  }

  /**
   * Tests a container which has an error.
   */
  public function testErrorContainer() {
    $settings = [];
    $settings['settings']['container_base_class'] = (object) [
      'value' => '\Drupal\system\Tests\Bootstrap\ErrorContainer',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    // Need to rebuild the container, so the dumped container can be tested
    // and not the container builder.
    \Drupal::service('kernel')->rebuildContainer();

    // Ensure that we don't use the now broken generated container on the test
    // process.
    \Drupal::setContainer($this->container);

    $this->expectedExceptionMessage = 'Argument 1 passed to Drupal\system\Tests\Bootstrap\ErrorContainer::Drupal\system\Tests\Bootstrap\{closur';
    $this->drupalGet('');

    $this->assertRaw($this->expectedExceptionMessage);
  }

  /**
   * Tests a container which has an exception really early.
   */
  public function testExceptionContainer() {
    $settings = [];
    $settings['settings']['container_base_class'] = (object) [
      'value' => '\Drupal\system\Tests\Bootstrap\ExceptionContainer',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    // Need to rebuild the container, so the dumped container can be tested
    // and not the container builder.
    \Drupal::service('kernel')->rebuildContainer();

    // Ensure that we don't use the now broken generated container on the test
    // process.
    \Drupal::setContainer($this->container);

    $this->expectedExceptionMessage = 'Thrown exception during Container::get';
    $this->drupalGet('');


    $this->assertRaw('The website encountered an unexpected error');
    $this->assertRaw($this->expectedExceptionMessage);
  }

  /**
   * Tests the case when the database connection is gone.
   */
  public function testLostDatabaseConnection() {
    $incorrect_username = $this->randomMachineName(16);
    switch ($this->container->get('database')->driver()) {
      case 'pgsql':
      case 'mysql':
        $this->expectedExceptionMessage = $incorrect_username;
        break;
      default:
        // We can not carry out this test.
        $this->pass('Unable to run \Drupal\system\Tests\System\UncaughtExceptionTest::testLostDatabaseConnection for this database type.');
        return;
    }

    // We simulate a broken database connection by rewrite settings.php to no
    // longer have the proper data.
    $settings['databases']['default']['default']['username'] = (object) array(
      'value' => $incorrect_username,
      'required' => TRUE,
    );
    $settings['databases']['default']['default']['passowrd'] = (object) array(
      'value' => $this->randomMachineName(16),
      'required' => TRUE,
    );

    $this->writeSettings($settings);

    $this->drupalGet('');
    $this->assertRaw('PDOException');
  }

  /**
   * {@inheritdoc}
   */
  protected function error($message = '', $group = 'Other', array $caller = NULL) {
    if (!empty($this->expectedExceptionMessage) && strpos($message, $this->expectedExceptionMessage) !== FALSE) {
      // We're expecting this error.
      return FALSE;
    }
    return parent::error($message, $group, $caller);
  }

}
