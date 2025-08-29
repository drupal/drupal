<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\Core\Test\JUnitConverter;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Test\JUnitConverter.
 *
 * This test class has significant overlap with
 * Drupal\Tests\simpletest\Kernel\PhpUnitErrorTest.
 *
 * @see \Drupal\Tests\simpletest\Kernel\PhpUnitErrorTest
 */
#[CoversClass(JUnitConverter::class)]
#[Group('Test')]
#[Group('simpletest')]
class JUnitConverterTest extends UnitTestCase {

  /**
   * Tests errors reported.
   *
   * @legacy-covers ::xmlToRows
   */
  public function testXmlToRowsWithErrors(): void {
    $phpunit_error_xml = __DIR__ . '/../../../../fixtures/phpunit_error.xml';

    $res = JUnitConverter::xmlToRows(1, $phpunit_error_xml);
    $this->assertCount(4, $res, 'All test cases got extracted');
    $this->assertSame('fail', $res[0]['status']);
    $this->assertSame('fail', $res[1]['status']);
    $this->assertSame('error', $res[2]['status']);
    $this->assertSame('pass', $res[3]['status']);

    // Make sure xmlToRows() does not balk if there are no test results.
    $this->assertSame([], JUnitConverter::xmlToRows(1, 'does_not_exist'));
  }

  /**
   * Tests skips reported.
   */
  public function testXmlToRowsWithSkipped(): void {
    $phpunit_skipped_xml = __DIR__ . '/../../../../fixtures/phpunit_skipped.xml';

    $res = JUnitConverter::xmlToRows(1, $phpunit_skipped_xml);
    $this->assertCount(93, $res, 'All test cases got extracted');
    for ($i = 0; $i < 81; $i++) {
      $this->assertSame('pass', $res[$i]['status'], 'Fail at offset ' . $i);
    }
    for ($i = 81; $i < 85; $i++) {
      $this->assertSame('skipped', $res[$i]['status'], 'Fail at offset ' . $i);
    }
    for ($i = 85; $i < 90; $i++) {
      $this->assertSame('pass', $res[$i]['status'], 'Fail at offset ' . $i);
    }
    $this->assertSame('skipped', $res[90]['status']);
    $this->assertSame('pass', $res[91]['status']);
    $this->assertSame('pass', $res[92]['status']);
  }

  /**
   * Tests xml to rows empty file.
   *
   * @legacy-covers ::xmlToRows
   */
  public function testXmlToRowsEmptyFile(): void {
    // File system with an empty XML file.
    vfsStream::setup('junit_test', NULL, ['empty.xml' => '']);
    $this->assertSame([], JUnitConverter::xmlToRows(23, vfsStream::url('junit_test/empty.xml')));
  }

  /**
   * Tests xml element to rows.
   *
   * @legacy-covers ::xmlElementToRows
   */
  public function testXmlElementToRows(): void {
    $junit = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
  <testsuite name="Drupal\Tests\simpletest\Unit\TestDiscoveryTest" file="/Users/paul/projects/drupal/core/modules/simpletest/tests/src/Unit/TestDiscoveryTest.php" tests="3" assertions="5" errors="0" failures="0" skipped="0" time="0.215539">
    <testcase name="testGetTestClasses" class="Drupal\Tests\simpletest\Unit\TestDiscoveryTest" classname="Drupal.Tests.simpletest.Unit.TestDiscoveryTest" file="/Users/paul/projects/drupal/core/modules/simpletest/tests/src/Unit/TestDiscoveryTest.php" line="108" assertions="2" time="0.100787"/>
  </testsuite>
</testsuites>
EOD;
    $expected = [
      [
        'test_id' => 23,
        'test_class' => 'Drupal\Tests\simpletest\Unit\TestDiscoveryTest',
        'status' => 'pass',
        'message' => '',
        'message_group' => 'Other',
        'function' => 'Drupal\Tests\simpletest\Unit\TestDiscoveryTest->testGetTestClasses()',
        'line' => 108,
        'file' => '/Users/paul/projects/drupal/core/modules/simpletest/tests/src/Unit/TestDiscoveryTest.php',
      ],
    ];
    $actual = JUnitConverter::xmlElementToRows(23, new \SimpleXMLElement($junit));
    unset($actual['time']);
    $this->assertEquals($expected, $expected);
  }

  /**
   * Tests convert test case to simpletest row.
   *
   * @legacy-covers ::convertTestCaseToSimpletestRow
   */
  public function testConvertTestCaseToSimpletestRow(): void {
    $junit = <<<EOD
    <testcase name="testGetTestClasses" class="Drupal\Tests\simpletest\Unit\TestDiscoveryTest" classname="Drupal.Tests.simpletest.Unit.TestDiscoveryTest" file="/Users/paul/projects/drupal/core/modules/simpletest/tests/src/Unit/TestDiscoveryTest.php" line="108" assertions="2" time="0.100787"/>
EOD;
    $expected = [
      'test_id' => 23,
      'test_class' => 'Drupal\Tests\simpletest\Unit\TestDiscoveryTest',
      'status' => 'pass',
      'message' => '',
      'message_group' => 'Other',
      'function' => 'Drupal\Tests\simpletest\Unit\TestDiscoveryTest->testGetTestClasses()',
      'line' => 108,
      'file' => '/Users/paul/projects/drupal/core/modules/simpletest/tests/src/Unit/TestDiscoveryTest.php',
    ];
    $actual = JUnitConverter::xmlElementToRows(23, new \SimpleXMLElement($junit));
    unset($actual['time']);
    $this->assertEquals($expected, $expected);
  }

}
