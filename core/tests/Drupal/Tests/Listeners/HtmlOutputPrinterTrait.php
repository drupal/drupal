<?php

namespace Drupal\Tests\Listeners;

use Drupal\Component\Utility\Html;

/**
 * Defines a class for providing html output results for functional tests.
 *
 * @internal
 */
trait HtmlOutputPrinterTrait {

  /**
   * File to write html links to.
   *
   * @var string
   */
  protected $browserOutputFile;

  /**
   * {@inheritdoc}
   */
  public function __construct($out = NULL, $verbose = FALSE, $colors = self::COLOR_DEFAULT, $debug = FALSE, $numberOfColumns = 80, $reverse = FALSE) {
    parent::__construct($out, $verbose, $colors, $debug, $numberOfColumns, $reverse);

    $this->setUpHtmlOutput();
  }

  /**
   * Creates the file to list the HTML output created during the test.
   *
   * @see \Drupal\Tests\BrowserTestBase::initBrowserOutputFile()
   */
  protected function setUpHtmlOutput() {
    if ($html_output_directory = getenv('BROWSERTEST_OUTPUT_DIRECTORY')) {
      // Initialize html output debugging.
      $html_output_directory = rtrim($html_output_directory, '/');

      // Check if directory exists.
      if (!is_dir($html_output_directory) || !is_writable($html_output_directory)) {
        $this->writeWithColor('bg-red, fg-black', "HTML output directory $html_output_directory is not a writable directory.");
      }
      else {
        // Convert to a canonicalized absolute pathname just in case the current
        // working directory is changed.
        $html_output_directory = realpath($html_output_directory);
        $this->browserOutputFile = tempnam($html_output_directory, 'browser_output_');
        if ($this->browserOutputFile) {
          touch($this->browserOutputFile);
        }
        else {
          $this->writeWithColor('bg-red, fg-black', "Unable to create a temporary file in $html_output_directory.");
        }
      }
    }

    if ($this->browserOutputFile) {
      putenv('BROWSERTEST_OUTPUT_FILE=' . $this->browserOutputFile);
    }
    else {
      // Remove any environment variable.
      putenv('BROWSERTEST_OUTPUT_FILE');
    }
  }

  /**
   * Prints the list of HTML output generated during the test.
   */
  protected function printHtmlOutput() {
    if ($this->browserOutputFile) {
      $contents = file_get_contents($this->browserOutputFile);
      if ($contents) {
        $this->writeNewLine();
        $this->writeWithColor('bg-yellow, fg-black', 'HTML output was generated');
        $this->write($contents);
      }
      // No need to keep the file around any more.
      unlink($this->browserOutputFile);
    }
  }

  /**
   * Prints HTML output links for the Simpletest UI.
   */
  public function simpletestUiWrite($buffer) {
    $buffer = Html::escape($buffer);
    // Turn HTML output URLs into clickable link <a> tags.
    $url_pattern = '@https?://[^\s]+@';
    $buffer = preg_replace($url_pattern, '<a href="$0" target="_blank" title="$0">$0</a>', $buffer);
    // Make the output readable in HTML by breaking up lines properly.
    $buffer = nl2br($buffer);

    print $buffer;
  }

}
