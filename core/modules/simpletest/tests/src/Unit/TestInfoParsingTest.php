<?php

namespace Drupal\Tests\simpletest\Unit;

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
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\Tests\simpletest\Unit\TestInfoParsingTest',
        'group' => 'PHPUnit',
        'description' => 'Tests \Drupal\simpletest\TestDiscovery.',
      ],
      // Classname.
      'Drupal\Tests\simpletest\Unit\TestInfoParsingTest',
    ];

    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\field\Tests\BulkDeleteTest',
        'group' => 'field',
        'description' => 'Bulk delete storages and fields, and clean up afterwards.',
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

    // Multiple @group annotations.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\field\Tests\BulkDeleteTest',
        'group' => 'Test',
        'description' => 'Bulk delete storages and fields, and clean up afterwards.',
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
   * @expectedException \Drupal\simpletest\Exception\MissingSummaryLineException
   * @expectedExceptionMessage Missing PHPDoc summary line in Drupal\field\Tests\BulkDeleteTest
   */
  public function testTestInfoParserMissingSummary() {
    $classname = 'Drupal\field\Tests\BulkDeleteTest';
    $doc_comment = <<<EOT
/**
 * @group field
 */
EOT;
    \Drupal\simpletest\TestDiscovery::getTestInfo($classname, $doc_comment);
  }

}
