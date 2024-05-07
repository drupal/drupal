<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension\HtmlLogging;

use PHPUnit\Event\Facade;
use PHPUnit\Event\TestRunner\Finished as TestRunnerFinished;
use PHPUnit\Event\TestRunner\Started as TestRunnerStarted;

/**
 * Drupal's extension for providing HTML output results for functional tests.
 *
 * @internal
 */
final class HtmlOutputLogger {

  /**
   * The singleton instance.
   */
  private static ?self $instance = NULL;

  /**
   * A file with list of links to HTML pages generated.
   */
  private ?string $browserOutputFile = NULL;

  /**
   * @throws \PHPUnit\Event\EventFacadeIsSealedException
   * @throws \PHPUnit\Util\Exception
   * @throws \PHPUnit\Event\UnknownSubscriberTypeException
   * @throws \RuntimeException
   */
  private function __construct(
    private readonly string $outputDirectory,
    private readonly bool $outputVerbose,
    private readonly Facade $facade,
  ) {
    $this->facade->registerSubscriber(new TestRunnerStartedSubscriber($this));
    $this->facade->registerSubscriber(new TestRunnerFinishedSubscriber($this));
  }

  /**
   * Initializes the extension.
   *
   * @param string $outputDirectory
   *   The directory where the HTML pages should be generated.
   * @param bool $outputVerbose
   *   If TRUE, a list of links generated will be output at the end of the test
   *   run; if FALSE, only a summary with the count of pages generated.
   *
   * @throws \PHPUnit\Event\EventFacadeIsSealedException
   * @throws \PHPUnit\Util\Exception
   * @throws \PHPUnit\Event\UnknownSubscriberTypeException
   * @throws \RuntimeException
   */
  public static function init(string $outputDirectory, bool $outputVerbose): void {
    if (self::$instance === NULL) {
      $realDirectory = realpath($outputDirectory);
      if ($realDirectory === FALSE || !is_dir($realDirectory) || !is_writable($realDirectory)) {
        print "HTML output directory {$outputDirectory} is not a writable directory.\n\n";
        return;
      }
      self::$instance = new self($realDirectory, $outputVerbose, Facade::instance());
    }
  }

  /**
   * Determines if the extension is enabled.
   *
   * @return bool
   *   TRUE if enabled, FALSE if disabled.
   */
  public static function isEnabled(): bool {
    return self::$instance !== NULL;
  }

  /**
   * Logs a link to a generated HTML page.
   *
   * @param string $logEntry
   *   A link to a generated HTML page.
   *
   * @throws \RuntimeException
   */
  public static function log(string $logEntry): void {
    if (!self::isEnabled()) {
      throw new \RuntimeException("HTML output is not enabled");
    }

    $browserOutputFile = getenv('BROWSERTEST_OUTPUT_FILE');
    file_put_contents($browserOutputFile, $logEntry . "\n", FILE_APPEND);
  }

  /**
   * Empties the list of the HTML output created during the test run.
   */
  public function testRunnerStarted(TestRunnerStarted $event): void {
    if (!self::isEnabled()) {
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
    if (!self::isEnabled()) {
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
