<?php

namespace Drupal\Tests\Listeners\Legacy;

use Drupal\Tests\Listeners\HtmlOutputPrinterTrait;

/**
 * Defines a class for providing html output results for functional tests.
 *
 * @internal
 */
class HtmlOutputPrinter extends \PHPUnit_TextUI_ResultPrinter {
  use HtmlOutputPrinterTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct($out = NULL, $verbose = FALSE, $colors = self::COLOR_DEFAULT, $debug = FALSE, $numberOfColumns = 80) {
    parent::__construct($out, $verbose, $colors, $debug, $numberOfColumns);

    $this->setUpHtmlOutput();
  }

  /**
   * {@inheritdoc}
   */
  public function printResult(\PHPUnit_Framework_TestResult $result) {
    parent::printResult($result);

    $this->printHtmlOutput();
  }

}
