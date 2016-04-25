<?php

namespace Drupal\Tests\simpletest\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Tests simpletest_run_phpunit_tests() handles PHPunit fatals correctly.
 *
 * @group simpletest
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SimpletestPhpunitRunCommandTest extends UnitTestCase {

  function testSimpletestPhpUnitRunCommand() {
    include_once __DIR__ .'/../../fixtures/simpletest_phpunit_run_command_test.php';
    $app_root = __DIR__ . '/../../../../../..';
    include_once "$app_root/core/modules/simpletest/simpletest.module";
    $container = new ContainerBuilder;
    $container->set('app.root', $app_root);
    $file_system = $this->prophesize('Drupal\Core\File\FileSystemInterface');
    $file_system->realpath('public://simpletest')->willReturn(sys_get_temp_dir());
    $container->set('file_system', $file_system->reveal());
    \Drupal::setContainer($container);
    $test_id = basename(tempnam(sys_get_temp_dir(), 'xxx'));
    foreach (['pass', 'fail'] as $status) {
      putenv('SimpletestPhpunitRunCommandTestWillDie=' . $status);
      $ret = simpletest_run_phpunit_tests($test_id, ['Drupal\Tests\simpletest\Unit\SimpletestPhpunitRunCommandTestWillDie']);
      $this->assertSame($ret[0]['status'], $status);
    }
    unlink(simpletest_phpunit_xml_filepath($test_id));
  }

}
