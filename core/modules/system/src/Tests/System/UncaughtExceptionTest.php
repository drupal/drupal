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
    $this->assertNoText('Oh oh, bananas in the instruments');

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
    $this->assertText('Oh oh, bananas in the instruments');
  }

  /**
   * Tests a missing dependency on a service.
   */
  public function testMissingDependency() {
    $this->drupalGet('broken-service-class');

    $message = 'Argument 1 passed to Drupal\error_service_test\LonelyMonkeyClass::__construct() must be an instance of Drupal\Core\Database\Connection, non';

    $this->assertRaw('The website encountered an unexpected error.');
    $this->assertRaw($message);

    $found_exception = FALSE;
    foreach ($this->assertions as &$assertion) {
      if (strpos($assertion['message'], $message) !== FALSE) {
        $found_exception = TRUE;
        $this->deleteAssert($assertion['message_id']);
        unset($assertion);
      }
    }

    $this->assertTrue($found_exception, 'Ensure that the exception of a missing constructor argument was triggered.');
  }

  /**
   * Tests a container which has an error.
   */
  public function testErrorContainer() {
    $kernel = ErrorContainerRebuildKernel::createFromRequest($this->prepareRequestForGenerator(), $this->classLoader, 'prod', TRUE);
    $kernel->rebuildContainer();

    $this->prepareRequestForGenerator();
    // Ensure that we don't use the now broken generated container on the test
    // process.
    \Drupal::setContainer($this->container);

    $this->drupalGet('');

    $message = 'Argument 1 passed to Drupal\system\Tests\Bootstrap\ErrorContainer::Drupal\system\Tests\Bootstrap\{closur';
    $this->assertRaw($message);

    $found_error = FALSE;
    foreach ($this->assertions as &$assertion) {
      if (strpos($assertion['message'], $message) !== FALSE) {
        $found_error = TRUE;
        $this->deleteAssert($assertion['message_id']);
        unset($assertion);
      }
    }

    $this->assertTrue($found_error, 'Ensure that the error of the container was triggered.');
  }

  /**
   * Tests a container which has an exception really early.
   */
  public function testExceptionContainer() {
    $kernel = ExceptionContainerRebuildKernel::createFromRequest($this->prepareRequestForGenerator(), $this->classLoader, 'prod', TRUE);
    $kernel->rebuildContainer();

    $this->prepareRequestForGenerator();
    // Ensure that we don't use the now broken generated container on the test
    // process.
    \Drupal::setContainer($this->container);

    $this->drupalGet('');

    $message = 'Thrown exception during Container::get';

    $this->assertRaw('The website encountered an unexpected error');
    $this->assertRaw($message);

    $found_exception = FALSE;
    foreach ($this->assertions as &$assertion) {
      if (strpos($assertion['message'], $message) !== FALSE) {
        $found_exception = TRUE;
        $this->deleteAssert($assertion['message_id']);
        unset($assertion);
      }
    }
    $this->assertTrue($found_exception, 'Ensure that the exception of the container was triggered.');
  }

  /**
   * Tests the case when the database connection is gone.
   */
  public function testLostDatabaseConnection() {
    // We simulate a broken database connection by rewrite settings.php to no
    // longer have the proper data.
    $settings['databases']['default']['default']['password'] = (object) array(
      'value' => $this->randomMachineName(),
      'required' => TRUE,
    );
    $this->writeSettings($settings);

    $this->drupalGet('');

    $message = 'Access denied for user';
    $this->assertRaw($message);

    $found_exception = FALSE;
    foreach ($this->assertions as &$assertion) {
      if (strpos($assertion['message'], $message) !== FALSE) {
        $found_exception = TRUE;
        $this->deleteAssert($assertion['message_id']);
        unset($assertion);
      }
    }
    $this->assertTrue($found_exception, 'Ensure that the access denied DB connection exception is thrown.');

  }

  /**
   * {@inheritdoc}
   */
  protected function error($message = '', $group = 'Other', array $caller = NULL) {
    if ($message === 'Oh oh, bananas in the instruments.') {
      // We're expecting this error.
      return;
    }
    return parent::error($message, $group, $caller);
  }

}
