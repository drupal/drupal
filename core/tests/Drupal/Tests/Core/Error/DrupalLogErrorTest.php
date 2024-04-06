<?php

declare(strict_types=1);

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
   *
   * @dataProvider provideFatalExitCodeData
   */
  public function testFatalExitCode(string $script, string $output, string $errorOutput, bool $processIsSuccessful): void {
    // We need to override the current working directory for invocations from
    // run-tests.sh to work properly.
    $process = new PhpProcess($script, $this->root);
    $process->run();

    // Assert the output strings as unrelated errors (like the log-exit.php
    // script throwing a PHP error) would still pass the final assertion.
    $this->assertEquals($output, $process->getOutput());
    $this->assertEquals($errorOutput, $process->getErrorOutput());
    $this->assertSame($processIsSuccessful, $process->isSuccessful());
  }

  public static function provideFatalExitCodeData(): array {
    $verbose = "\$GLOBALS['config']['system.logging']['error_level'] = 'verbose';";
    $scriptBody = self::getScriptBody();
    $data['normal'] = [
      "<?php\n\$fatal = TRUE;\n$scriptBody",
      "kernel test: This is a test message in test_function (line 456 of test.module).\n",
      "kernel test: This is a test message in test.module on line 456 backtrace\nand-more-backtrace\n",
      FALSE,
    ];
    $data['verbose'] = [
      "<?php\n\$fatal = FALSE;\n$verbose\n$scriptBody",
      "<details class=\"error-with-backtrace\"><summary><em class=\"placeholder\">kernel test</em>: This is a test message in <em class=\"placeholder\">test_function</em> (line <em class=\"placeholder\">456</em> of <em class=\"placeholder\">test.module</em>).</summary><pre class=\"backtrace\"></pre></details>",
      "",
      TRUE,
    ];
    return $data;
  }

  protected static function getScriptBody(): string {
    return <<<'EOT'
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
  '@backtrace_string' => "backtrace\nand-more-backtrace",
  'severity_level' => 0,
  'backtrace' => [],
  'exception' => NULL,
];
_drupal_log_error($error, $fatal);
EOT;
  }

}
