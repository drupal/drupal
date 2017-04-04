<?php

namespace Drupal\Tests\simpletest\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\File\FileSystemInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests simpletest_run_phpunit_tests() handles PHPunit fatals correctly.
 *
 * We don't extend Drupal\Tests\UnitTestCase here because its $root property is
 * not static and we need it to be static here.
 *
 * @group simpletest
 *
 * @runTestsInSeparateProcesses
 */
class SimpletestPhpunitRunCommandTest extends TestCase {

  /**
   * Path to the app root.
   *
   * @var string
   */
  protected static $root;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    // Figure out our app root.
    self::$root = dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))));
    // Include the files we need for tests. The stub test we will run is
    // SimpletestPhpunitRunCommandTestWillDie which is located in
    // simpletest_phpunit_run_command_test.php.
    include_once self::$root . '/core/modules/simpletest/tests/fixtures/simpletest_phpunit_run_command_test.php';
    // Since we're testing simpletest_run_phpunit_tests(), we need to include
    // simpletest.module.
    include_once self::$root . '/core/modules/simpletest/simpletest.module';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Organize our mock container.
    $container = new ContainerBuilder();
    $container->set('app.root', self::$root);
    $file_system = $this->prophesize(FileSystemInterface::class);
    // The simpletest directory wrapper will always point to /tmp.
    $file_system->realpath('public://simpletest')->willReturn(sys_get_temp_dir());
    $container->set('file_system', $file_system->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Data provider for testSimpletestPhpUnitRunCommand().
   *
   * @return array
   *   Arrays of status codes and the label they're expected to have.
   */
  public function provideStatusCodes() {
    $data = [
      [0, 'pass'],
      [1, 'fail'],
      [2, 'exception'],
    ];
    // All status codes 3 and above should be labeled 'error'.
    // @todo: The valid values here would be 3 to 127. But since the test
    // touches the file system a lot, we only have 3, 4, and 127 for speed.
    foreach ([3, 4, 127] as $status) {
      $data[] = [$status, 'error'];
    }
    return $data;
  }

  /**
   * Test the round trip for PHPUnit execution status codes.
   *
   * @covers ::simpletest_run_phpunit_tests
   *
   * @dataProvider provideStatusCodes
   */
  public function testSimpletestPhpUnitRunCommand($status, $label) {
    $test_id = basename(tempnam(sys_get_temp_dir(), 'xxx'));
    putenv('SimpletestPhpunitRunCommandTestWillDie=' . $status);
    $ret = simpletest_run_phpunit_tests($test_id, [SimpletestPhpunitRunCommandTestWillDie::class]);
    $this->assertSame($ret[0]['status'], $label);
    putenv('SimpletestPhpunitRunCommandTestWillDie');
    unlink(simpletest_phpunit_xml_filepath($test_id));
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // We unset the $base_url global, since the test code sets it as a
    // side-effect.
    unset($GLOBALS['base_url']);
    parent::tearDown();
  }

}
