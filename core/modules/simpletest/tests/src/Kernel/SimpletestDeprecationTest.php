<?php

namespace Drupal\Tests\simpletest\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\TestDiscovery;

/**
 * Verify deprecations within the simpletest module.
 *
 * @group simpletest
 * @group legacy
 */
class SimpletestDeprecationTest extends KernelTestBase {

  public static $modules = ['simpletest'];

  /**
   * @expectedDeprecation The simpletest_phpunit_configuration_filepath function is deprecated since version 8.4.x and will be removed in 9.0.0.
   * @expectedDeprecation The simpletest_test_get_all function is deprecated in version 8.3.x and will be removed in 9.0.0. Use \Drupal::service('test_discovery')->getTestClasses($extension, $types) instead.
   * @expectedDeprecation The simpletest_classloader_register function is deprecated in version 8.3.x and will be removed in 9.0.0. Use \Drupal::service('test_discovery')->registerTestNamespaces() instead.
   */
  public function testDeprecatedFunctions() {
    $this->assertNotEmpty(simpletest_phpunit_configuration_filepath());
    $this->assertNotEmpty(simpletest_test_get_all());
    simpletest_classloader_register();
  }

  /**
   * @expectedDeprecation Drupal\simpletest\TestDiscovery is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Test\TestDiscovery instead. See https://www.drupal.org/node/2949692
   * @expectedDeprecation The "test_discovery" service relies on the deprecated "Drupal\simpletest\TestDiscovery" class. It should either be deprecated or its implementation upgraded.
   */
  public function testDeprecatedServices() {
    $this->assertInstanceOf(TestDiscovery::class, $this->container->get('test_discovery'));
  }

  /**
   * @expectedDeprecation simpletest_phpunit_xml_filepath is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Test\PhpUnitTestRunner::xmlLogFilepath() instead. See https://www.drupal.org/node/2948547
   * @expectedDeprecation simpletest_phpunit_command is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Test\PhpUnitTestRunner::phpUnitCommand() instead. See https://www.drupal.org/node/2948547
   * @expectedDeprecation simpletest_phpunit_find_testcases is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Test\JUnitConverter::findTestCases() instead. See https://www.drupal.org/node/2948547
   * @expectedDeprecation simpletest_phpunit_testcase_to_row is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Test\JUnitConverter::convertTestCaseToSimpletestRow() instead. See https://www.drupal.org/node/2948547
   * @expectedDeprecation simpletest_summarize_phpunit_result is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Test\PhpUnitTestRunner::summarizeResults() instead. See https://www.drupal.org/node/2948547
   */
  public function testDeprecatedPhpUnitFunctions() {
    // We can't test the deprecation errors for the following functions because
    // they cannot be mocked, and calling them would change the test results:
    // - simpletest_run_phpunit_tests().
    // - simpletest_phpunit_run_command().
    // - simpletest_phpunit_xml_to_rows().
    $this->assertStringEndsWith('/phpunit-23.xml', simpletest_phpunit_xml_filepath(23));

    $this->assertInternalType('string', simpletest_phpunit_command());

    $this->assertEquals([], simpletest_phpunit_find_testcases(new \SimpleXMLElement('<not_testcase></not_testcase>')));

    $this->assertEquals([
      'test_id' => 23,
      'test_class' => '',
      'status' => 'pass',
      'message' => '',
      'message_group' => 'Other',
      'function' => '->()',
      'line' => 0,
      'file' => NULL,
    ], simpletest_phpunit_testcase_to_row(23, new \SimpleXMLElement('<not_testcase></not_testcase>')));

    $this->assertEquals(
      [
        static::class => [
          '#pass' => 0,
          '#fail' => 0,
          '#exception' => 0,
          '#debug' => 1,
        ],
      ],
      simpletest_summarize_phpunit_result([
        [
          'test_class' => static::class,
          'status' => 'debug',
        ],
      ])
    );
  }

  /**
   * @expectedDeprecation simpletest_generate_file() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Tests\TestFileCreationTrait::generateFile() instead. See https://www.drupal.org/node/3077768
   */
  public function testDeprecatedSimpletestGenerateFile() {
    $file = simpletest_generate_file('foo', 40, 10);
    $public_file = 'public://' . $file . '.txt';
    $this->assertFileExists($public_file);
    $this->assertTrue(unlink($public_file));
  }

  /**
   * @expectedDeprecation simpletest_process_phpunit_results() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Test\TestDatabase::processPhpUnitResults() instead. See https://www.drupal.org/node/3075252
   */
  public function testProcessPhpUnitResults() {
    // The only safe way to test this deprecation is to call it with an empty
    // result set. This should not touch the results database.
    $this->assertNull(simpletest_process_phpunit_results([]));
  }

}
