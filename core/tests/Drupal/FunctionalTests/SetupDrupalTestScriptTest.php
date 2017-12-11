<?php

namespace Drupal\FunctionalTests;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Tests setup-drupal-test.php.
 *
 * @group core
 */
class SetupDrupalTestScriptTest extends UnitTestCase {

  public function testInstallScript() {
    $php_executable_finder = new PhpExecutableFinder();
    $php = $php_executable_finder->find();

    $db_url = getenv('SIMPLETEST_DB');
    $base_url = getenv('SIMPLETEST_BASE_URL');
    $process = new Process("$php {$this->root}/core/scripts/setup-drupal-test.php --db_url={$db_url} --base_url={$base_url}");
    $process->run(function ($type, $data) {
      // @todo How does one test an async API?
      // This code might happen after the test function is done.
    });
  }

}
