<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Test;

use Drupal\Core\File\FileExists;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Process\Process;

/**
 * Test to ensure that functional tests produce debug HTML output when required.
 *
 * @group browsertestbase
 * @group #slow
 */
class FunctionalTestDebugHtmlOutputTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensures that functional tests produce debug HTML output when required.
   *
   * Note: this test must be a BrowserTestBase to ensure all requirements for
   * running a functional test are met.
   */
  public function testFunctionalTestDebugHtmlOutput(): void {
    $command = [
      'vendor/bin/phpunit',
      'core/tests/Drupal/FunctionalTests/Test/FunctionalTestDebugHtmlOutputHelperTest.php',
    ];

    // Test with the default settings in phpunit.xml.dist.
    $config = [
      '--configuration',
      'core',
    ];
    $process = new Process(array_merge($command, $config));
    $process->setWorkingDirectory($this->root)
      ->setTimeout(300)
      ->setIdleTimeout(300);
    $process->run();
    $this->assertEquals(0, $process->getExitCode(),
      'COMMAND: ' . $process->getCommandLine() . "\n" .
      'OUTPUT: ' . $process->getOutput() . "\n" .
      'ERROR: ' . $process->getErrorOutput() . "\n");
    $this->assertStringContainsString('HTML output was generated.', $process->getOutput());
    $this->assertStringContainsString('Drupal_FunctionalTests_Test_FunctionalTestDebugHtmlOutputHelperTest', $process->getOutput());

    // Test without verbose output, set in xml.
    $alteredConfigFile = $this->getAlteredPhpunitXmlConfigurationFile(
      '<parameter name="verbose" value="true"/>',
      '<parameter name="verbose" value="false"/>',
    );
    $config = [
      '--configuration',
      $alteredConfigFile,
    ];
    $process = new Process(array_merge($command, $config));
    $process->setWorkingDirectory($this->root)
      ->setTimeout(300)
      ->setIdleTimeout(300);
    $process->run();
    $this->assertEquals(0, $process->getExitCode(),
      'COMMAND: ' . $process->getCommandLine() . "\n" .
      'OUTPUT: ' . $process->getOutput() . "\n" .
      'ERROR: ' . $process->getErrorOutput() . "\n");
    $this->assertMatchesRegularExpression('/HTML output was generated, \d+ page\(s\)\./m', $process->getOutput());
    unlink($alteredConfigFile);

    // Test without verbose output, overridden by BROWSERTEST_OUTPUT_VERBOSE
    // environment variable.
    $config = [
      '--configuration',
      'core',
    ];
    $process = new Process(array_merge($command, $config));
    $process->setWorkingDirectory($this->root)
      ->setTimeout(300)
      ->setIdleTimeout(300);
    $process->run(NULL, [
      'BROWSERTEST_OUTPUT_VERBOSE' => 'false',
    ]);
    $this->assertEquals(0, $process->getExitCode(),
      'COMMAND: ' . $process->getCommandLine() . "\n" .
      'OUTPUT: ' . $process->getOutput() . "\n" .
      'ERROR: ' . $process->getErrorOutput() . "\n");
    $this->assertMatchesRegularExpression('/HTML output was generated, \d+ page\(s\)\./m', $process->getOutput());

    // Test with a wrong output directory, set in xml.
    $alteredConfigFile = $this->getAlteredPhpunitXmlConfigurationFile(
      '<parameter name="outputDirectory" value="sites/simpletest/browser_output"/>',
      '<parameter name="outputDirectory" value="can_we_assume_that_a_subdirectory_with_this_name_does_not_exist"/>',
    );
    $config = [
      '--configuration',
      $alteredConfigFile,
    ];
    $process = new Process(array_merge($command, $config));
    $process->setWorkingDirectory($this->root)
      ->setTimeout(300)
      ->setIdleTimeout(300);
    $process->run(NULL, [
      'BROWSERTEST_OUTPUT_DIRECTORY' => FALSE,
    ]);
    $this->assertEquals(0, $process->getExitCode(),
      'COMMAND: ' . $process->getCommandLine() . "\n" .
      'OUTPUT: ' . $process->getOutput() . "\n" .
      'ERROR: ' . $process->getErrorOutput() . "\n");
    $this->assertStringContainsString('HTML output directory can_we_assume_that_a_subdirectory_with_this_name_does_not_exist is not a writable directory.', $process->getOutput());
    unlink($alteredConfigFile);

    // Test with a wrong output directory, overridden by
    // BROWSERTEST_OUTPUT_DIRECTORY environment variable.
    $config = [
      '--configuration',
      'core',
    ];
    $process = new Process(array_merge($command, $config));
    $process->setWorkingDirectory($this->root)
      ->setTimeout(300)
      ->setIdleTimeout(300);
    $process->run(NULL, [
      'BROWSERTEST_OUTPUT_DIRECTORY' => 'can_we_assume_that_a_subdirectory_with_this_name_does_not_exist',
    ]);
    $this->assertEquals(0, $process->getExitCode(),
      'COMMAND: ' . $process->getCommandLine() . "\n" .
      'OUTPUT: ' . $process->getOutput() . "\n" .
      'ERROR: ' . $process->getErrorOutput() . "\n");
    $this->assertStringContainsString('HTML output directory can_we_assume_that_a_subdirectory_with_this_name_does_not_exist is not a writable directory.', $process->getOutput());

    // Test disabling by setting BROWSERTEST_OUTPUT_DIRECTORY = ''.
    $config = [
      '--configuration',
      'core',
    ];
    $process = new Process(array_merge($command, $config));
    $process->setWorkingDirectory($this->root)
      ->setTimeout(300)
      ->setIdleTimeout(300);
    $process->run(NULL, [
      'BROWSERTEST_OUTPUT_DIRECTORY' => '',
    ]);
    $this->assertEquals(0, $process->getExitCode(),
      'COMMAND: ' . $process->getCommandLine() . "\n" .
      'OUTPUT: ' . $process->getOutput() . "\n" .
      'ERROR: ' . $process->getErrorOutput() . "\n");
    $this->assertStringContainsString('HTML output disabled by BROWSERTEST_OUTPUT_DIRECTORY = \'\'.', $process->getOutput());

    // Test missing 'outputDirectory' parameter.
    $alteredConfigFile = $this->getAlteredPhpunitXmlConfigurationFile(
      '<parameter name="outputDirectory" value="sites/simpletest/browser_output"/>',
      '',
    );
    $config = [
      '--configuration',
      $alteredConfigFile,
    ];
    $process = new Process(array_merge($command, $config));
    $process->setWorkingDirectory($this->root)
      ->setTimeout(300)
      ->setIdleTimeout(300);
    $process->run(NULL, [
      'BROWSERTEST_OUTPUT_DIRECTORY' => FALSE,
    ]);
    $this->assertEquals(0, $process->getExitCode(),
      'COMMAND: ' . $process->getCommandLine() . "\n" .
      'OUTPUT: ' . $process->getOutput() . "\n" .
      'ERROR: ' . $process->getErrorOutput() . "\n");
    $this->assertStringContainsString('HTML output directory not specified.', $process->getOutput());
    unlink($alteredConfigFile);
  }

  private function getAlteredPhpunitXmlConfigurationFile(array|string $search, array|string $replace): string {
    $fileSystem = \Drupal::service('file_system');
    $copiedConfigFile = $fileSystem->tempnam($this->root . \DIRECTORY_SEPARATOR . 'core', 'pux');
    $fileSystem->copy($this->root . \DIRECTORY_SEPARATOR . 'core' . \DIRECTORY_SEPARATOR . 'phpunit.xml.dist', $copiedConfigFile, FileExists::Replace);
    $content = file_get_contents($copiedConfigFile);
    $content = str_replace($search, $replace, $content);
    file_put_contents($copiedConfigFile, $content);
    return $fileSystem->realpath($copiedConfigFile);
  }

}
