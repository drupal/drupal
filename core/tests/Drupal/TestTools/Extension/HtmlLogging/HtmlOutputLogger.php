<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension\HtmlLogging;

use PHPUnit\Event\TestRunner\Finished as TestRunnerFinished;
use PHPUnit\Event\TestRunner\Started as TestRunnerStarted;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * Drupal's extension for providing HTML output results for functional tests.
 *
 * @internal
 */
final class HtmlOutputLogger implements Extension {

  /**
   * The status of the extension.
   */
  private bool $enabled = FALSE;

  /**
   * A file with list of links to HTML pages generated.
   */
  private ?string $browserOutputFile = NULL;

  /**
   * A file with list of links to HTML pages generated.
   */
  private string $outputDirectory;

  /**
   * Verbosity of the final report.
   *
   * If TRUE, a list of links generated will be output at the end of the test
   * run; if FALSE, only a summary with the count of pages generated.
   */
  private bool $outputVerbose;

  /**
   * {@inheritdoc}
   */
  public function bootstrap(
    Configuration $configuration,
    Facade $facade,
    ParameterCollection $parameters,
  ): void {
    // Determine output directory.
    $envDirectory = getenv('BROWSERTEST_OUTPUT_DIRECTORY');
    if ($envDirectory === "") {
      print "HTML output disabled by BROWSERTEST_OUTPUT_DIRECTORY = ''.\n\n";
      return;
    }
    elseif ($envDirectory !== FALSE) {
      $directory = $envDirectory;
    }
    elseif ($parameters->has('outputDirectory')) {
      $directory = $parameters->get('outputDirectory');
    }
    else {
      print "HTML output directory not specified.\n\n";
      return;
    }
    $realDirectory = realpath($directory);
    if ($realDirectory === FALSE || !is_dir($realDirectory) || !is_writable($realDirectory)) {
      print "HTML output directory {$directory} is not a writable directory.\n\n";
      return;
    }
    $this->outputDirectory = $realDirectory;

    // Determine output verbosity.
    $envVerbose = getenv('BROWSERTEST_OUTPUT_VERBOSE');
    if ($envVerbose !== FALSE) {
      $verbose = $envVerbose;
    }
    elseif ($parameters->has('verbose')) {
      $verbose = $parameters->get('verbose');
    }
    else {
      $verbose = FALSE;
    }
    $this->outputVerbose = filter_var($verbose, \FILTER_VALIDATE_BOOLEAN);

    $facade->registerSubscriber(new TestRunnerStartedSubscriber($this));
    $facade->registerSubscriber(new TestRunnerFinishedSubscriber($this));

    $this->enabled = TRUE;
  }

  /**
   * Logs a link to a generated HTML page.
   *
   * @param string $logEntry
   *   A link to a generated HTML page, should not contain a trailing newline.
   *
   * @throws \RuntimeException
   */
  public static function log(string $logEntry): void {
    $browserOutputFile = getenv('BROWSERTEST_OUTPUT_FILE');
    if ($browserOutputFile === FALSE) {
      throw new \RuntimeException("HTML output is not enabled");
    }
    file_put_contents($browserOutputFile, $logEntry . "\n", FILE_APPEND);
  }

  /**
   * Empties the list of the HTML output created during the test run.
   */
  public function testRunnerStarted(TestRunnerStarted $event): void {
    if (!$this->enabled) {
      throw new \RuntimeException("HTML output is not enabled");
    }

    // Convert to a canonicalized absolute pathname just in case the current
    // working directory is changed.
    $this->browserOutputFile = tempnam($this->outputDirectory, 'browser_output_');
    if ($this->browserOutputFile) {
      touch($this->browserOutputFile);
      putenv('BROWSERTEST_OUTPUT_FILE=' . $this->browserOutputFile);
    }
    else {
      // Remove any environment variable.
      putenv('BROWSERTEST_OUTPUT_FILE');
      throw new \RuntimeException("Unable to create a temporary file in {$this->outputDirectory}.");
    }
  }

  /**
   * Prints the list of HTML output generated during the test.
   */
  public function testRunnerFinished(TestRunnerFinished $event): void {
    if (!$this->enabled) {
      throw new \RuntimeException("HTML output is not enabled");
    }

    $contents = file_get_contents($this->browserOutputFile);
    if ($contents) {
      print "\n\n";
      if ($this->outputVerbose) {
        print "HTML output was generated.\n";
        print $contents;
      }
      else {
        print "HTML output was generated, " . count(explode("\n", $contents)) . " page(s).\n";
      }
    }

    // No need to keep the file around any more.
    unlink($this->browserOutputFile);
    putenv('BROWSERTEST_OUTPUT_FILE');
    $this->browserOutputFile = NULL;
  }

}
