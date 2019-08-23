<?php

namespace Drupal\Tests\simpletest\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests PHPUnit errors are getting converted to Simpletest errors.
 *
 * @group simpletest
 * @group legacy
 */
class PhpUnitErrorTest extends KernelTestBase {

  /**
   * Enable the simpletest module.
   *
   * @var string[]
   */
  protected static $modules = ['simpletest'];

  /**
   * Test errors reported.
   *
   * @expectedDeprecation simpletest_phpunit_xml_to_rows is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Test\JUnitConverter::xmlToRows() instead. See https://www.drupal.org/node/2948547
   */
  public function testPhpUnitXmlParsing() {
    $phpunit_error_xml = __DIR__ . '/../../fixtures/phpunit_error.xml';

    $res = simpletest_phpunit_xml_to_rows(1, $phpunit_error_xml);
    $this->assertEquals(count($res), 4, 'All testcases got extracted');
    $this->assertNotEquals($res[0]['status'], 'pass');
    $this->assertEquals($res[0]['status'], 'fail');

    // Test nested testsuites, which appear when you use @dataProvider.
    for ($i = 0; $i < 3; $i++) {
      $this->assertNotEquals($res[$i + 1]['status'], 'pass');
      $this->assertEquals($res[$i + 1]['status'], 'fail');
    }

    // Make sure simpletest_phpunit_xml_to_rows() does not balk if the test
    // didn't run.
    $this->assertNull(simpletest_phpunit_xml_to_rows(1, 'does_not_exist'));
  }

}
