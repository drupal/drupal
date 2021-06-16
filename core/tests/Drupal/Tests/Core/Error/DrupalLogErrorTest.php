<?php

namespace Drupal\Tests\Core\Error;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\Process\PhpProcess;

/**
 * Tests logging of errors in core/error.inc.
 *
 * @group Error
 */
class DrupalLogErrorTest extends UnitTestCase {

  /**
   * Tests that fatal errors return a non-zero exit code.
   */
  public function testFatalExitCode() {
    $script = <<<'EOT'
<?php
if (PHP_SAPI !== 'cli') {
  return;
}

$autoloader = require_once 'autoload.php';
require_once 'core/includes/errors.inc';
define('DRUPAL_TEST_IN_CHILD_SITE', FALSE);

$error = [
  '%type' => 'kernel test',
  '@message' => 'This is a test message',
  '%function' => 'test_function',
  '%file' => 'test.module',
  '%line' => 456,
  '@backtrace_string' => 'backtrace',
  'severity_level' => 0,
  'backtrace' => [],
  'exception' => NULL,
];
_drupal_log_error($error, TRUE);
EOT;

    // We need to override the current working directory for invocations from
    // run-tests.sh to work properly.
    $process = new PhpProcess($script, $this->root);
    $process->run();

    // Assert the output strings as unrelated errors (like the log-exit.php
    // script throwing a PHP error) would still pass the final assertion.
    $this->assertEquals("kernel test: This is a test message in test_function (line 456 of test.module).\n", $process->getOutput());
    $this->assertEquals("kernel test: This is a test message in test.module on line 456 backtrace\n", $process->getErrorOutput());
    $this->assertFalse($process->isSuccessful());
  }

}
