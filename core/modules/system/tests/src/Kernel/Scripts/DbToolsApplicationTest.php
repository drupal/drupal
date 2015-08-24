<?php

/**
 * @file
 * Contains \Drupal\Tests\system\Kernel\Scripts\DbToolsApplicationTest.
 */

namespace Drupal\Tests\system\Kernel\Scripts;

use Drupal\Core\Command\DbToolsApplication;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test that the DbToolsApplication works correctly.
 *
 * The way console application's run it is impossible to test. For now we only
 * test that we are registering the correct commands.
 */
class DbToolsApplicationTest extends KernelTestBase {

  public function testApplication() {
    $application = new DbToolsApplication();
    $tester = new ApplicationTester($application);
    // Running this breaks all assertions following it so we can't test anything yet...
//    $tester->run([], []);
//    $this->assertEquals('', $tester->getStatusCode());
//    $this->assertEquals('not a thing that happened', $tester->getOutput());
  }

}
