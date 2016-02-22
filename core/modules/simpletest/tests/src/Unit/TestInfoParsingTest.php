<?php

/**
 * @file
 * Contains \Drupal\Tests\simpletest\Unit\TestInfoParsingTest.
 */

namespace Drupal\Tests\simpletest\Unit;

use Drupal\simpletest\TestDiscovery;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\simpletest\TestDiscovery
 * @group simpletest
 */
class TestInfoParsingTest extends UnitTestCase {

  /**
   * @covers ::getTestInfo
   * @dataProvider infoParserProvider
   */
  public function testTestInfoParser($expected, $classname, $doc_comment = NULL) {
    $info = \Drupal\simpletest\TestDiscovery::getTestInfo($classname, $doc_comment);
    $this->assertEquals($expected, $info);
  }

  public function infoParserProvider() {
    // A module provided unit test.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\Tests\simpletest\Unit\TestInfoParsingTest',
        'group' => 'simpletest',
        'description' => 'Tests \Drupal\simpletest\TestDiscovery.',
        'type' => 'PHPUnit-Unit',
      ],
      // Classname.
      'Drupal\Tests\simpletest\Unit\TestInfoParsingTest',
    ];

    // A core unit test.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\Tests\Core\DrupalTest',
        'group' => 'DrupalTest',
        'description' => 'Tests \Drupal.',
        'type' => 'PHPUnit-Unit',
      ],
      // Classname.
      'Drupal\Tests\Core\DrupalTest',
    ];

    // Functional PHPUnit test.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\Tests\simpletest\Functional\BrowserTestBaseTest',
        'group' => 'simpletest',
        'description' => 'Tests BrowserTestBase functionality.',
        'type' => 'PHPUnit-Functional',
      ],
      // Classname.
      'Drupal\Tests\simpletest\Functional\BrowserTestBaseTest',
    ];

    // kernel PHPUnit test.
    $tests['phpunit-kernel'] = [
      // Expected result.
      [
        'name' => '\Drupal\Tests\file\Kernel\FileItemValidationTest',
        'group' => 'file',
        'description' => 'Tests that files referenced in file and image fields are always validated.',
        'type' => 'PHPUnit-Kernel',
      ],
      // Classname.
      '\Drupal\Tests\file\Kernel\FileItemValidationTest',
    ];

    // Simpletest classes can not be autoloaded in a PHPUnit test, therefore
    // provide a docblock.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\field\Tests\BulkDeleteTest',
        'group' => 'field',
        'description' => 'Bulk delete storages and fields, and clean up afterwards.',
        'type' => 'Simpletest',
      ],
      // Classname.
      'Drupal\field\Tests\BulkDeleteTest',
      // Doc block.
      "/**
 * Bulk delete storages and fields, and clean up afterwards.
 *
 * @group field
 */
 ",
    ];

    // Test with a different amount of leading spaces.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\field\Tests\BulkDeleteTest',
        'group' => 'field',
        'description' => 'Bulk delete storages and fields, and clean up afterwards.',
        'type' => 'Simpletest'
      ],
      // Classname.
      'Drupal\field\Tests\BulkDeleteTest',
      // Doc block.
      "/**
   * Bulk delete storages and fields, and clean up afterwards.
   *
   * @group field
   */
 ",
    ];

    // Make sure that a "* @" inside a string does not get parsed as an
    // annotation.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\field\Tests\BulkDeleteTest',
        'group' => 'field',
        'description' => 'Bulk delete storages and fields, and clean up afterwards. * @',
        'type' => 'Simpletest'
      ],
      // Classname.
      'Drupal\field\Tests\BulkDeleteTest',
      // Doc block.
      "/**
   * Bulk delete storages and fields, and clean up afterwards. * @
   *
   * @group field
   */
 ",
    ];

    // Multiple @group annotations.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\field\Tests\BulkDeleteTest',
        'group' => 'Test',
        'description' => 'Bulk delete storages and fields, and clean up afterwards.',
        'type' => 'Simpletest'
      ],
      // Classname.
      'Drupal\field\Tests\BulkDeleteTest',
      // Doc block.
      "/**
 * Bulk delete storages and fields, and clean up afterwards.
 *
 * @group Test
 * @group field
 */
 ",
    ];

    // @dependencies annotation.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\field\Tests\BulkDeleteTest',
        'group' => 'field',
        'description' => 'Bulk delete storages and fields, and clean up afterwards.',
        'requires' => ['module' => ['test']],
        'type' => 'Simpletest'
      ],
      // Classname.
      'Drupal\field\Tests\BulkDeleteTest',
      // Doc block.
      "/**
 * Bulk delete storages and fields, and clean up afterwards.
 *
 * @dependencies test
 * @group field
 */
 ",
    ];

    // Multiple @dependencies annotation.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\field\Tests\BulkDeleteTest',
        'group' => 'field',
        'description' => 'Bulk delete storages and fields, and clean up afterwards.',
        'requires' => ['module' => ['test', 'test1', 'test2']],
        'type' => 'Simpletest'
      ],
      // Classname.
      'Drupal\field\Tests\BulkDeleteTest',
      // Doc block.
      "/**
 * Bulk delete storages and fields, and clean up afterwards.
 *
 * @dependencies test, test1,test2
 * @group field
 */
 ",
    ];

    // Multi-line summary line.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\field\Tests\BulkDeleteTest',
        'group' => 'field',
        'description' => 'Bulk delete storages and fields, and clean up afterwards. And the summary line continues and there is no gap to the annotation.',
        'type' => 'Simpletest'
      ],
      // Classname.
      'Drupal\field\Tests\BulkDeleteTest',
      // Doc block.
      "/**
 * Bulk delete storages and fields, and clean up afterwards. And the summary
 * line continues and there is no gap to the annotation.
 * @group field
 */
 ",
    ];
    return $tests;
  }

  /**
   * @covers ::getTestInfo
   * @expectedException \Drupal\simpletest\Exception\MissingGroupException
   * @expectedExceptionMessage Missing @group annotation in Drupal\field\Tests\BulkDeleteTest
   */
  public function testTestInfoParserMissingGroup() {
    $classname = 'Drupal\field\Tests\BulkDeleteTest';
    $doc_comment = <<<EOT
/**
 * Bulk delete storages and fields, and clean up afterwards.
 */
EOT;
    \Drupal\simpletest\TestDiscovery::getTestInfo($classname, $doc_comment);
  }

  /**
   * @covers ::getTestInfo
   */
  public function testTestInfoParserMissingSummary() {
    $classname = 'Drupal\field\Tests\BulkDeleteTest';
    $doc_comment = <<<EOT
/**
 * @group field
 */
EOT;
    $info = \Drupal\simpletest\TestDiscovery::getTestInfo($classname, $doc_comment);
    $this->assertEmpty($info['description']);
  }

  /**
   * @covers ::getPhpunitTestSuite
   * @dataProvider providerTestGetPhpunitTestSuite
   */
  public function testGetPhpunitTestSuite($classname, $expected) {
    $this->assertEquals($expected, TestDiscovery::getPhpunitTestSuite($classname));
  }

  public function providerTestGetPhpunitTestSuite() {
    $data = [];
    $data['simpletest-webtest'] = ['\Drupal\rest\Tests\NodeTest', FALSE];
    $data['simpletest-kerneltest'] = ['\Drupal\hal\Tests\FileNormalizeTest', FALSE];
    $data['module-unittest'] = [static::class, 'Unit'];
    $data['module-kerneltest'] = ['\Drupal\KernelTests\Core\Theme\TwigMarkupInterfaceTest', 'Kernel'];
    $data['module-functionaltest'] = ['\Drupal\Tests\simpletest\Functional\BrowserTestBaseTest', 'Functional'];
    $data['module-functionaljavascripttest'] = ['\Drupal\Tests\toolbar\FunctionalJavascript\ToolbarIntegrationTest', 'FunctionalJavascript'];
    $data['core-unittest'] = ['\Drupal\Tests\ComposerIntegrationTest', 'Unit'];
    $data['core-unittest2'] = ['Drupal\Tests\Core\DrupalTest', 'Unit'];
    $data['core-kerneltest'] = ['\Drupal\KernelTests\KernelTestBaseTest', 'Kernel'];
    $data['core-functionaltest'] = ['\Drupal\FunctionalTests\ExampleTest', 'Functional'];
    $data['core-functionaljavascripttest'] = ['\Drupal\FunctionalJavascriptTests\ExampleTest', 'FunctionalJavascript'];

    return $data;
  }

}
