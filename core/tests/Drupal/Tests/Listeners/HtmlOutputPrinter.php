<?php

namespace Drupal\Tests\Listeners;

use PHPUnit\Framework\TestResult;
use PHPUnit\TextUI\ResultPrinter;

/**
 * Defines a class for providing html output results for functional tests.
 *
 * @internal
 */
class HtmlOutputPrinter extends ResultPrinter {
  use HtmlOutputPrinterTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct($out = NULL, $verbose = FALSE, $colors = self::COLOR_DEFAULT, $debug = FALSE, $numberOfColumns = 80, $reverse = FALSE) {
    parent::__construct($out, $verbose, $colors, $debug, $numberOfColumns, $reverse);

    $this->setUpHtmlOutput();
  }

  /**
   * {@inheritdoc}
   */
  public function printResult(TestResult $result) {
    parent::printResult($result);

    $this->printHtmlOutput();
  }

}
